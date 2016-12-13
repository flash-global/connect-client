<?php

namespace Fei\Service\Connect\Client;

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
}
