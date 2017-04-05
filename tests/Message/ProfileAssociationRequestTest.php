<?php

namespace Test\Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;
use Fei\Service\Connect\Client\Message\ProfileAssociationRequest;
use Fei\Service\Connect\Client\Message\UsernamePasswordMessage;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Stream;

/**
 * Class ProfileAssociationRequestTest
 *
 * @package Test\Fei\Service\Connect\Client\Message
 */
class ProfileAssociationRequestTest extends TestCase
{
    public function testExtractProfileAssociationMessage()
    {
        $message = \json_encode(
            [
                'message' => base64_encode($this->encryptMessage(
                    \json_encode(['username' => 'test', 'password' => 'test'])
                ))
            ]
        );

        $stream = new Stream('php://temp', 'rw');
        $stream->write($message);

        $request = (new ProfileAssociationRequest())
            ->withBody($stream);

        $message = $request->extractProfileAssociationMessage(
            file_get_contents(__DIR__ . '/../../example/keys/sp.pem')
        );

        $this->assertInstanceOf(UsernamePasswordMessage::class, $message);
        $this->assertEquals('test', $message->getusername());
        $this->assertEquals('test', $message->getPassword());
    }

    public function testExtractProfileAssociationMessageWithNoValidPrivateKey()
    {
        $message = \json_encode(
            [
                'message' => base64_encode($this->encryptMessage(
                    \json_encode(['username' => 'test', 'password' => 'test'])
                ))
            ]
        );

        $stream = new Stream('php://temp', 'rw');
        $stream->write($message);

        $request = (new ProfileAssociationRequest())
            ->withBody($stream);

        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('Private key isn\'t valid');
        $this->expectExceptionCode(500);

        $request->extractProfileAssociationMessage('');
    }

    public function testExtractProfileAssociationMessageWithNoMessage()
    {
        $message = \json_encode(['test' => base64_encode(':a')]);

        $stream = new Stream('php://temp', 'rw');
        $stream->write($message);

        $request = (new ProfileAssociationRequest())
            ->withBody($stream);

        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('Message attribute must be provided');
        $this->expectExceptionCode(400);

        $request->extractProfileAssociationMessage(file_get_contents(__DIR__ . '/../../example/keys/sp.pem'));
    }

    public function testExtractProfileAssociationMessageWithBadEncryption()
    {
        $message = \json_encode(['message' => base64_encode(':a')]);

        $stream = new Stream('php://temp', 'rw');
        $stream->write($message);

        $request = (new ProfileAssociationRequest())
            ->withBody($stream);

        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('Bad encrypted message attribute');
        $this->expectExceptionCode(400);

        $request->extractProfileAssociationMessage(file_get_contents(__DIR__ . '/../../example/keys/sp.pem'));
    }

    public function testExtractProfileAssociationMessageWithBadBAse64Encoding()
    {
        $message = \json_encode(['message' => ':a']);

        $stream = new Stream('php://temp', 'rw');
        $stream->write($message);

        $request = (new ProfileAssociationRequest())
            ->withBody($stream);

        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('Message attribute is not a base64 encoded string');
        $this->expectExceptionCode(400);

        $request->extractProfileAssociationMessage(file_get_contents(__DIR__ . '/../../example/keys/sp.pem'));
    }

    public function testExtractProfileAssociationMessageWithBadJson()
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write('{"badjson"}');

        $request = (new ProfileAssociationRequest())
            ->withBody($stream);

        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('json_decode error: Syntax error');
        $this->expectExceptionCode(400);

        $request->extractProfileAssociationMessage(file_get_contents(__DIR__ . '/../../example/keys/sp.pem'));
    }

    public function testExtractProfileAssociationMessageWithBadJsonEncrypted()
    {
        $message = \json_encode(
            [
                'message' => base64_encode($this->encryptMessage('{"badjson"}'))
            ]
        );

        $stream = new Stream('php://temp', 'rw');
        $stream->write($message);

        $request = (new ProfileAssociationRequest())
            ->withBody($stream);

        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('json_decode error: Syntax error');
        $this->expectExceptionCode(400);

        $request->extractProfileAssociationMessage(file_get_contents(__DIR__ . '/../../example/keys/sp.pem'));
    }

    protected function encryptMessage($message)
    {
        openssl_public_encrypt($message, $encrypted, file_get_contents(__DIR__ . '/../../example/keys/sp.crt'));

        return $encrypted;
    }
}
