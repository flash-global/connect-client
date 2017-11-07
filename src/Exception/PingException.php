<?php

namespace Fei\Service\Connect\Client\Exception;

use Fei\Service\Connect\Common\Exception\ResponseExceptionInterface;
use Fei\Service\Connect\Common\Message\Message;
use Zend\Diactoros\Response;

/**
 * Class PingException
 *
 * @package Fei\Service\Connect\Client\Exception
 */
class PingException extends \Exception implements ResponseExceptionInterface
{
    /**
     * @var string
     */
    protected $certificate;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * Get Certificate
     *
     * @return string
     */
    public function getCertificate()
    {
        return $this->certificate;
    }

    /**
     * Set Certificate
     *
     * @param string $certificate
     *
     * @return $this
     */
    public function setCertificate($certificate)
    {
        $this->certificate = $certificate;

        return $this;
    }

    /**
     * Get PrivateKey
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set PrivateKey
     *
     * @param string $privateKey
     *
     * @return $this
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Returns a ResponseInterface method
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $response = new Response();

        $message = new Message();
        $message->setData(['message' => $this->getMessage()]);

        if (!empty($this->getPrivateKey())) {
            $message->setCertificate($this->getCertificate());
            $message->sign($this->getPrivateKey());
        }

        $response->getBody()->write(json_encode($message));

        return $response
            ->withStatus($this->getCode())
            ->withAddedHeader('Content-Type', 'application/json');
    }
}
