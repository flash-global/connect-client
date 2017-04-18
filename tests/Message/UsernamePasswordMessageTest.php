<?php

namespace Test\Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;
use Fei\Service\Connect\Client\Message\UsernamePasswordMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class UsernamePasswordMessageTest
 *
 * @package Test\Fei\Service\Connect\Client\Message
 */
class UsernamePasswordMessageTest extends TestCase
{
    public function testGetUsername()
    {
        $message = new UsernamePasswordMessage(['username' => 'test', 'password' => 'test']);

        $this->assertEquals('test', $message->getUsername());
    }

    public function testGetPassword()
    {
        $message = new UsernamePasswordMessage(['password' => 'test', 'username' => 'test']);

        $this->assertEquals('test', $message->getPassword());
    }

    public function testConstructorFail()
    {
        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage('Username and password must be provided');
        $this->expectExceptionCode(400);

        new UsernamePasswordMessage('test');
    }
}
