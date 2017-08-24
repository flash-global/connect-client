<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\ResponseDescriptor;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\Config;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Exception\TokenException;
use Fei\Service\Connect\Client\Metadata;
use Fei\Service\Connect\Client\Saml;
use Fei\Service\Connect\Client\Token;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
use PHPUnit\Framework\TestCase;

/**
 * Class TokenValidatorTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class TokenTest extends TestCase
{
    public function testTokenizerAccessors()
    {
        $token = new Token();

        $tokenizer = new Tokenizer();

        $token->setTokenizer($tokenizer);

        $this->assertEquals($tokenizer, $token->getTokenizer());
        $this->assertAttributeEquals($token->getTokenizer(), 'tokenizer', $token);
    }

    public function testCreateToken()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $transport->expects($this->once())->method('send')->willReturn(
            (new ResponseDescriptor())
                ->setBody(
                    json_encode([
                        'token' => 'token'
                    ])
                )
        );

        $token = new Token();
        $token->setTransport($transport);

        $metadata = new Metadata();
        $metadata->setServiceProvider(
            (new SpSsoDescriptor())->setID('http://test')
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
        );

        $connect = new Connect(new Saml($metadata), new Config());
        $connect->setUser(new User());

        $result = $token->create($connect);

        $this->assertEquals('token', $result);
    }

    public function testCreateTokenFailWithBadResponse()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $response = new Response('Internal Server error');
        $response->setBody(json_encode(['error' => 'error', 'code' => 500]));

        $exception = new BadResponseException('test');
        $exception->setResponse($response);

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0, $exception)
        );

        $token = new Token();
        $token->setTransport($transport);

        $metadata = new Metadata();
        $metadata->setServiceProvider(
            (new SpSsoDescriptor())->setID('http://test')
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
        );

        $connect = new Connect(new Saml($metadata), new Config());
        $connect->setUser(new User());

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('error');

        $token->create($connect);
    }

    public function testCreateTokenFailWithApiException()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0)
        );

        $token = new Token();
        $token->setTransport($transport);

        $metadata = new Metadata();
        $metadata->setServiceProvider(
            (new SpSsoDescriptor())->setID('http://test')
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
        );

        $connect = new Connect(new Saml($metadata), new Config());
        $connect->setUser(new User());

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('test');

        $token->create($connect);
    }

    public function testValidateToken()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $transport->expects($this->once())->method('send')->willReturn(
            (new ResponseDescriptor())
                ->setBody(
                    json_encode(
                        (new User())
                            ->setUserName('test')
                            ->toArray()
                    )
                )
        );

        $validator = new Token();
        $validator->setTransport($transport);

        $result = $validator->validate('token');

        $this->assertEquals((new User())->setUserName('test'), $result);
    }

    public function testValidateTokenFailWithBadResponse()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $response = new Response('Internal Server error');
        $response->setBody(json_encode(['error' => 'error', 'code' => 500]));

        $exception = new BadResponseException('test');
        $exception->setResponse($response);

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0, $exception)
        );

        $validator = new Token();
        $validator->setTransport($transport);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('error');

        $validator->validate('token');
    }

    public function testValidateTokenFailWithApiException()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0, new ApiClientException('test'))
        );

        $validator = new Token();
        $validator->setTransport($transport);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('test');

        $validator->validate('token');
    }

    public function testApplicationIdAccessors()
    {
        $token = new Token();
        $token->setApplicationId('fake-app-id');

        $this->assertEquals($token->getApplicationId(), 'fake-app-id');
        $this->assertAttributeEquals($token->getApplicationId(), 'applicationId', $token);
    }

    public function testPrivateKeyAccessors()
    {
        $token = new Token();
        $token->setPrivateKey('fake-private-key');

        $this->assertEquals($token->getPrivateKey(), 'fake-private-key');
        $this->assertAttributeEquals($token->getPrivateKey(), 'privateKey', $token);
    }
}
