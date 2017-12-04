<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Exception\UserAttributionException;
use Fei\Service\Connect\Client\UserAttribution;
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
        $this->testOneAccessors('userAttribution', new UserAttribution());
    }

    public function testSwitchLocalUsernameNotAuthenticated()
    {
        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'isAuthenticated'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(false);

        $this->expectException(UserAttributionException::class);
        $connect->switchLocalUsername('test', 'USER');
    }

    public function testSwitchLocalUsernameNotUser()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods([
                'get'
            ])
            ->getMock();
        $userAttribution->method('get')->willThrowException(new \Exception());

        $config = (new Config())
            ->setEntityID('http://filer.flash-global.eu');

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'getConfig',
                'isAuthenticated',
                'getUserAttribution'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(true);
        $connect->method('getUser')->willReturn(
            (new User())
                ->setUserName('test')
        );
        $connect->method('getConfig')->willReturn($config);
        $connect->method('getUserAttribution')->willReturn($userAttribution);

        $this->expectException(UserAttributionException::class);
        $connect->switchLocalUsername('test', 'USER');
    }

    public function testSwitchLocalUsernameNotRole()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods([
                'get'
            ])
            ->getMock();
        $userAttribution->method('get')->willReturn([
            (new Attribution())
                ->setRole(
                    (new Role())
                        ->setRole('ADMIN')
                )
                ->setApplication(
                    (new Application())
                        ->setUrl('http://filer.flash-global.eu')
                )
                ->setUser(
                    (new User())
                        ->setUserName('test')
                )
                ->toArray(),
        ]);

        $config = (new Config())
            ->setEntityID('http://filer.flash-global.eu');

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'setUser',
                'getConfig',
                'isAuthenticated',
                'getUserAttribution'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(true);
        $connect->method('getUser')->willReturn(
            (new User())
                ->setUserName('test')
        );
        $connect->method('getConfig')->willReturn($config);
        $connect->method('getUserAttribution')->willReturn($userAttribution);
        $connect->method('setUser');

        $this->expectException(UserAttributionException::class);
        $connect->switchLocalUsername('test', 'USER');
    }

    public function testSwitchLocalUsername()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods([
                'get'
            ])
            ->getMock();
        $userAttribution->method('get')->willReturn([
            (new Attribution())
                ->setRole(
                    (new Role())
                        ->setRole('Filer:USER:test')
                )
                ->setApplication(
                    (new Application())
                        ->setName('Filer')
                        ->setUrl('http://filer.flash-global.eu')
                )
                ->setUser(
                    (new User())
                        ->setUserName('test')
                )
                ->toArray(),
            (new Attribution())
                ->setRole(
                    (new Role())
                        ->setRole('Filer:ADMIN:test')
                )
                ->setApplication(
                    (new Application())
                        ->setName('Filer')
                        ->setUrl('http://filer.flash-global.eu')
                )
                ->setUser(
                    (new User())
                        ->setUserName('test')
                )
                ->toArray(),
        ]);

        $config = (new Config())
            ->setEntityID('http://filer.flash-global.eu');

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'setUser',
                'getConfig',
                'isAuthenticated',
                'getUserAttribution'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(true);
        $connect->method('getUser')->willReturn(
            (new User())
                ->setUserName('test')
        );
        $connect->method('getConfig')->willReturn($config);
        $connect->method('getUserAttribution')->willReturn($userAttribution);
        $connect->method('setUser');

        $connect->switchLocalUsername('test', 'USER');
    }

    public function testSwitchRoleNotAuthenticated()
    {
        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'isAuthenticated'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(false);

        $this->expectException(UserAttributionException::class);
        $connect->switchRole('USER');
    }

    public function testSwitchRoleNotUser()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods([
                'get'
            ])
            ->getMock();
        $userAttribution->method('get')->willThrowException(new \Exception());

        $config = (new Config())
            ->setEntityID('http://filer.flash-global.eu');

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'getConfig',
                'isAuthenticated',
                'getUserAttribution'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(true);
        $connect->method('getUser')->willReturn(
            (new User())
                ->setUserName('test')
        );
        $connect->method('getConfig')->willReturn($config);
        $connect->method('getUserAttribution')->willReturn($userAttribution);

        $this->expectException(UserAttributionException::class);
        $connect->switchRole('USER');
    }

    public function testSwitchRoleNotRole()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods([
                'get'
            ])
            ->getMock();
        $userAttribution->method('get')->willReturn([
            (new Attribution())
                ->setRole(
                    (new Role())
                        ->setRole('ADMIN')
                )
                ->setApplication(
                    (new Application())
                        ->setUrl('http://filer.flash-global.eu')
                )
                ->setUser(
                    (new User())
                        ->setUserName('test')
                )
                ->toArray(),
        ]);

        $config = (new Config())
            ->setEntityID('http://filer.flash-global.eu');

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'setUser',
                'getConfig',
                'isAuthenticated',
                'getUserAttribution'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(true);
        $connect->method('getUser')->willReturn(
            (new User())
                ->setUserName('test')
        );
        $connect->method('getConfig')->willReturn($config);
        $connect->method('getUserAttribution')->willReturn($userAttribution);
        $connect->method('setUser');

        $this->expectException(UserAttributionException::class);
        $connect->switchRole('USER');
    }

    public function testSwitchRole()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods([
                'get'
            ])
            ->getMock();
        $userAttribution->method('get')->willReturn([
            (new Attribution())
                ->setRole(
                    (new Role())
                        ->setRole('USER')
                )
                ->setApplication(
                    (new Application())
                        ->setUrl('http://filer.flash-global.eu')
                )
                ->setUser(
                    (new User())
                        ->setUserName('test')
                )
                ->toArray(),
            (new Attribution())
                ->setRole(
                    (new Role())
                        ->setRole('ADMIN')
                )
                ->setApplication(
                    (new Application())
                        ->setUrl('http://filer.flash-global.eu')
                )
                ->setUser(
                    (new User())
                        ->setUserName('test')
                )
                ->toArray(),
        ]);

        $config = (new Config())
            ->setEntityID('http://filer.flash-global.eu');

        $connect = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser',
                'setUser',
                'getConfig',
                'isAuthenticated',
                'getUserAttribution'
            ])
            ->getMock();
        $connect->method('isAuthenticated')->willReturn(true);
        $connect->method('getUser')->willReturn(
            (new User())
                ->setUserName('test')
        );
        $connect->method('getConfig')->willReturn($config);
        $connect->method('getUserAttribution')->willReturn($userAttribution);
        $connect->method('setUser');

        $connect->switchRole('USER');
    }

    protected function testOneAccessors($name, $expected)
    {
        $class = $this->getMockBuilder(Connect::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSaml',
                'getUser'
            ])
            ->getMock();

        $setter = 'set' . ucfirst($name);
        $getter = 'get' . ucfirst($name);
        $class->$setter($expected);
        $this->assertEquals($class->$getter(), $expected);
        $this->assertAttributeEquals($class->$getter(), $name, $class);
    }
}
