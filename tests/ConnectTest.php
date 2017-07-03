<?php

namespace Test\Fei\Service\Connect\Client;

use FastRoute\Dispatcher;
use Fei\Service\Connect\Client\Config;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Saml;
use Fei\Service\Connect\Common\Entity\User;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;

/**
 * Class ConnectTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class ConnectTest extends TestCase
{
    public function testSamlAccessors()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();

        $saml->expects($this->once())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->once())->method('getLogoutLocation')->willReturn('/logout');

        $connect = new Connect($saml, $config);

        $this->assertEquals($saml, $connect->getSaml());
        $this->assertAttributeEquals($connect->getSaml(), 'saml', $connect);
    }

    public function testConfigAccessors()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();

        $saml->expects($this->once())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->once())->method('getLogoutLocation')->willReturn('/logout');

        $connect = new Connect($saml, $config);

        $this->assertEquals($config, $connect->getConfig());
        $this->assertAttributeEquals($connect->getConfig(), 'config', $connect);
    }

    public function testUserAccessors()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();

        $saml->expects($this->once())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->once())->method('getLogoutLocation')->willReturn('/logout');

        $connect = new Connect($saml, $config);

        $user = (new User())
            ->setCurrentRole('test')
            ->setLocalUsername('localusername');

        $connect->setUser($user);

        $this->assertEquals($user, $connect->getUser());
        $this->assertAttributeEquals($connect->getUser(), 'user', $connect);

        $this->assertEquals($user->getCurrentRole(), $connect->getRole());
        $this->assertEquals($user->getLocalUsername(), $connect->getLocalUsername());
    }

    public function testResponseAccessors()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();

        $saml->expects($this->once())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->once())->method('getLogoutLocation')->willReturn('/logout');

        $connect = new Connect($saml, $config);

        $response = new Response();

        $connect->setResponse($response);

        $this->assertEquals($response, $connect->getResponse());
        $this->assertAttributeEquals($connect->getResponse(), 'response', $connect);
    }

    public function testDispatcherAccessors()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();

        $saml->expects($this->once())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->once())->method('getLogoutLocation')->willReturn('/logout');

        $connect = new Connect($saml, $config);

        $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();

        $connect->setDispatcher($dispatcher);

        $this->assertEquals($dispatcher, $connect->getDispatcher());
        $this->assertAttributeEquals($connect->getDispatcher(), 'dispatcher', $connect);
    }
}
