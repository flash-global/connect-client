<?php

namespace Fei\Service\Connect\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\RequestDescriptor;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\Exception\UserAttributionException;
use Fei\Service\Connect\Common\Entity\Application;
use Fei\Service\Connect\Common\Entity\ApplicationGroup;
use Fei\Service\Connect\Common\Entity\Attribution;
use Fei\Service\Connect\Common\Entity\Role;
use Fei\Service\Connect\Common\Entity\User;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Class UserAttribution
 *
 * @package Fei\Service\Connect\Client
 */
class UserAttribution extends AbstractApiClient
{
    const OPTION_BASEURL = 'baseUrl';
    const IDP_ATTRIBUTIONS_API = '/api/attributions';

    /**
     * Attribution constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->setTransport(new BasicTransport());
    }

    /**
     * Add an Attribution
     *
     * @param int $sourceId
     * @param int $targetId
     * @param int $roleId
     *
     * @return Attribution|null
     */
    public function add($sourceId, $targetId, $roleId)
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::IDP_ATTRIBUTIONS_API))
            ->setMethod('POST');

        $request->setBodyParams([
            'data' => json_encode([
                'source_id' => $sourceId,
                'target_id' => $targetId,
                'role_id' => $roleId,
            ])
        ]);

        try {
            $response = json_decode($this->send($request)->getBody(), true);
            return (count($response['added']) > 0 ? reset($response['added']) : null);
        } catch (\Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody(), true);

                throw new UserAttributionException($error['error'], $error['code'], $previous);
            }

            throw new UserAttributionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Get an User Attributions
     *
     * @param int $sourceId
     * @param int|null $targetId
     *
     * @return Attribution[]
     */
    public function get($sourceId, $targetId = null)
    {
        $url = self::IDP_ATTRIBUTIONS_API . '/' . $sourceId;
        if ($targetId) {
            $url .= '?target=' . urlencode($targetId);
        }

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl($url))
            ->setMethod('GET');

        try {
            return json_decode($this->send($request)->getBody(), true);
        } catch (\Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody(), true);

                throw new UserAttributionException($error['error'], $error['code'], $previous);
            }

            throw new UserAttributionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Remove attributions for a user. An application and a role can be defined to target more attributions more
     * precisely.
     *
     * @param int $sourceId
     * @param int $targetId
     * @param int $roleId
     *   If omitted, all attributions link to this source / target will be deleted.
     */
    public function remove($sourceId, $targetId = null, $roleId = null)
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::IDP_ATTRIBUTIONS_API))
            ->setMethod('DELETE')
            ->setRawData(json_encode([
                'source_id' => $sourceId,
                'target_id' => $targetId,
                'role_id' => $roleId,
            ]));

        try {
            $this->send($request);
        } catch (\Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody(), true);

                throw new UserAttributionException($error['error'], $error['code'], $previous);
            }

            throw new UserAttributionException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
