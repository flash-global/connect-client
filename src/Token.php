<?php

namespace Fei\Service\Connect\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\RequestDescriptor;
use Fei\Service\Connect\Client\Exception\TokenException;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Guzzle\Http\Exception\BadResponseException;

/**
 * Class Token
 *
 * @package Fei\Service\Connect\Client
 */
class Token extends AbstractApiClient
{
    /**
     * @var Tokenizer
     */
    protected $tokenizer;

    /**
     * Get Tokenizer
     *
     * @return Tokenizer
     */
    public function getTokenizer()
    {
        if (!$this->tokenizer) {
            $this->tokenizer = new Tokenizer();
        }

        return $this->tokenizer;
    }

    /**
     * Set Tokenizer
     *
     * @param Tokenizer $tokenizer
     *
     * @return $this
     */
    public function setTokenizer($tokenizer)
    {
        $this->tokenizer = $tokenizer;

        return $this;
    }

    /**
     * Create a Token
     *
     * @param Connect $connect
     *
     * @return string
     */
    public function create(Connect $connect)
    {
        $tokenRequest = $this->getTokenizer()->signTokenRequest(
            $this->getTokenizer()->createTokenRequest(
                $connect->getUser(),
                $connect->getSaml()->getMetadata()->getServiceProvider()->getID()
            ),
            $connect->getSaml()->getMetadata()->getServiceProviderPrivateKey()
        );

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(sprintf('/api/token')))
            ->setMethod('POST');
        $request->setBodyParams(['token-request' => json_encode($tokenRequest->toArray())]);

        try {
            return json_decode($this->send($request)->getBody(), true)['token'];
        } catch (\Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody(true), true);

                throw new TokenException($error['error'], $error['code'], $previous);
            }

            throw new TokenException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Validate a Token
     *
     * @param string $token
     *
     * @return User
     *
     * @throws ApiClientException
     */
    public function validate($token)
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(sprintf('/api/token/validate?token=%s', (string) $token)))
            ->setMethod('GET');

        try {
            return new User(json_decode($this->send($request)->getBody(), true));
        } catch (ApiClientException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody(true), true);

                throw new TokenException($error['error'], $error['code'], $previous);
            }

            throw new TokenException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
