<?php

namespace Fei\Service\Connect\Client\Config;

class Checker
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * ConfigChecker constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }

    /**
     * Get Config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set Config
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Check if private key file exist
     *
     * @return bool
     */
    public function checkPrivateKeyFile()
    {
        return file_exists($this->getConfig()->getPrivateKeyFilePath());
    }

    /**
     * Check if IdP metadata file exist
     *
     * @return bool
     */
    public function checkIdpMetadataFile()
    {
        return file_exists(
            $this->getConfig()->getSamlMetadataBaseDir() . '/' . $this->getConfig()->getIdpMetadataFile()
        );
    }

    /**
     * Check if SP metadata file exist
     *
     * @return bool
     */
    public function checkSpMetadataFile()
    {
        return file_exists(
            $this->getConfig()->getSamlMetadataBaseDir() . '/' . $this->getConfig()->getSpMetadataFile()
        );
    }
}
