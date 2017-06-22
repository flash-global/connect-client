<?php

namespace Test\Fei\Service\Connect\Client;

use FastRoute\Dispatcher;
use Fei\Service\Connect\Client\Config;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Metadata;
use Fei\Service\Connect\Client\Saml;
use Fei\Service\Connect\Common\Entity\User;
use Lcobucci\JWT\Token;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
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

    public function testCreateToken()
    {
        $saml = new Saml((new Metadata())->setServiceProvider(
            (new SpSsoDescriptor())
                ->setID('http://translate.dev:8084')
                ->addAssertionConsumerService(
                    new AssertionConsumerService(
                        'http://test/acs.php',
                        SamlConstants::BINDING_SAML2_HTTP_POST
                    )
                )
                ->addSingleLogoutService(
                    new SingleLogoutService(
                        'http://test/logout.php',
                        SamlConstants::BINDING_SAML2_HTTP_POST
                    )
                ),
            file_get_contents(__DIR__ . '/../example/keys/sp.pem')
        ));

        $config = $this->getMockBuilder(Config::class)->getMock();

        $connect = new Connect($saml, $config);

        $user = (new User())
            ->setCurrentRole('test')
            ->setLocalUsername('localusername');

        $connect->setUser($user);

        $token = $connect->createToken();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals(json_encode($user->toArray()), $token->getClaim('user_entity'));
    }

    public function testCreateTokenWhenUserNotSet()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();

        $saml->expects($this->once())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->once())->method('getLogoutLocation')->willReturn('/logout');

        unset($_SESSION['user']);

        $connect = new Connect($saml, $config);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to create token: user is not set');

        $connect->createToken();
    }
}
