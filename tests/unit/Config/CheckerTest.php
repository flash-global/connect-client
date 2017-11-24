<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Config\Checker;
use Fei\Service\Connect\Client\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Class CheckerTest
 * @package Test\Fei\Service\Connect\Client
 */
class CheckerTest extends TestCase
{
    public function testConfig()
    {
        $config  = $this->getConfig();
        $checker = new Checker($config);

        $this->assertEquals($config, $checker->getConfig());
    }

    public function testCheckPrivateKeyFile()
    {
        $checker = new Checker($this->getConfig());
        $this->assertEquals($checker->checkPrivateKeyFile(), false);
    }

    public function testCheckIdpMetadataFile()
    {
        $checker = new Checker($this->getConfig());
        $this->assertEquals($checker->checkIdpMetadataFile(), false);
    }

    public function testCheckSpMetadataFile()
    {
        $checker = new Checker($this->getConfig());
        $this->assertEquals($checker->checkSpMetadataFile(), false);
    }

    protected function getConfig()
    {
        $config = (new Config())
            ->setEntityID('http://client.dev:8084')
            ->setIdpEntityID('http://idp.dev:8080')
            ->setSamlMetadataBaseDir(__DIR__ . '/test/metadata')
            ->setPrivateKeyFilePath(__DIR__ . '/test/keys/sp.pem')
            ->setDefaultTargetPath('/resource.php')
            ->setLogoutTargetPath('/')
            ->setAdminPathInfo('/admin.php');

        return $config;
    }
}