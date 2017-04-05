<?php

namespace Fei\Service\Connect\Client\Exception;

/**
 * Interface ResponseException
 *
 * @package Fei\Service\Connect\Client\Exception
 */
interface ResponseExceptionInterface
{
    /**
     * Returns a ResponseInterface method
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse();
}
