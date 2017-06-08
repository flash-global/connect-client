<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Config;
use Fei\Service\Connect\Common\ProfileAssociation\Message\RequestMessageInterface;
use Fei\Service\Connect\Common\ProfileAssociation\Message\UsernamePasswordMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class ConfigTest extends TestCase
{
    public function testDefaultTargetPathAccessors()
    {
        $config = new Config();

        $config->setDefaultTargetPath('test');

        $this->assertEquals('test', $config->getDefaultTargetPath());
        $this->assertAttributeEquals($config->getDefaultTargetPath(), 'defaultTargetPath', $config);
    }

    public function testLogoutTargetPathAccessors()
    {
        $config = new Config();

        $config->setLogoutTargetPath('test');

        $this->assertEquals('test', $config->getLogoutTargetPath());
        $this->assertAttributeEquals($config->getLogoutTargetPath(), 'logoutTargetPath', $config);
    }

    public function testProfileAssociationPathAccessors()
    {
        $config = new Config();

        $this->assertEquals('/connect/profile-association', $config->getProfileAssociationPath());

        $config->registerProfileAssociation(
            function (RequestMessageInterface $message) {
            },
            'test'
        );

        $this->assertEquals('test', $config->getProfileAssociationPath());
        $this->assertAttributeEquals($config->getProfileAssociationPath(), 'profileAssociationPath', $config);
    }

    public function testProfileAssociationCallbackAccessors()
    {
        $config = new Config();

        $this->assertEquals(null, $config->getProfileAssociationCallback());

        $callback = function (RequestMessageInterface $message) {
        };

        $config->registerProfileAssociation($callback);

        $this->assertEquals($callback, $config->getProfileAssociationCallback());
        $this->assertAttributeEquals(
            $config->getProfileAssociationCallback(),
            'profileAssociationCallback',
            $config
        );
    }

    public function testHasProfileAssociationCallback()
    {
        $config = new Config();

        $this->assertFalse($config->hasProfileAssociationCallback());

        $callback = function (UsernamePasswordMessage $message) {
        };

        $config->registerProfileAssociation($callback);

        $this->assertTrue($config->hasProfileAssociationCallback());
    }

    public function testProfileAssociationCallbackAccessorsWithBadParameterMethod()
    {
        $config = new Config();

        $callback = function ($message) {
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'First parameter of the profile association callback must be a type of %s',
                RequestMessageInterface::class
            )
        );

        $config->registerProfileAssociation($callback);
    }
}
