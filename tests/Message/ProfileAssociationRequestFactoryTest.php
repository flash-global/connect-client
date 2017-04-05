<?php

namespace Test\Fei\Service\Connect\Client\Message;

use Fei\Service\Connect\Client\Message\ProfileAssociationRequest;
use Fei\Service\Connect\Client\Message\ProfileAssociationRequestFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class ProfileAssociationRequestTest
 *
 * @package Test\Fei\Service\Connect\Client\Message
 */
class ProfileAssociationRequestFactoryTest extends TestCase
{
    public function testFromGlobal()
    {
        $this->assertInstanceOf(ProfileAssociationRequest::class, ProfileAssociationRequestFactory::fromGlobals());
    }
}
