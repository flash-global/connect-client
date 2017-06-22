<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\ResponseDescriptor;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\TokenValidator;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Guzzle\Http\Exception\BadResponseException;
use PHPUnit\Framework\TestCase;

/**
 * Class TokenValidatorTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class TokenValidatorTest extends TestCase
{
    public function testValidateToken()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

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

        $validator = new TokenValidator();
        $validator->setTransport($transport);

        $result = $validator->validate((string) $token);

        $this->assertEquals($result, $token);
    }

    public function testValidateTokenFailWithBadResponse()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $token = (new Tokenizer())->createToken(
            new User(),
            'test',
            file_get_contents(__DIR__ . '/../example/keys/sp.pem')
        );

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0, new BadResponseException('test'))
        );

        $validator = new TokenValidator();
        $validator->setTransport($transport);

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('test');

        $validator->validate((string) $token);
    }

    public function testValidateTokenFailWithApiException()
    {
        $transport = $this->getMockBuilder(BasicTransport::class)->getMock();

        $token = (new Tokenizer())->createToken(
            new User(),
            'test',
            file_get_contents(__DIR__ . '/../example/keys/sp.pem')
        );

        $transport->expects($this->once())->method('send')->willThrowException(
            new ApiClientException('test', 0, new ApiClientException('test'))
        );

        $validator = new TokenValidator();
        $validator->setTransport($transport);

        $this->expectException(ApiClientException::class);
        $this->expectExceptionMessage('test');

        $validator->validate((string) $token);
    }
}
