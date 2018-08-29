<?php

namespace Fei\Service\Connect\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\RequestDescriptor;
use Fei\ApiClient\Transport\BasicTransport;

/**
 * Class ApiToken
 * @package Fei\Service\Connect\Client
 */
class ApiToken extends AbstractApiClient
{
    const OPTION_BASEURL = 'baseUrl';
    const IDP_API_TOKEN  = '/api/apitoken';

    /**
     * ApiToken constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->setTransport(new BasicTransport());
    }

    /**
     * @param $apiToken
     * @param $application
     * @return mixed
     */
    public function hasAccess($apiToken, $application)
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::IDP_API_TOKEN))
            ->setMethod('POST')
            ->setRawData(json_encode(['apiToken' => $apiToken, 'application' => $application]));

        $response = json_decode($this->send($request)->getBody(), true);
        return $response;
    }
}
