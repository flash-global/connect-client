<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\ApiClient\ResponseDescriptor;
use Fei\Service\Connect\Client\Exception\UserAttributionException;
use Fei\Service\Connect\Client\UserAttribution;
use Guzzle\Http\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class UserAttributionTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class UserAttributionTest extends TestCase
{
    /**
     * @dataProvider addDataProvider
     */
    public function testAdd($body, $user, $application, $role, $isDefault, $localUsername)
    {
        $descriptor = $this->getMockBuilder(ResponseDescriptor::class)
            ->setMethods(['getBody'])
            ->getMock();
        $descriptor->expects($this->once())->method('getBody')
            ->willReturn($body);

        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')
            ->willReturn($descriptor);

        $result = json_decode($body, true);
        $expected = isset($result['added']) && count($result['added']) > 0 ? reset($result['added']) : null;
        $this->assertEquals($expected, $userAttribution->add($user, $application, $role, $isDefault, $localUsername));
    }

    public function addDataProvider()
    {
        return [
            0 => [
                '{
                    "code": 201,
                    "message": "1 attribution(s) successfully added",
                    "added": []
                }',
                'boris',
                '28',
                '1',
                true,
                null
            ],
            1 => [
                '{
                    "code": 201,
                    "message": "1 attribution(s) successfully added",
                    "added": [
                        {
                            "id": 123,
                            "user": {
                                "id": 1,
                                "user_name": "boris",
                                "password": null,
                                "created_at": "2017-02-23T17:51:24+00:00",
                                "status": 2,
                                "first_name": "Boris",
                                "last_name": "Cerati",
                                "email": "bce@bce.fr",
                                "register_token": null,
                                "current_role": null,
                                "local_username": null,
                                "attributions": [
                                    {
                                        "id": 86,
                                        "application": {
                                            "id": 2,
                                            "name": "Connect Admin",
                                            "url": "http://127.0.0.1:9060",
                                            "status": 1,
                                            "logo_url": null,
                                            "allow_profile_association": false,
                                            "is_subscribed": false,
                                            "is_manageable": false,
                                            "contexts": []
                                        },
                                        "role": {
                                            "id": 2,
                                            "role": "USER",
                                            "label": "User",
                                            "user_created": false
                                        },
                                        "is_default": false
                                    }
                                ],
                                "foreign_services_ids": [],
                                "avatar_url": "",
                                "mini_avatar_url": "",
                                "language": "de",
                                "role_id": null
                            },
                            "application": {
                                "id": 28,
                                "name": "http://filer-api_php",
                                "url": "http://127.0.0.1:8020",
                                "status": 1,
                                "logo_url": null,
                                "allow_profile_association": false,
                                "is_subscribed": false,
                                "is_manageable": true,
                                "contexts": {
                                    "ping": "http://filer-api_php/connect/admin"
                                }
                            },
                            "role": {
                                "id": 1,
                                "role": "ADMIN",
                                "label": "Admin",
                                "user_created": false
                            },
                            "is_default": true
                        }
                    ]
                }',
                'boris',
                '28',
                '1',
                true,
                null
            ],
            2 => [
                '{
                    "code": 201,
                    "message": "1 attribution(s) successfully added",
                    "added": []
                }',
                'boris',
                'http://filer.flash-global.eu',
                'ADMIN',
                false,
                null
            ],
            3 => [
                '{
                    "code": 201,
                    "message": "1 attribution(s) successfully added",
                    "added": [
                        {
                            "id": 123,
                            "user": {
                                "id": 1,
                                "user_name": "boris",
                                "password": null,
                                "created_at": "2017-02-23T17:51:24+00:00",
                                "status": 2,
                                "first_name": "Boris",
                                "last_name": "Cerati",
                                "email": "bce@bce.fr",
                                "register_token": null,
                                "current_role": null,
                                "local_username": null,
                                "attributions": [
                                    {
                                        "id": 86,
                                        "application": {
                                            "id": 2,
                                            "name": "Connect Admin",
                                            "url": "http://127.0.0.1:9060",
                                            "status": 1,
                                            "logo_url": null,
                                            "allow_profile_association": false,
                                            "is_subscribed": false,
                                            "is_manageable": false,
                                            "contexts": []
                                        },
                                        "role": {
                                            "id": 2,
                                            "role": "USER",
                                            "label": "User",
                                            "user_created": false
                                        },
                                        "is_default": false
                                    }
                                ],
                                "foreign_services_ids": [],
                                "avatar_url": "",
                                "mini_avatar_url": "",
                                "language": "de",
                                "role_id": null
                            },
                            "application": {
                                "id": 28,
                                "name": "http://filer-api_php",
                                "url": "http://127.0.0.1:8020",
                                "status": 1,
                                "logo_url": null,
                                "allow_profile_association": false,
                                "is_subscribed": false,
                                "is_manageable": true,
                                "contexts": {
                                    "ping": "http://filer-api_php/connect/admin"
                                }
                            },
                            "role": {
                                "id": 1,
                                "role": "ADMIN",
                                "label": "Admin",
                                "user_created": false
                            },
                            "is_default": true
                        }
                    ]
                }',
                'boris',
                'http://filer.flash-global.eu',
                'ADMIN',
                false,
                null
            ]
        ];
    }

    public function testAddUserAttributionException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
            new \Exception('', 0)
        );

        $this->expectException(UserAttributionException::class);
        $userAttribution->add('boris', 'http://filer.flash-global.net', 'ADMIN', true, 'test');
    }

    public function testAddBadResponseException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
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

        $this->expectException(UserAttributionException::class);
        $userAttribution->add('boris', 'http://filer.flash-global.com', 'ADMIN', true, 'test');
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGet($body)
    {
        $descriptor = $this->getMockBuilder(ResponseDescriptor::class)
            ->setMethods(['getBody'])
            ->getMock();
        $descriptor->expects($this->once())->method('getBody')
            ->willReturn($body);

        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')
            ->willReturn($descriptor);

        $expected = json_decode($body, true);
        $this->assertEquals($expected, $userAttribution->get('boris', '28'));
    }

    public function getDataProvider()
    {
        return [
            0 => [
                '[]'
            ],
            1 => [
                '[
                    {
                        "id": 123,
                        "user": {
                            "id": 1,
                            "user_name": "boris",
                            "password": null,
                            "created_at": "2017-02-23T17:51:24+00:00",
                            "status": 2,
                            "first_name": "Boris",
                            "last_name": "Cerati",
                            "email": "bce@bce.fr",
                            "register_token": null,
                            "current_role": null,
                            "local_username": null,
                            "attributions": [
                                {
                                    "id": 86,
                                    "application": {
                                        "id": 2,
                                        "name": "Connect Admin",
                                        "url": "http://127.0.0.1:9060",
                                        "status": 1,
                                        "logo_url": null,
                                        "allow_profile_association": false,
                                        "is_subscribed": false,
                                        "is_manageable": false,
                                        "contexts": []
                                    },
                                    "role": {
                                        "id": 2,
                                        "role": "USER",
                                        "label": "User",
                                        "user_created": false
                                    },
                                    "is_default": false
                                }
                            ],
                            "foreign_services_ids": [],
                            "avatar_url": "",
                            "mini_avatar_url": "",
                            "language": "de",
                            "role_id": null
                        },
                        "application": {
                            "id": 28,
                            "name": "http://filer-api_php",
                            "url": "http://127.0.0.1:8020",
                            "status": 1,
                            "logo_url": null,
                            "allow_profile_association": false,
                            "is_subscribed": false,
                            "is_manageable": true,
                            "contexts": {
                                "ping": "http://filer-api_php/connect/admin"
                            }
                        },
                        "role": {
                            "id": 1,
                            "role": "ADMIN",
                            "label": "Admin",
                            "user_created": false
                        },
                        "is_default": true
                    }
                ]'
            ]
        ];
    }

    public function testGetUserAttributionException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
            new \Exception('', 0)
        );

        $this->expectException(UserAttributionException::class);
        $userAttribution->get('boris');
    }

    public function testGetBadResponseException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
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

        $this->expectException(UserAttributionException::class);
        $userAttribution->get('boris');
    }

    public function testRemove()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send');

        $userAttribution->remove('boris', '28', '1');
    }

    public function testRemoveUserAttributionException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
            new \Exception('', 0)
        );

        $this->expectException(UserAttributionException::class);
        $userAttribution->remove('boris', '28', '1');
    }

    public function testRemoveBadResponseException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
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

        $this->expectException(UserAttributionException::class);
        $userAttribution->remove('boris', '28', '1');
    }

    public function testRemoveAll()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send');

        $userAttribution->removeAll('boris', '28');
    }

    public function testRemoveAllUserAttributionException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
            new \Exception('', 0)
        );

        $this->expectException(UserAttributionException::class);
        $userAttribution->removeAll('boris', '28');
    }

    public function testRemoveAllBadResponseException()
    {
        $userAttribution = $this->getMockBuilder(UserAttribution::class)
            ->setMethods(['send'])
            ->getMock();
        $userAttribution->expects($this->once())->method('send')->willThrowException(
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

        $this->expectException(UserAttributionException::class);
        $userAttribution->removeAll('boris', 'http://filer.flash-global.net');
    }
}
