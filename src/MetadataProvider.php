<?php

namespace Fei\Service\Connect\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\RequestDescriptor;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\Config\Config;
use Fei\Service\Connect\Common\Admin\Message\SubscribeMessage;
use Fei\Service\Connect\Common\Admin\Message\SpEntityConfigurationMessage;
use Fei\Service\Connect\Common\Cryptography\Cryptography;
use Fei\Service\Connect\Common\Cryptography\X509CertificateGen;
use Fei\Service\Connect\Common\Message\Extractor\MessageExtractor;
use Fei\Service\Connect\Common\Message\Hydrator\MessageHydrator;
use Fei\Service\Connect\Common\Message\MessageDecorator;
use Fei\Service\Connect\Common\Message\SignedMessageDecorator;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;

/**
 * Class MetadataProvider
 *
 * @package Fei\Service\Connect\Client
 */
class MetadataProvider extends AbstractApiClient
{
    const API_IDP = '/api/sp';

    /**
     * MetadataProvider constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->setTransport(new BasicTransport());
    }

    /**
     * Register an application
     *
     * @param Config           $config
     * @param IdpSsoDescriptor $idp
     */
    public function subscribeApplication(Config $config, IdpSsoDescriptor $idp)
    {
        $name = $config->getName() ? $config->getName() : $config->getEntityID();

        $message = (new SubscribeMessage())
            ->setEntityID($config->getEntityID())
            ->setName($name)
            ->setAdminPathInfo($config->getEntityID() . $config->getAdminPathInfo());

        $url = $this->buildUrl(self::API_IDP . '/subscribe');

        $message = (new SignedMessageDecorator($message))
            ->setCertificate((new X509CertificateGen())->createX509Certificate($config->getPrivateKey()))
            ->sign($config->getPrivateKey());

        $content = base64_encode(
            (new Cryptography())->encrypt(
                json_encode(new MessageDecorator($message)),
                $idp->getFirstKeyDescriptor(KeyDescriptor::USE_ENCRYPTION)
                    ->getCertificate()
                    ->toPem()
            )
        );

        $request = (new RequestDescriptor())
            ->setMethod('POST')
            ->setRawData($content)
            ->setUrl($url);

        $this->send($request);
    }

    /**
     * Get the SP entity configuration from Connect IdP
     *
     * @param string $entityID
     *
     * @return SpEntityConfigurationMessage
     */
    public function getSPEntityConfiguration($entityID)
    {
        $url = $this->buildUrl(self::API_IDP . '?entityID=' . $entityID);

        $request = (new RequestDescriptor())
            ->setMethod('GET')
            ->setUrl($url);

        $response = $this->send($request);

        $extractor = (new MessageExtractor())->setHydrator(new MessageHydrator());

        /** @var SignedMessageDecorator $message */
        $message = $extractor->extract(\json_decode($response->getBody(), true));

        return $message->getMessage();
    }
}
