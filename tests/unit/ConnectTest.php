<?php

namespace Test\Fei\Service\Connect\Client;

use Doctrine\Common\Collections\ArrayCollection;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Exception\UserAttributionException;
use Fei\Service\Connect\Common\Entity\Application;
use Fei\Service\Connect\Common\Entity\Attribution;
use Fei\Service\Connect\Common\Entity\Role;
use Fei\Service\Connect\Common\Entity\User;
use PHPUnit\Framework\TestCase;
use Fei\Service\Connect\Client\Config\Config;

/**
 * Class ConnectTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class ConnectTest extends TestCase
{
    public function testIsConfigConsistentAccessors()
    {
        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser'
            ])
            ->getMock();

        $setter = 'setIsConfigConsistent';
        $getter = 'isConfigConsistent';
        $connect->$setter(true);
        $this->assertEquals($connect->$getter(), true);
        $this->assertAttributeEquals($connect->$getter(), 'isConfigConsistent', $connect);
    }

    public function testAccessors()
    {
        $this->testOneAccessors('role', 'ADMIN');
        $this->testOneAccessors('localUsername', 'toto');
    }

    protected function testOneAccessors($name, $expected)
    {
        $class = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getSource'
            ])
            ->getMock();

        $setter = 'set' . ucfirst($name);
        $getter = 'get' . ucfirst($name);
        $class->$setter($expected);
        $this->assertEquals($class->$getter(), $expected);
        $this->assertAttributeEquals($class->$getter(), $name, $class);
    }
}
