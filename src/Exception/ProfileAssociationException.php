<?php

namespace Fei\Service\Connect\Client\Exception;

use Fei\Service\Connect\Client\Message\ProfileAssociationResponse;

/**
 * Class ProfileAssociationException
 *
 * @package Fei\Service\Connect\Client\Exception
 */
class ProfileAssociationException extends \Exception implements ResponseExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        $response = new ProfileAssociationResponse($this->getMessage());

        $status = $code = ($this->getCode() < 100 || $this->getCode() > 599) ? 500 : $this->getCode();

        return $response
            ->withAddedHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
