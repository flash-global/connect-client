<?php

namespace Fei\Service\Connect\Client\Exception;

use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use Zend\Diactoros\Response;
use LightSaml\Model\Protocol\Response as SamlResponse;

/**
 * Class SamlException
 *
 * @package Fei\Service\Connect\Client\Exception
 */
class SamlException extends \Exception implements ResponseExceptionInterface
{
    /**
     * @var string
     */
    protected $status;

    /**
     * SamlException constructor.
     *
     * @param string     $status
     * @param \Exception $previous
     */
    public function __construct($status = '', \Exception $previous = null)
    {
        $this->status = $status;

        parent::__construct('', 0, $previous);
    }

    /**
     * {@inheritdoc}
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
        $response->setStatus(new Status(new StatusCode($this->status)));

        $context = new SerializationContext();
        $response->serialize($context->getDocument(), $context);

        return $context->getDocument()->saveXML();
    }
}
