<?php

namespace Fei\Service\Connect\Client;

use Fei\Service\Connect\Common\ProfileAssociation\Message\RequestMessageInterface;

/**
 * Class Config
 *
 * @package Fei\Service\Connect\Client
 */
class Config
{
    /**
     * @var string
     */
    protected $defaultTargetPath = '/';

    /**
     * @var string
     */
    protected $logoutTargetPath = '/';

    /**
     * @var string
     */
    protected $profileAssociationPath = '/connect/profile-association';

    /**
     * @var callable
     */
    protected $profileAssociationCallback = null;

    /**
     * Get DefaultTargetPath
     *
     * @return string
     */
    public function getDefaultTargetPath()
    {
        return $this->defaultTargetPath;
    }

    /**
     * Set DefaultTargetPath
     *
     * @param string $defaultTargetPath
     *
     * @return $this
     */
    public function setDefaultTargetPath($defaultTargetPath)
    {
        $this->defaultTargetPath = $defaultTargetPath;

        return $this;
    }

    /**
     * Get LogoutTargetPath
     *
     * @return string
     */
    public function getLogoutTargetPath()
    {
        return $this->logoutTargetPath;
    }

    /**
     * Set LogoutTargetPath
     *
     * @param string $logoutTargetPath
     *
     * @return $this
     */
    public function setLogoutTargetPath($logoutTargetPath)
    {
        $this->logoutTargetPath = $logoutTargetPath;

        return $this;
    }

    /**
     * Get ProfileAssociationPath
     *
     * @return string
     */
    public function getProfileAssociationPath()
    {
        return $this->profileAssociationPath;
    }

    /**
     * Get ProfileAssociationCallback
     *
     * @return callable
     */
    public function getProfileAssociationCallback()
    {
        return $this->profileAssociationCallback;
    }

    /**
     * Returns if a profile association callback is registered
     *
     * @return bool
     */
    public function hasProfileAssociationCallback()
    {
        return !empty($this->profileAssociationCallback);
    }

    /**
     * Register a profile association callback
     *
     * @param callable $callback
     * @param string   $profileAssociationPath
     *
     * @return $this
     */
    public function registerProfileAssociation(
        callable $callback,
        $profileAssociationPath = '/connect/profile-association'
    ) {
        $parameters = (new \ReflectionFunction($callback))->getParameters();

        if (!isset($parameters[0]) || $parameters[0]->getClass() == null ||
            ($parameters[0]->getClass()->getName() != RequestMessageInterface::class &&
                !in_array(RequestMessageInterface::class, $parameters[0]->getClass()->getInterfaceNames())
            )
        ) {
            throw new \LogicException(
                sprintf(
                    'First parameter of the profile association callback must be a type of %s',
                    RequestMessageInterface::class
                )
            );
        }

        $this->profileAssociationCallback = $callback;
        $this->profileAssociationPath = $profileAssociationPath;

        return $this;
    }
}
