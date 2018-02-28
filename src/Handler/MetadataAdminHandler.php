<?php

namespace Fei\Service\Connect\Client\Handler;

use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Exception\PingException;
use Fei\Service\Connect\Client\Exception\SamlException;
use Fei\Service\Connect\Common\Admin\Message\SpEntityConfigurationMessage;
use Fei\Service\Connect\Common\Cryptography\X509CertificateGen;
use Fei\Service\Connect\Common\Message\ErrorMessage;
use Fei\Service\Connect\Common\Message\Http\MessageResponse;
use Fei\Service\Connect\Common\Message\SignedMessageDecorator;
use LightSaml\Model\Metadata\KeyDescriptor;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Stream;

/**
 * Class MetadataAdminHandler
 *
 * @package Fei\Service\Connect\Client\Handler
 */
class MetadataAdminHandler
{
    /**
     * Metadata request
     *
     * @param Connect $connect
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Connect $connect)
    {
        $metadata = $connect->getConfig()->getSamlMetadataBaseDir() . '/' . $connect->getConfig()->getSpMetadataFile();

        if (!is_file($metadata)) {
            throw new SamlException('No Saml metadata file found!');
        }

        $response = new Response\TextResponse(file_get_contents($metadata), 200, [
            'content-type' => 'application/samlmetadata+xml'
        ]);

        return $response;
    }
}
