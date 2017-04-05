<?php

namespace Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Class ProfileAssociationResponse
 *
 * @package Fei\Service\Connect\Client\Message
 */
class ProfileAssociationResponse extends Response
{
    /**
     * ProfileAssociationResponse constructor.
     *
     * @param string $message
     * @param int    $status
     * @param array  $headers
     */
    public function __construct($message, $status = 200, array $headers = [])
    {
        $this->setMessage($message);

        parent::__construct('php://memory', $status, $headers);
    }

    /**
     * @var string
     */
    protected $message;

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set Message
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Returns the encrypted message
     *
     * @param string $certificate
     *
     * @return string
     *
     * @throws ProfileAssociationException
     */
    public function encryptMessage($certificate)
    {
        $key = openssl_pkey_get_public($certificate);

        if ($key === false) {
            throw new ProfileAssociationException('Certificate isn\'t valid', 500);
        }

        openssl_public_encrypt($this->getMessage(), $encrypted, $key);

        return $encrypted;
    }

    /**
     * Build the response to send
     *
     * @param string $certificate
     *
     * @return static
     */
    public function buildMessageResponse($certificate)
    {
        $stream = new Stream('php://temp', 'wb+');

        $stream->write(\json_encode(['message' => base64_encode($this->encryptMessage($certificate))]));

        return $this
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }
}
