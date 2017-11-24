<?php

namespace Fei\Service\Connect\Client\Config;

/**
 * Class Config
 *
 * @package Fei\Service\Connect\Clientx
 */
class Config
{
    /**
     * @var string
     */
    protected $entityID;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $idpEntityID;

    /**
     * @var string
     */
    protected $samlMetadataBaseDir;

    /**
     * @var string
     */
    protected $spMetadataFile = 'sp.xml';

    /**
     * @var string
     */
    protected $idpMetadataFile = 'idp.xml';

    /**
     * @var string
     */
    protected $idpMetadataFileTarget = '/idp.xml';

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var string
     */
    protected $privateKeyFilePath;

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
    protected $profileAssociationCallback;

    /**
     * @var string
     */
    protected $adminPathInfo =  '/connect/admin';

    /**
     * Get EntityID
     *
     * @return string
     */
    public function getEntityID()
    {
        return $this->entityID;
    }

    /**
     * Set EntityID
     *
     * @param string $entityID
     *
     * @return $this
     */
    public function setEntityID($entityID)
    {
        $this->entityID = $entityID;

        return $this;
    }

    /**
     * Get IdpEntityID
     *
     * @return string
     */
    public function getIdpEntityID()
    {
        return $this->idpEntityID;
    }

    /**
     * Set IdpEntityID
     *
     * @param string $idpEntityID
     *
     * @return $this
     */
    public function setIdpEntityID($idpEntityID)
    {
        $this->idpEntityID = $idpEntityID;

        return $this;
    }

    /**
     * Get SamlMetadataBaseDir
     *
     * @return string
     */
    public function getSamlMetadataBaseDir()
    {
        return $this->samlMetadataBaseDir;
    }

    /**
     * Set SamlMetadataBaseDir
     *
     * @param string $samlMetadataBaseDir
     *
     * @return $this
     */
    public function setSamlMetadataBaseDir($samlMetadataBaseDir)
    {
        $this->samlMetadataBaseDir = $samlMetadataBaseDir;

        return $this;
    }

    /**
     * Get SpMetadataFile
     *
     * @return string
     */
    public function getSpMetadataFile()
    {
        return $this->spMetadataFile;
    }

    /**
     * Set SpMetadataFile
     *
     * @param string $spMetadataFile
     *
     * @return $this
     */
    public function setSpMetadataFile($spMetadataFile)
    {
        $this->spMetadataFile = $spMetadataFile;

        return $this;
    }

    /**
     * Get IdpMetadataFile
     *
     * @return string
     */
    public function getIdpMetadataFile()
    {
        return $this->idpMetadataFile;
    }

    /**
     * Set IdpMetadataFile
     *
     * @param string $idpMetadataFile
     *
     * @return $this
     */
    public function setIdpMetadataFile($idpMetadataFile)
    {
        $this->idpMetadataFile = $idpMetadataFile;

        return $this;
    }

    /**
     * Get PrivateKey
     *
     * @return string
     */
    public function getPrivateKey()
    {
        if (!is_null($this->privateKey)) {
            return $this->privateKey;
        }

        return $this->privateKey = file_get_contents($this->getPrivateKeyFilePath());
    }

    /**
     * Set PrivateKey
     *
     * @param string $privateKey
     *
     * @return $this
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get PrivateKeyFile
     *
     * @return string
     */
    public function getPrivateKeyFilePath()
    {
        return $this->privateKeyFilePath;
    }

    /**
     * Set PrivateKeyFile
     *
     * @param string $privateKeyFilePath
     *
     * @return $this
     */
    public function setPrivateKeyFilePath($privateKeyFilePath)
    {
        $this->privateKeyFilePath = $privateKeyFilePath;

        return $this;
    }

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
        $this->profileAssociationCallback = $callback;
        $this->profileAssociationPath = $profileAssociationPath;

        return $this;
    }

    /**
     * Get PingPathInfo
     *
     * @return string
     */
    public function getAdminPathInfo()
    {
        return $this->adminPathInfo;
    }

    /**
     * Set PingPathInfo
     *
     * @param string $adminPathInfo
     *
     * @return $this
     */
    public function setAdminPathInfo($adminPathInfo)
    {
        $this->adminPathInfo = $adminPathInfo;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdpMetadataFileTarget()
    {
        return $this->idpMetadataFileTarget;
    }

    /**
     * @param string $idpMetadataFileTarget
     * @return Config
     */
    public function setIdpMetadataFileTarget($idpMetadataFileTarget)
    {
        $this->idpMetadataFileTarget = $idpMetadataFileTarget;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Config
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }


}
