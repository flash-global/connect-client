<?php

namespace Fei\Service\Connect\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\RequestDescriptor;
use Fei\Service\Connect\Client\Exception\TokenException;
use Fei\Service\Connect\Common\Entity\Application;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Guzzle\Http\Exception\BadResponseException;
use Psr\SimpleCache\CacheInterface;

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
     * @var CacheInterface
     */
    protected $cache;

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
     * Get Cache
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set Cache
     *
     * @param CacheInterface $cache
     *
     * @return $this
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Tells if cache is set
     *
     * @return bool
     */
    public function hasCache()
    {
        return $this->getCache() instanceof CacheInterface;
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
            ->setUrl($this->buildUrl('/api/token'))
            ->setMethod('POST');
        $request->setBodyParams(['token-request' => json_encode($tokenRequest->toArray())]);

        try {
            return json_decode($this->send($request)->getBody(), true);
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
     * Create an application token
     *
     * @param string          $application
     * @param resource|string $privateKey
     *
     * @return string
     */
    public function createApplicationToken($application, $privateKey)
    {
        $tokenRequest = $this->getTokenizer()->signTokenRequest(
            $this->getTokenizer()->createApplicationTokenRequest(
                $application
            ),
            $privateKey
        );

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl('/api/token'))
            ->setMethod('POST');
        $request->setBodyParams([
            'token-request' => json_encode($tokenRequest->toArray())
        ]);

        try {
            return json_decode($this->send($request)->getBody(), true);
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
     * @return array
     *
     * @throws ApiClientException
     */
    public function validate($token)
    {
        if ($this->hasCache()) {
            $body = $this->getCache()->get($token);

            // Check if token is expired
            if (!is_null($body)) {
                $body = json_decode($body, true);
                if (new \DateTime($body['expire_at']) >= new \DateTime()) {
                    return $this->buildValidationReturn($body);
                } else {
                    $this->getCache()->delete($token);
                    throw new TokenException('The provided token is expired');
                }
            }
        }

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(sprintf('/api/token/validate?token=%s', (string) $token)))
            ->setMethod('GET');

        try {
            $body = json_decode($this->send($request)->getBody(), true);

            if ($this->hasCache()) {
                $this->getCache()->set(
                    $token,
                    json_encode($body),
                    (new \DateTime())->diff(new \DateTime($body['expire_at']))
                );
            }

            return $this->buildValidationReturn($body);
        } catch (ApiClientException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody(true), true);

                throw new TokenException($error['error'], $error['code'], $previous);
            }

            throw new TokenException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Build the validation return
     *
     * @param array $body
     *
     * @return array
     */
    protected function buildValidationReturn(array $body)
    {
        if (isset($body['user'])) {
            $body['user'] = new User($body['user']);
        }

        $body['application'] = new Application($body['application']);
        $body['expire_at'] = new \DateTime($body['expire_at']);

        return $body;
    }
}
