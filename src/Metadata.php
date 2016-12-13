<?php

namespace Fei\Service\Connect\Client;

use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\SpSsoDescriptor;

/**
 * Class Metadata
 *
 * @package Fei\Service\Connect\Client
 */
class Metadata
{
    /**
     * @var IdpSsoDescriptor
     */
    protected $identityProvider;

    /**
     * @var SpSsoDescriptor
     */
    protected $serviceProvider;

    /**
     * @var string
     */
    protected $serviceProviderPrivateKey;

    /**
     * Get Identity Provider descriptor
     *
     * @return IdpSsoDescriptor
     */
    public function getIdentityProvider()
    {
        return $this->identityProvider;
    }

    /**
     * Set Identity Provider descriptor
     *
     * @param IdpSsoDescriptor $identityProvider
     *
     * @return $this
     */
    public function setIdentityProvider(IdpSsoDescriptor $identityProvider)
    {
        $this->identityProvider = $identityProvider;

        return $this;
    }

    /**
     * Get Service Provider descriptor
     *
     * @return SpSsoDescriptor
     */
    public function getServiceProvider()
    {
        return $this->serviceProvider;
    }

    /**
     * Set Service Provider descriptor
     *
     * @param SpSsoDescriptor $serviceProvider
     * @param string          $privateKey
     *
     * @return $this
     */
    public function setServiceProvider(SpSsoDescriptor $serviceProvider, $privateKey)
    {
        $this->serviceProvider = $serviceProvider;
        $this->serviceProviderPrivateKey = $privateKey;

        return $this;
    }

    /**
     * Get the Service Provider private key
     *
     * @return string
     */
    public function getServiceProviderPrivateKey()
    {
        return $this->serviceProviderPrivateKey;
    }

    /**
     * Set the Service Provider private key
     *
     * @param string $serviceProviderPrivateKey
     *
     * @return $this
     */
    public function setServiceProviderPrivateKey($serviceProviderPrivateKey)
    {
        $this->serviceProviderPrivateKey = $serviceProviderPrivateKey;

        return $this;
    }

    /**
     * Returns the first Identity Provider SSO location
     *
     * @return \LightSaml\Model\Metadata\SingleSignOnService
     */
    public function getFirstSso()
    {
        if (!$this->getIdentityProvider()->getFirstSingleSignOnService()) {
            throw new \LogicException('The Identity Provider must have one SSO registered');
        }

        return $this->getIdentityProvider()->getFirstSingleSignOnService();
    }

    /**
     * Returns the first Service Provider ACS
     *
     * @return \LightSaml\Model\Metadata\AssertionConsumerService
     */
    public function getFirstAcs()
    {
        if (!$this->getServiceProvider()->getFirstAssertionConsumerService()) {
            throw new \LogicException('The Service Provider must have one ACS registered');
        }

        return $this->getServiceProvider()->getFirstAssertionConsumerService();
    }

    /**
     * Returns the first logout Service
     *
     * @return \LightSaml\Model\Metadata\SingleLogoutService
     */
    public function getFirstLogout()
    {
        if (!$this->getServiceProvider()->getFirstSingleLogoutService()) {
            throw new \LogicException('The Service Provider must have one Logout service registered');
        }

        return $this->getServiceProvider()->getFirstSingleLogoutService();
    }
}
