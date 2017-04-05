<?php

namespace Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;

/**
 * Class ProfileAssociationMessageFactory
 *
 * @package Fei\Service\Connect\Client\Message
 */
class ProfileAssociationMessageFactory
{
    /**
     * Returns an instance of ProfileAssociationMessageInterface
     *
     * @param mixed $message
     *
     * @return ProfileAssociationMessageInterface
     *
     * @throws ProfileAssociationException
     */
    public static function getInstance($message)
    {
        if (isset($message['username']) && isset($message['password'])) {
            return new UsernamePasswordMessage($message);
        }

        throw new ProfileAssociationException(
            sprintf(
                'Unable to create a instance of %s with message provided',
                ProfileAssociationMessageInterface::class
            ),
            500
        );
    }
}
