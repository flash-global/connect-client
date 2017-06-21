<?php

namespace Test\Fei\Service\Connect\Client;

use FastRoute\Dispatcher;
use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\ResponseDescriptor;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\Config;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Metadata;
use Fei\Service\Connect\Client\Saml;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Guzzle\Http\Exception\BadResponseException;
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

    public function testValidateToken()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $saml->expects($this->any())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->any())->method('getLogoutLocation')->willReturn('/logout');

        $token = (new Tokenizer())->createToken(
            new User(),
            'test',
            file_get_contents(__DIR__ . '/../example/keys/sp.pem')
        );

        $transport->expects($this->once())->method('send')->willReturn(
            (new ResponseDescriptor())
                ->setBody(
                    json_encode([
                        'token' => (string) $token
                    ])
                )
        );

        $connect = new Connect($saml, $config);
        $connect->setTransport($transport);

        $result = $connect->validateToken((string) $token);

        $this->assertEquals($result, $token);
    }

    public function testValidateTokenFailWithBadResponse()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $saml->expects($this->any())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->any())->method('getLogoutLocation')->willReturn('/logout');

        $token = (new Tokenizer())->createToken(
            new User(),
            'test',
            file_get_contents(__DIR__ . '/../example/keys/sp.pem')
        );

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0, new BadResponseException('test'))
        );

        $connect = new Connect($saml, $config);
        $connect->setTransport($transport);

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('test');

        $connect->validateToken((string) $token);
    }

    public function testValidateTokenFailWithApiException()
    {
        $saml = $this->getMockBuilder(Saml::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->getMock();
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $saml->expects($this->any())->method('getAcsLocation')->willReturn('/acs');
        $saml->expects($this->any())->method('getLogoutLocation')->willReturn('/logout');

        $token = (new Tokenizer())->createToken(
            new User(),
            'test',
            file_get_contents(__DIR__ . '/../example/keys/sp.pem')
        );

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0, new ApiClientException('test'))
        );

        $connect = new Connect($saml, $config);
        $connect->setTransport($transport);

        $this->expectException(ApiClientException::class);
        $this->expectExceptionMessage('test');

        $connect->validateToken((string) $token);
    }
}
