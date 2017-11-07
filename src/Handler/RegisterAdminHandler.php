<?php

namespace Fei\Service\Connect\Client\Handler;

use Fei\Service\Connect\Client\Config\ConfigConsistency;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\MetadataBuilder;
use Fei\Service\Connect\Common\Admin\Message\RegenMessage;
use Fei\Service\Connect\Common\Admin\Message\RegisterMessage;
use Fei\Service\Connect\Common\Admin\Message\SpEntityConfigurationMessage;
use Fei\Service\Connect\Common\Cryptography\X509CertificateGen;
use Fei\Service\Connect\Common\Message\Extractor\MessageExtractor;
use Fei\Service\Connect\Common\Message\Http\MessageResponse;
use Fei\Service\Connect\Common\Message\Hydrator\MessageHydrator;
use Fei\Service\Connect\Common\Message\SignedMessageDecorator;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Metadata\KeyDescriptor;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Class RegisterAdminHandler
 *
 * @package Fei\Service\Connect\Client\Handler
 */
class RegisterAdminHandler
{
    /**
     * Handle Register request
     *
     * @param Connect $connect
     *
     * @return ResponseInterface
     */
    public function __invoke(Connect $connect)
    {
        $request = ServerRequestFactory::fromGlobals();
        $content = $request->getBody()->getContents();

        $content = json_decode($content, true);

        $extractor = (new MessageExtractor())->setHydrator(new MessageHydrator());

        /** @var SignedMessageDecorator $message */
        $message = $extractor->extract($content);

        /** @var RegisterMessage $message */
        $message = $message->getMessage();

        if ($message instanceof RegenMessage) {
            $entityID = $message->getEntityID();
            $data['entityID'] = $entityID;

            $path = $connect->getConfig()->getSamlMetadataBaseDir().'/'.$connect->getConfig()->getSpMetadataFile();
            $builder = new MetadataBuilder();
            $data    = $data + $builder->getInformations($path) ;

            $configConsistency = new ConfigConsistency($connect->getConfig());
            $configConsistency->createRSAPrivateKey();

            $privateKey = $connect->getConfig()->getPrivateKey();

            return $this->generateXml($connect, $privateKey, $data);
        }

        $privateKey = $connect->getConfig()->getPrivateKey();

        return $this->generateXml($connect, $privateKey, [
            'entityID' => $message->getEntityID(),
            'acs'      => $message->getAcs(),
            'logout'   => $message->getLogout()
        ]);
    }

    /**
     * Generate the SP metadata XML file
     *
     * @param Connect $connect
     * @param string  $privateKey
     * @param array   $data
     *
     * @return MessageResponse
     */
    protected function generateXml(Connect $connect, $privateKey, $data)
    {
        $certificateGen = (new X509CertificateGen())->createX509Certificate($privateKey);

        $certificate = new X509Certificate();
        $certificate->loadPem($certificateGen);

        $builder = new MetadataBuilder();
        $xml = $builder->build($data['entityID'], $data['acs'], $data['logout'], $certificate);

        $path = $connect->getConfig()->getSamlMetadataBaseDir().'/'.$connect->getConfig()->getSpMetadataFile();
        file_put_contents($path, $xml);

        $message = (new SpEntityConfigurationMessage())
            ->setXml($xml);

        $message = (new SignedMessageDecorator($message))
            ->setCertificate((new X509CertificateGen())->createX509Certificate($privateKey))
            ->sign($privateKey);

        $certificate = $connect->getSaml()
            ->getMetadata()
            ->getFirstIdpSsoDescriptor()
            ->getFirstKeyDescriptor(KeyDescriptor::USE_ENCRYPTION)
            ->getCertificate()
            ->toPem();

        return (new MessageResponse($message))->buildEncrypted($certificate);
    }
}
