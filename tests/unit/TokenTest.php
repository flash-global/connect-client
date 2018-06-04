<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\ResponseDescriptor;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Exception\TokenException;
use Fei\Service\Connect\Client\Metadata;
use Fei\Service\Connect\Client\Saml;
use Fei\Service\Connect\Client\Token;
use Fei\Service\Connect\Common\Cryptography\RsaKeyGen;
use Fei\Service\Connect\Common\Entity\Application;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Fei\Service\Connect\Common\Token\TokenRequest;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use LightSaml\Model\Metadata\EntityDescriptor;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\DateInterval;

/**
 * Class TokenTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class TokenTest extends TestCase
{
    public function testAccessors()
    {
        $this->testOneAccessors('cache', new CacheTest());
        $this->testOneAccessors('tokenizer', new Tokenizer());
    }

    public function testHasCache()
    {
        $token = new Token();
        $cache = $token->hasCache();
        $this->assertEquals($cache, false);
    }

    public function testTokenizer()
    {
        $token = new Token();
        $tokenizer = $token->getTokenizer();
        $this->assertInstanceOf(Tokenizer::class, $tokenizer);
    }


    public function providerValidateHasCache()
    {
        return [
            ['{"expire_at":"2060-12-12"}', false],
            ['{"expire_at":"2015-12-12"}', true]
        ];
    }

    /**
     * @dataProvider providerValidateHasCache
     */
    public function testValidateHasCache($cacheValue, $exception)
    {
        if ($exception) {
            $this->expectException(TokenException::class);
        }
        $cache = $this->getMockBuilder(CacheTest::class)
            ->setMethods(['get'])
            ->getMock();
        $cache->expects($this->once())->method('get')
            ->willReturn($cacheValue);

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['buildValidationReturn'])
            ->getMock();
        $token->setCache($cache);

        $token->validate('AAA');
    }

    public function testValidate()
    {
        $body = json_encode([
            'expire_at' => '2017-12-12',
            'user'      => [],
            'application' => []
        ]);

        $cache = $this->getMockBuilder(CacheTest::class)
            ->setMethods(['get'])
            ->getMock();
        $cache->expects($this->once())->method('get')
            ->willReturn(null);

        $descriptor = $this->getMockBuilder(ResponseDescriptor::class)
            ->setMethods(['getBody'])
            ->getMock();
        $descriptor->expects($this->once())->method('getBody')
            ->willReturn($body);

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willReturn($descriptor);

        $token->setCache($cache);

        $token->validate('AAA');
    }

    public function testValidateException()
    {
        $this->expectException(TokenException::class);

        $body = json_encode([
            'expire_at' => '2017-12-12',
            'user'      => [],
            'application' => []
        ]);

        $cache = $this->getMockBuilder(CacheTest::class)
            ->setMethods(['get'])
            ->getMock();
        $cache->expects($this->once())->method('get')
            ->willReturn(null);

        $descriptor = $this->getMockBuilder(ResponseDescriptor::class)
            ->setMethods(['getBody'])
            ->getMock();
        $descriptor->expects($this->once())->method('getBody')
            ->willReturn($body);

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send', 'buildValidationReturn'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willReturn($descriptor);
        $token->expects($this->once())->method('buildValidationReturn')
            ->willThrowException(new ApiClientException());

        $token->setCache($cache);

        $token->validate('AAA');
    }

    public function testValidateWithBadResponseException()
    {
        $body = json_encode([
            'expire_at' => '2017-12-12',
            'user'      => [],
            'application' => []
        ]);

        $cache = $this->getMockBuilder(CacheTest::class)
            ->setMethods(['get'])
            ->getMock();
        $cache->expects($this->once())->method('get')
            ->willReturn(null);

        $descriptor = $this->getMockBuilder(ResponseDescriptor::class)
            ->setMethods(['getBody'])
            ->getMock();
        $descriptor->expects($this->once())->method('getBody')
            ->willReturn($body);

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send', 'buildValidationReturn'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willReturn($descriptor);
        $token->expects($this->once())->method('buildValidationReturn')
            ->willThrowException(
                new ApiClientException(
                    '',
                    0,
                    new BadResponseException(
                        'BadResponseException',
                        new Request('GET', 'test'),
                        new Response(500)
                    )
                )
            );

        $token->setCache($cache);

        $this->expectException(TokenException::class);
        $token->validate('AAA');
    }

    public function testCreate()
    {
        $metadata = $this->getMockBuilder(Metadata::class)
            ->setMethods(['getServiceProvider'])
            ->getMock();
        $metadata->expects($this->once())->method('getServiceProvider')
            ->willReturn((new EntityDescriptor()));

        $saml = $this->getMockBuilder(Saml::class)
            ->setMethods(['getMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
        $saml->expects($this->once())->method('getMetadata')
            ->willReturn($metadata);

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser'
            ])
            ->getMock();
        $connect->method('getSaml')->willReturn($saml);
        $connect->method('getUser')->willReturn(new User());

        $body = '{}';
        $tokenizer = $this->getMockBuilder(Tokenizer::class)
            ->setMethods(['signTokenRequest'])
            ->getMock();
        $tokenizer->expects($this->once())->method('signTokenRequest')
            ->willReturn(new TokenRequest());

        $descriptor = $this->getMockBuilder(ResponseDescriptor::class)
            ->setMethods(['getBody'])
            ->getMock();
        $descriptor->expects($this->once())->method('getBody')
            ->willReturn($body);

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willReturn($descriptor);

        $token->setTokenizer($tokenizer);
        $token->create($connect);
    }

    public function testCreateWithException()
    {
        $metadata = $this->getMockBuilder(Metadata::class)
            ->setMethods(['getServiceProvider'])
            ->getMock();
        $metadata->expects($this->once())->method('getServiceProvider')
            ->willReturn((new EntityDescriptor()));

        $saml = $this->getMockBuilder(Saml::class)
            ->setMethods(['getMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
        $saml->expects($this->once())->method('getMetadata')
            ->willReturn($metadata);

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser'
            ])
            ->getMock();
        $connect->method('getSaml')->willReturn($saml);
        $connect->method('getUser')->willReturn(new User());

        $tokenizer = $this->getMockBuilder(Tokenizer::class)
            ->setMethods(['signTokenRequest'])
            ->getMock();
        $tokenizer->expects($this->once())->method('signTokenRequest')
            ->willReturn(new TokenRequest());

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willThrowException(new \Exception('', 0));

        $token->setTokenizer($tokenizer);
        $this->expectException(TokenException::class);
        $token->create($connect);
    }


    public function testCreateWithBadResponseException()
    {
        $metadata = $this->getMockBuilder(Metadata::class)
            ->setMethods(['getServiceProvider'])
            ->getMock();
        $metadata->expects($this->once())->method('getServiceProvider')
            ->willReturn((new EntityDescriptor()));

        $saml = $this->getMockBuilder(Saml::class)
            ->setMethods(['getMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
        $saml->expects($this->once())->method('getMetadata')
            ->willReturn($metadata);

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser'
            ])
            ->getMock();
        $connect->method('getSaml')->willReturn($saml);
        $connect->method('getUser')->willReturn(new User());

        $tokenizer = $this->getMockBuilder(Tokenizer::class)
            ->setMethods(['signTokenRequest'])
            ->getMock();
        $tokenizer->expects($this->once())->method('signTokenRequest')
            ->willReturn(new TokenRequest());

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willThrowException(
                new \Exception(
                    '',
                    0,
                    new BadResponseException(
                        'BadResponseException',
                        new Request('GET', 'test'),
                        new Response(500)
                    )
                )
            );

        $token->setTokenizer($tokenizer);
        $this->expectException(TokenException::class);
        $token->create($connect);
    }

    public function testCreateApplicationToken()
    {
        $body = '{}';
        $application = new Application();
        $privateKey  = (new RsaKeyGen())->createPrivateKey();

        $tokenizer = $this->getMockBuilder(Tokenizer::class)
            ->setMethods(['signTokenRequest', 'createApplicationTokenRequest'])
            ->getMock();
        $tokenizer->expects($this->once())->method('createApplicationTokenRequest')
            ->willReturn(new TokenRequest());
        $tokenizer->expects($this->once())->method('signTokenRequest')
            ->willReturn(new TokenRequest());

        $descriptor = $this->getMockBuilder(ResponseDescriptor::class)
            ->setMethods(['getBody'])
            ->getMock();
        $descriptor->expects($this->once())->method('getBody')
            ->willReturn($body);

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willReturn($descriptor);

        $token->setTokenizer($tokenizer);
        $token->createApplicationToken($application, $privateKey);
    }

    public function testCreateApplicationTokenWithException()
    {
        $application = new Application();
        $privateKey  = (new RsaKeyGen())->createPrivateKey();

        $tokenizer = $this->getMockBuilder(Tokenizer::class)
            ->setMethods(['signTokenRequest', 'createApplicationTokenRequest'])
            ->getMock();
        $tokenizer->expects($this->once())->method('createApplicationTokenRequest')
            ->willReturn(new TokenRequest());
        $tokenizer->expects($this->once())->method('signTokenRequest')
            ->willReturn(new TokenRequest());

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willThrowException(new \Exception('', 0));

        $token->setTokenizer($tokenizer);
        $this->expectException(TokenException::class);
        $token->createApplicationToken($application, $privateKey);
    }

    public function testCreateApplicationTokenWithBadResponseException()
    {
        $application = new Application();
        $privateKey  = (new RsaKeyGen())->createPrivateKey();

        $tokenizer = $this->getMockBuilder(Tokenizer::class)
            ->setMethods(['signTokenRequest', 'createApplicationTokenRequest'])
            ->getMock();
        $tokenizer->expects($this->once())->method('createApplicationTokenRequest')
            ->willReturn(new TokenRequest());
        $tokenizer->expects($this->once())->method('signTokenRequest')
            ->willReturn(new TokenRequest());

        $token = $this->getMockBuilder(Token::class)
            ->setMethods(['send'])
            ->getMock();
        $token->expects($this->once())->method('send')
            ->willThrowException(
                new \Exception(
                    '',
                    0,
                    new BadResponseException(
                        'BadResponseException',
                        new Request('GET', 'test'),
                        new Response(500)
                    )
                )
            );

        $token->setTokenizer($tokenizer);
        $this->expectException(TokenException::class);
        $token->createApplicationToken($application, $privateKey);
    }

    protected function testOneAccessors($name, $expected)
    {
        $setter = 'set' . ucfirst($name);
        $getter = 'get' . ucfirst($name);
        $class = new Token();
        $class->$setter($expected);
        $this->assertEquals($class->$getter(), $expected);
        $this->assertAttributeEquals($class->$getter(), $name, $class);
    }
}


class CacheTest implements CacheInterface
{
    public function get($key, $default = null)
    {
        // TODO: Implement get() method.
    }

    public function set($key, $value, $ttl = null)
    {
        // TODO: Implement set() method.
    }

    public function delete($key)
    {
        // TODO: Implement delete() method.
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }

    public function getMultiple($keys, $default = null)
    {
        // TODO: Implement getMultiple() method.
    }

    public function setMultiple($values, $ttl = null)
    {
        // TODO: Implement setMultiple() method.
    }

    public function deleteMultiple($keys)
    {
        // TODO: Implement deleteMultiple() method.
    }

    public function has($key)
    {
        // TODO: Implement has() method.
    }

}
