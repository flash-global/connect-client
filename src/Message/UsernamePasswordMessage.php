<?php

namespace Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;

/**
 * Class UsernamePasswordMessage
 *
 * @package Fei\Service\Connect\Client\Message
 */
class UsernamePasswordMessage extends AbstractProfileAssociationMessage
{
    /**
     * UsernamePasswordMessage constructor.
     *
     * @param mixed $message
     *
     * @throws ProfileAssociationException
     */
    public function __construct($message)
    {
        if (!isset($message['username']) || !isset($message['password'])) {
            throw new ProfileAssociationException('Username and password must be provided', 400);
        }

        parent::__construct($message);
    }

    /**
     * Returns the username from message
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getMessage()['username'];
    }

    /**
     * Returns the password from message
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->getMessage()['password'];
    }
}
