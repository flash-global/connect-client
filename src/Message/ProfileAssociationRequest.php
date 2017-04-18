<?php

namespace Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;
use Zend\Diactoros\ServerRequest;

/**
 * Class ProfileAssociationRequest
 *
 * @package Fei\Service\Connect\Client\Message
 */
class ProfileAssociationRequest extends ServerRequest
{
    /**
     * Extract an instance of ProfileAssociationMessage from request content
     *
     * @param string $privateKey
     *
     * @return ProfileAssociationMessageInterface
     *
     * @throws ProfileAssociationException
     */
    public function extractProfileAssociationMessage($privateKey)
    {
        $this->getBody()->rewind();

        $message = $this->jsonDecode($this->getBody()->getContents());

        if (!isset($message['message'])) {
            throw new ProfileAssociationException('Message attribute must be provided', 400);
        }

        $message = base64_decode($message['message'], true);

        if ($message === false) {
            throw new ProfileAssociationException('Message attribute is not a base64 encoded string', 400);
        }

        $key = openssl_pkey_get_private($privateKey);

        if ($key === false) {
            throw new ProfileAssociationException('Private key isn\'t valid', 500);
        }

        if (!openssl_private_decrypt($message, $decrypted, $key)) {
            throw new ProfileAssociationException('Bad encrypted message attribute', 400);
        };

        $message = $this->jsonDecode($decrypted);

        return ProfileAssociationMessageFactory::getInstance($message);
    }

    /**
     * Decode JSON formatted string
     *
     * @param string $str
     *
     * @return mixed
     *
     * @throws ProfileAssociationException
     */
    protected function jsonDecode($str)
    {
        $array = \json_decode($str, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ProfileAssociationException('json_decode error: ' . json_last_error_msg(), 400);
        }

        return $array;
    }

}
