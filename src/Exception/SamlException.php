<?php

namespace Fei\Service\Connect\Client\Exception;

use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use Zend\Diactoros\Response;
use LightSaml\Model\Protocol\Response as SamlResponse;

class SamlException extends \Exception
{
    /**
     * Get exception response to emit
     *
     * @return Response
     */
    public function getResponse()
    {
        $response = new Response();
        $response->getBody()->write($this->getContent());

        return $response->withAddedHeader('Content-Type', 'text/xml');
    }

    /**
     * Returns the Saml error reporting
     *
     * @return string
     */
    protected function getContent()
    {
        $response = new SamlResponse();
        $response->setStatus(new Status(new StatusCode('urn:oasis:names:tc:SAML:2.0:status:RequestDenied')));

        $context = new SerializationContext();
        $response->serialize($context->getDocument(), $context);

        return $context->getDocument()->saveXML();
    }
}
