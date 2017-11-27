<?php

namespace Fei\Service\Connect\Client\Config;

use Fei\Service\Connect\Client\Metadata;
use Fei\Service\Connect\Client\MetadataProvider;
use Fei\Service\Connect\Common\Admin\Message\SpEntityConfigurationMessage;
use Fei\Service\Connect\Common\Cryptography\RsaKeyGen;

/**
 * Class ConfigConsistency
 *
 * @package Fei\Service\Connect\Client\Config
 */
class ConfigConsistency
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Checker
     */
    protected $checker;

    /**
     * ConfigConsistency constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
        $this->setChecker(new Checker($config));
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
     * Get Checker
     *
     * @return Checker
     */
    public function getChecker()
    {
        return $this->checker;
    }

    /**
     * Set Checker
     *
     * @param Checker $checker
     *
     * @return $this
     */
    public function setChecker($checker)
    {
        $this->checker = $checker;

        return $this;
    }

    /**
     * Create a directory
     *
     * @param string $path
     *
     * @throws \Exception
     */
    protected function createDir($path)
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        if (!is_dir($dir)) {
            if (@mkdir($dir, 0755, true) === false) {
                throw new \Exception('Error in dir creation');
            }
        }
    }

    /**
     * Create and write the RSA private file
     *
     * @throws \Exception
     */
    public function createRSAPrivateKey()
    {
        $path = $this->getConfig()->getPrivateKeyFilePath();
        $this->createDir($path);
        $this->getConfig()->setPrivateKey((new RsaKeyGen())->createPrivateKey());

        if (@file_put_contents($path, $this->getConfig()->getPrivateKey()) === false) {
            throw new \Exception('Error when writing the RSA private key');
        }
    }

    /**
     * Create and write the IdP metadata XML file
     *
     * @throws \Exception
     */
    protected function createIdpMetadataFile()
    {
        $url = $this->getConfig()->getIdpEntityID() . $this->getConfig()->getIdpMetadataFileTarget();

        try {
            $idp = file_get_contents($url);
            $path = $this->getConfig()->getSamlMetadataBaseDir() . '/' . $this->getConfig()->getIdpMetadataFile();
            $this->createDir($path);

            if (@file_put_contents($path, $idp) === false) {
                throw new \Exception(sprintf('Unable to write IdP Metadata on %s', $path));
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Unable to fetch IdP Metadata on %s', $url));
        }
    }

    /**
     * Create and write the SP metadata XML file
     *
     * @throws \Exception
     */
    protected function createSpMetadataFile()
    {
        $url = $this->getConfig()->getIdpEntityID();

        $entityID = $this->getConfig()->getEntityID();
        $metadataProvider = new MetadataProvider([MetadataProvider::OPTION_BASEURL => $url]);

        try {
            $message = $metadataProvider->getSPEntityConfiguration($entityID);
        } catch (\Exception $e) {
            $message = new SpEntityConfigurationMessage();
        }

        if (empty($message->getXml())) {
            if (!$message->getId()) {
                if (!$this->validateIdpMetadata()) {
                    throw new \Exception('Unable to send registration request due to not found IdP metadata file');
                }

                $idp = (new Metadata())->createEntityDescriptor(
                    file_get_contents(
                        $this->getConfig()->getSamlMetadataBaseDir() . '/' . $this->getConfig()->getIdpMetadataFile()
                    )
                )->getFirstIdpSsoDescriptor();

                $metadataProvider->subscribeApplication($this->getConfig(), $idp);
            }

            return false;
        }

        $path = $this->getConfig()->getSamlMetadataBaseDir() . '/' . $this->getConfig()->getSpMetadataFile();
        $this->createDir($path);

        if (@file_put_contents($path, $message->getXml()) === false) {
            throw new \Exception('Error in Sp MetadataFile writing');
        }

        return true;
    }

    /**
     * Validate the private key configuration
     *
     */
    public function validatePrivateKey()
    {
        if (!$this->getChecker()->checkPrivateKeyFile()) {
            $this->createRSAPrivateKey();
        }

        return true;
    }

    /**
     * Validate the IdP metadata configuration
     *
     */
    public function validateIdpMetadata()
    {
        if (!$this->getChecker()->checkIdpMetadataFile()) {
            $this->createIdpMetadataFile();
        }

        return true;
    }

    /**
     * Validate the SP metadata configuration
     *
     */
    public function validateSpMetadata()
    {
        if (!$this->getChecker()->checkSpMetadataFile()) {
            return $this->createSpMetadataFile();
        }

        return true;
    }

    /**
     * Validate the configuration
     *
     * @return bool
     */
    public function validate()
    {
        $steps = [
            'PrivateKey',
            'IdpMetadata',
            'SpMetadata'
        ];

        foreach ($steps as $step) {
            $method = 'validate' . $step;
            if (!$this->$method()) {
                return false;
            }
        }

        return true;
    }
}
