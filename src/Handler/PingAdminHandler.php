<?php

namespace Fei\Service\Connect\Client\Handler;

use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Exception\PingException;
use Fei\Service\Connect\Common\Admin\Message\SpEntityConfigurationMessage;
use Fei\Service\Connect\Common\Cryptography\X509CertificateGen;
use Fei\Service\Connect\Common\Message\ErrorMessage;
use Fei\Service\Connect\Common\Message\Http\MessageResponse;
use Fei\Service\Connect\Common\Message\SignedMessageDecorator;
use LightSaml\Model\Metadata\KeyDescriptor;

/**
 * Class PingAdminHandler
 *
 * @package Fei\Service\Connect\Client\Handler
 */
class PingAdminHandler
{
    /**
     * Handle Ping request
     *
     * @param Connect $connect
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws PingException
     */
    public function __invoke(Connect $connect)
    {
        $metadata = $connect->getConfig()->getSamlMetadataBaseDir() . '/' . $connect->getConfig()->getSpMetadataFile();

        $certificate = $connect->getSaml()
            ->getMetadata()
            ->getFirstIdpSsoDescriptor()
            ->getFirstKeyDescriptor(KeyDescriptor::USE_ENCRYPTION)
            ->getCertificate()
            ->toPem();

        $privateKey = $connect->getConfig()->getPrivateKey();

        if (is_file($metadata)) {
            $xml = file_get_contents($metadata);

            $message = (new SpEntityConfigurationMessage())
                ->setXml($xml)
                ->setId('');

            $message = (new SignedMessageDecorator($message))
                ->setCertificate((new X509CertificateGen())->createX509Certificate($privateKey))
                ->sign($privateKey);

            return (new MessageResponse($message))->buildEncrypted($certificate);
        }

        $message = (new ErrorMessage())->setError(sprintf('Unable to find the SP metadata file "%s"', $metadata));

        $message = (new SignedMessageDecorator($message))
            ->setCertificate((new X509CertificateGen())->createX509Certificate($privateKey))
            ->sign($privateKey);

        return (new MessageResponse($message, 500))->buildEncrypted($certificate);
    }
}
