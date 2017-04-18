<?php

namespace Test\Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Exception\ProfileAssociationException;
use Fei\Service\Connect\Client\Message\ProfileAssociationMessageFactory;
use Fei\Service\Connect\Client\Message\UsernamePasswordMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class ProfileAssociationMessageFactoryTest
 *
 * @package Test\Fei\Service\Connect\Client\Message
 */
class ProfileAssociationMessageFactoryTest extends TestCase
{
    public function testGetInstanceWorking()
    {
        $instance = ProfileAssociationMessageFactory::getInstance(['username' => 'test', 'password' => 'test']);

        $this->assertInstanceOf(UsernamePasswordMessage::class, $instance);
    }

    public function testGetInstanceFail()
    {
        $this->expectException(ProfileAssociationException::class);
        $this->expectExceptionMessage(
            'Unable to create a instance of Fei\Service\Connect\Client\Message\ProfileAssociationMessageInterface' .
            ' with message provided'
        );
        $this->expectExceptionCode(500);

        ProfileAssociationMessageFactory::getInstance('test');
    }
}
