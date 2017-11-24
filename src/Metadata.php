<?php

namespace Fei\Service\Connect\Client;

use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\EntityDescriptor;

/**
 * Class Metadata
 *
 * @package Fei\Service\Connect\Client
 */
class Metadata
{
    /**
     * @var EntityDescriptor
     */
    protected $identityProvider;

    /**
     * @var EntityDescriptor
     */
    protected $serviceProvider;

    /**
     * Create an instance of Metadata
     *
     * @param string $idpMetadataFilePath
     * @param string $spMetadataFilePath
     *
     * @return Metadata
     */
    public static function load($idpMetadataFilePath, $spMetadataFilePath)
    {
        $metadata = (new static());

        return $metadata
            ->setIdentityProvider($metadata->createEntityDescriptor(file_get_contents($idpMetadataFilePath)))
            ->setServiceProvider($metadata->createEntityDescriptor(file_get_contents($spMetadataFilePath)));
    }

    /**
     * Get Identity Provider descriptor
     *
     * @return EntityDescriptor
     */
    public function getIdentityProvider()
    {
        if (is_null($this->identityProvider)) {
            $this->identityProvider = new EntityDescriptor();
        }

        return $this->identityProvider;
    }

    /**
     * Set Identity Provider descriptor
     *
     * @param EntityDescriptor $identityProvider
     *
     * @return $this
     */
    public function setIdentityProvider(EntityDescriptor $identityProvider)
    {
        $this->identityProvider = $identityProvider;

        return $this;
    }

    /**
     * Get Service Provider descriptor
     *
     * @return EntityDescriptor
     */
    public function getServiceProvider()
    {
        if (is_null($this->serviceProvider)) {
            $this->serviceProvider = new EntityDescriptor();
        }

        return $this->serviceProvider;
    }

    /**
     * Set Service Provider descriptor
     *
     * @param EntityDescriptor $serviceProvider
     *
     * @return $this
     */
    public function setServiceProvider(EntityDescriptor $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;

        return $this;
    }

    /**
     * Returns the first Identity Provider Descriptor
     *
     * @return \LightSaml\Model\Metadata\IdpSsoDescriptor|null
     */
    public function getFirstIdpSsoDescriptor()
    {
        if (empty($this->getIdentityProvider()->getAllIdpSsoDescriptors())) {
            throw new \LogicException('A Identity Provider descriptor must be registered');
        }

        return $this->getIdentityProvider()->getFirstIdpSsoDescriptor();
    }

    /**
     * Returns the first Identity Provider SSO location
     *
     * @return \LightSaml\Model\Metadata\SingleSignOnService
     */
    public function getFirstSso()
    {
        if (empty($this->getFirstIdpSsoDescriptor()->getAllSingleSignOnServices())) {
            throw new \LogicException('The Identity Provider descriptor must have one SSO registered');
        }

        return $this->getFirstIdpSsoDescriptor()->getFirstSingleSignOnService();
    }

    /**
     * Returns the first Service Provider Descriptor
     *
     * @return \LightSaml\Model\Metadata\SpSsoDescriptor|null
     */
    public function getFirstSpSsoDescriptor()
    {
        if (empty($this->getServiceProvider()->getAllSpSsoDescriptors())) {
            throw new \LogicException('A Service Provider descriptor must be registered');
        }

        return $this->getServiceProvider()->getFirstSpSsoDescriptor();
    }

    /**
     * Returns the first Service Provider ACS
     *
     * @return \LightSaml\Model\Metadata\AssertionConsumerService
     */
    public function getFirstAcs()
    {
        if (empty($this->getFirstSpSsoDescriptor()->getAllAssertionConsumerServices())) {
            throw new \LogicException('The Service Provider must have one ACS service registered');
        }

        return $this->getFirstSpSsoDescriptor()->getFirstAssertionConsumerService();
    }

    /**
     * Returns the first logout Service
     *
     * @return \LightSaml\Model\Metadata\SingleLogoutService
     */
    public function getFirstLogout()
    {
        if (empty($this->getFirstSpSsoDescriptor()->getAllSingleLogoutServices())) {
            throw new \LogicException('The Service Provider must have one Logout service registered');
        }

        return $this->getFirstSpSsoDescriptor()->getFirstSingleLogoutService();
    }

    /**
     * Create an EntityDescriptor instance with its XML metadata contents
     *
     * @param string $xml
     *
     * @return EntityDescriptor
     */
    public function createEntityDescriptor($xml)
    {
        $deserializationContext = new DeserializationContext();
        $deserializationContext->getDocument()->loadXML($xml);

        $ed = new EntityDescriptor();
        $ed->deserialize($deserializationContext->getDocument(), $deserializationContext);

        return $ed;
    }
}
