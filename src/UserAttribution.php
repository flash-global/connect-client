<?php

namespace Fei\Service\Connect\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\RequestDescriptor;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\Exception\UserAttributionException;
use Fei\Service\Connect\Common\Entity\Application;
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
     * @param string $username
     * @param string $application
     * @param string $role
     * @param bool $isDefault
     * @param string|null $localUsername
     *
     * @return Attribution|null
     */
    public function add($username, $application, $role, $isDefault = false, $localUsername = null)
    {
        $attribution = $this->constructAttribution($username, $application, $role, $localUsername);
        $attribution->setIsDefault($isDefault);

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::IDP_ATTRIBUTIONS_API))
            ->setMethod('POST');
        $request->setBodyParams([
            'data' => json_encode([$attribution->toArray()])
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
     * @param string $username
     * @param string|null $application
     *
     * @return Attribution[]
     */
    public function get($username, $application = null)
    {
        $url = self::IDP_ATTRIBUTIONS_API . '/' . $username;
        if ($application) {
            $url .= '?application=' . urlencode($application);
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
     * Remove an Attribution
     *
     * @param string $username
     * @param string $application
     * @param string $role
     * @param string|null $localUsername
     */
    public function remove($username, $application, $role, $localUsername = null)
    {
        $attribution = $this->constructAttribution($username, $application, $role, $localUsername);

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::IDP_ATTRIBUTIONS_API))
            ->setMethod('DELETE')
            ->setRawData(json_encode([$attribution->toArray()]));

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

    /**
     * Remove several User Attributions
     *
     * @param string $username
     * @param string|null $application
     */
    public function removeAll($username, $application = null)
    {
        $url = self::IDP_ATTRIBUTIONS_API . '/' . $username;
        if ($application) {
            $url .= '?application=' . urlencode($application);
        }

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl($url))
            ->setMethod('DELETE');

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

    /**
     * Construct an Attribution entity from several parameters
     *
     * @param string $username
     * @param string $application
     * @param string $role
     * @param string|null $localUsername
     *
     * @return Attribution
     */
    protected function constructAttribution($username, $application, $role, $localUsername = null)
    {
        $attributionUser = (new User())
            ->setUserName($username);
        if ($localUsername) {
            $attributionUser->setLocalUsername($localUsername);
        }

        $attributionApplication = (new Application());
        if ($localUsername) {
            $attributionApplication->setName($application);
        } elseif (is_numeric($application) && (int) $application == $application) {
            $attributionApplication->setId((int) $application);
        } elseif (filter_var($application, FILTER_VALIDATE_URL)) {
            $attributionApplication->setUrl($application);
        } else {
            $attributionApplication->setName($application);
        }

        $attributionRole = (new Role());
        if ($localUsername) {
            $attributionRole->setRole($application . ':' . $role . ':' . $localUsername);
        } elseif (is_numeric($role) && (int) $role == $role) {
            $attributionRole->setId((int) $role);
        } else {
            $attributionRole->setRole($role);
        }

        $attribution = (new Attribution())
            ->setUser($attributionUser)
            ->setApplication($attributionApplication)
            ->setRole($attributionRole);

        return $attribution;
    }
}
