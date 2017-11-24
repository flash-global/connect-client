<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Config\Config;
use Fei\Service\Connect\Common\Message\Exception\MessageException;
use Fei\Service\Connect\Common\ProfileAssociation\Message\ResponseMessage;
use Fei\Service\Connect\Common\ProfileAssociation\Message\UsernamePasswordMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class ConfigTest extends TestCase
{
    public function testConfig()
    {
        $config = $this->getConfig();

        $this->assertEquals($config->getEntityID(), 'http://client.dev:8084');
        $this->assertEquals($config->getIdpEntityID(), 'http://idp.dev:8080');
        $this->assertEquals($config->getSamlMetadataBaseDir(), __DIR__ . '/test/metadata');
        $this->assertEquals($config->getPrivateKeyFilePath(), __DIR__ . '/test/keys/sp.pem');
        $this->assertEquals($config->getDefaultTargetPath(), '/resource.php');
        $this->assertEquals($config->getLogoutTargetPath(), '/');
        $this->assertEquals($config->getAdminPathInfo(), '/admin.php');
        $this->assertEquals($config->getSpMetadataFile(), '/');
        $this->assertEquals($config->getIdpMetadataFile(), '/');
        $this->assertEquals($config->getIdpMetadataFileTarget(), '/');
        $this->assertEquals($config->getName(), 'NAME');
        $this->assertEquals($config->getPrivateKey(), 'ezfeergr');
        $this->assertEquals($config->getProfileAssociationPath(), '/profile-association.php');
        $this->assertInternalType('object', $config->getProfileAssociationCallback());
        $this->assertEquals(true, $config->hasProfileAssociationCallback());
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
            ->setSpMetadataFile('/')
            ->setIdpMetadataFile('/')
            ->setIdpMetadataFileTarget('/')
            ->setName('NAME')
            ->setPrivateKey('ezfeergr')
            ->registerProfileAssociation(
                function (UsernamePasswordMessage $message) {
                    if ($message->getUsername() != 'test' || $message->getPassword() != 'test') {
                        throw new MessageException('Profile not found!', 400);
                    }

                    return (new ResponseMessage())->setRole($message->getRoles()[0]);
                },
                '/profile-association.php'
            )
            ->setAdminPathInfo('/admin.php');

        return $config;
    }
}
