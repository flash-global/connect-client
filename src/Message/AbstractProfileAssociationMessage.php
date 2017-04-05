<?php

namespace Fei\Service\Connect\Client\Message;

/**
 * Class ProfileAssociationMessage
 *
 * @package Fei\Service\Connect\Client\Message
 */
abstract class AbstractProfileAssociationMessage implements ProfileAssociationMessageInterface
{
    /**
     * @var mixed
     */
    protected $message;

    /**
     * ProfileAssociationMessage constructor.
     *
     * @param mixed $message
     */
    public function __construct($message)
    {
        $this->setMessage($message);
    }

    /**
     * Set Message
     *
     * @param mixed $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Returns the profile association message sent
     *
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
}
