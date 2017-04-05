<?php

namespace Test\Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Message\AbstractProfileAssociationMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class ProfileAssociationMessage
 *
 * @package Test\Fei\Service\Connect\Client\Message
 */
class AbstractProfileAssociationMessageTest extends TestCase
{
    public function testUsernameAccessors()
    {
        $message = new class ('test') extends AbstractProfileAssociationMessage {
        };

        $this->assertEquals('test', $message->getMessage());

        $message->setMessage('test2');

        $this->assertEquals('test2', $message->getMessage());
        $this->assertAttributeEquals($message->getMessage(), 'message', $message);
    }
}
