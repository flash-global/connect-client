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
use Fei\Service\Connect\Common\Entity\Application;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

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

        $this->assertEquals(['token' => 'token'], $result);
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

        $now = new \DateTime();

        $transport->expects($this->once())->method('send')->willReturn(
            (new ResponseDescriptor())
                ->setBody(
                    json_encode(
                        [
                            'expire_at' => $now->format(\DateTime::ISO8601),
                            'application' => (new Application())->toArray()
                        ]

                    )
                )
        );

        $validator = new Token();
        $validator->setTransport($transport);

        $result = $validator->validate('token');

        $this->assertEquals([
            'expire_at' => $now,
            'application' => new Application()
        ], $result);
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

    public function testCacheAccessors()
    {
        $token = new Token();

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();

        $token->setCache($cache);

        $this->assertEquals($cache, $token->getCache());
        $this->assertAttributeEquals($token->getCache(), 'cache', $token);
    }

    public function testValidationHitCache()
    {
        $token = new Token();

        $expire = new \DateTime('+ 1 min');

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method('get')->with('token')->willReturn(json_encode([
            'expire_at' => $expire->format(\DateTime::ISO8601),
            'application' => (new Application())->toArray()
        ]));

        $token->setCache($cache);

        $this->assertEquals(
            [
                'expire_at' => $expire,
                'application' => new Application()
            ],
            $token->validate('token')
        );
    }

    public function testValidationHitCacheWithExpiredToken()
    {
        $token = new Token();

        $expire = new \DateTime('- 1 min');

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method('get')->with('token')->willReturn(json_encode([
            'expire_at' => $expire->format(\DateTime::ISO8601),
            'application' => (new Application())->toArray()
        ]));
        $cache->expects($this->once())->method('delete')->with('token')->willReturn(true);

        $token->setCache($cache);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('The provided token is expired');

        $token->validate('token');
    }

    public function testValidationDoesntHitCache()
    {
        $token = new Token();

        $expire = new \DateTime('+ 1 min');

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method('get')->with('token')->willReturn(null);
        $cache->expects($this->once())->method('set')->with(
            'token',
            json_encode(
                [
                    'expire_at' => $expire->format(\DateTime::ISO8601),
                    'application' => (new Application())->toArray(),
                    'user' => (new User())->toArray()
                ]

            )
        )->willReturn(true);

        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $transport->expects($this->once())->method('send')->willReturn(
            (new ResponseDescriptor())
                ->setBody(
                    json_encode(
                        [
                            'expire_at' => $expire->format(\DateTime::ISO8601),
                            'application' => (new Application())->toArray(),
                            'user' => (new User())->toArray()
                        ]

                    )
                )
        );

        $token->setCache($cache);
        $token->setTransport($transport);

        $this->assertEquals([
            'expire_at' => $expire,
            'application' => new Application(),
            'user' => new User()
        ], $token->validate('token'));
    }
}
