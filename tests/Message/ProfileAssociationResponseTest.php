<?php

namespace Test\Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;
use Fei\Service\Connect\Client\Message\ProfileAssociationResponse;
use PHPUnit\Framework\TestCase;

/**
 * Class ProfileAssociationResponseTest
 *
 * @package Test\Fei\Service\Connect\Client\Message
 */
class ProfileAssociationResponseTest extends TestCase
{
    public function testMessageAccessors()
    {
        $response = new ProfileAssociationResponse('test');

        $this->assertEquals('test', $response->getMessage());

        $response->setMessage('toto');

        $this->assertEquals('toto', $response->getMessage());
        $this->assertAttributeEquals($response->getMessage(), 'message', $response);
    }

    public function testEncryptMessage()
    {
        $response = new ProfileAssociationResponse('test');

        $encrypted = $response->encryptMessage(file_get_contents(__DIR__ . '/../../example/keys/sp.crt'));

        openssl_private_decrypt($encrypted, $decrypted, file_get_contents(__DIR__ . '/../../example/keys/sp.pem'));

        $this->assertEquals('test', $decrypted);
    }

    public function testEncryptMessageWithBadKey()
    {
        $response = new ProfileAssociationResponse('test');

        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('Certificate isn\'t valid');
        $this->expectExceptionCode(500);

        $response->encryptMessage('');
    }

    public function testBuildMessageResponse()
    {
        $response = new ProfileAssociationResponse('test');

        $response = $response->buildMessageResponse(file_get_contents(__DIR__ . '/../../example/keys/sp.crt'));

        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0]);

        $response->getBody()->rewind();

        $message = \json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('message', $message);

        openssl_private_decrypt(
            base64_decode($message['message']),
            $decrypted,
            file_get_contents(__DIR__ . '/../../example/keys/sp.pem')
        );

        $this->assertEquals('test', $decrypted);
    }
}
