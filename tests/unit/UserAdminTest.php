<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\ApiClient\RequestDescriptor;
use Fei\ApiClient\ResponseDescriptor;
use Fei\Service\Connect\Client\Config\Config;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Exception\UserException;
use Fei\Service\Connect\Client\Token;
use Fei\Service\Connect\Client\UserAdmin;
use Fei\Service\Connect\Common\Entity\User as UserEntity;
use Fei\Service\Connect\Common\Entity\User;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response\TextResponse;

/**
 * Class UserTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class UserAdminTest extends TestCase
{
    /**
     * @return array
     */
    public function providerPersist()
    {
        return [
            [(new UserEntity())
                ->setEmail('a@z.e')
                ->setFirstName('testFirstName')
                ->setLastName('testLastName')
                ->setUserName('testUserName')
            ],
        ];
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testPersist(UserEntity $userEntity)
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $userAdmin->expects($this->once())->method('createToken')->willReturn('token');
        $userAdmin->expects($this->once())
            ->method('send')
            ->willReturn(
                (new ResponseDescriptor())->setBody(json_encode(['created' => 1]))
            );

        $user = new User();

        $userAdmin->persist($user);

        $this->assertEquals(1, $user->getId());
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testPersistUserException(UserEntity $userEntity)
    {
        $connect = $this->getMockBuilder(Connect::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(Token::class)->disableOriginalConstructor()->getMock();

        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->setConstructorArgs([$connect, $token])
            ->setMethods(['send', 'createToken', 'setCertificate', 'fetchCertificate'])
            ->getMock();

        $exception = new UserException();
        $userAdmin->expects($this->once())->method('send')->willThrowException($exception);
        $userAdmin->expects($this->once())->method('createToken')->willReturn('FAKE-TOKEN');

        $this->setExpectedException(UserException::class, $exception);

        /** @var UserAdmin $userAdmin */
        $userAdmin->persist($userEntity);
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testPersistBadResponseException(UserEntity $userEntity)
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['send', 'createToken'])
            ->getMock();

        $response = new TextResponse(json_encode([
            'error' => 'error',
            'code' => 500
        ]));
        $badResponse = new \GuzzleHttp\Exception\BadResponseException('', new Request(), $response);
        $exception = new \Exception('exception', 0, $badResponse);

        $userAdmin->expects($this->once())->method('send')->willThrowException($exception);
        $this->setExpectedException(UserException::class, 'error');

        $userAdmin->persist($userEntity);
    }

    /**
     * @return array
     */
    public function providerEdit()
    {
        return [
            [(new UserEntity())
                ->setEmail('a@z.e')
                ->setFirstName('testFirstName')
                ->setLastName('testLastName')
                ->setUserName('testUserName')
            ]
        ];
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testEdit(UserEntity $userEntity)
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $userAdmin->expects($this->once())->method('createToken')->willReturn('token');
        $userAdmin->expects($this->once())
            ->method('send')
            ->willReturn(
                (new ResponseDescriptor())
            );

        $user = new User();

        $userAdmin->edit($user, $userEntity);
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testEditUserException(UserEntity $userEntity)
    {
        $exception = new UserException();

        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $userAdmin->expects($this->once())->method('createToken')->willReturn('token');
        $userAdmin->expects($this->once())
            ->method('send')
            ->willThrowException($exception);
        $this->setExpectedException(UserException::class, $exception);

        $userAdmin->edit($userEntity, $userEntity);
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testEditBadResponseException(UserEntity $userEntity)
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $response = new TextResponse(json_encode([
            'error' => 'error',
            'code' => 500
        ]));

        $badResponse = new \GuzzleHttp\Exception\BadResponseException('', new Request(), $response);
        $exception = new \Exception('exception', 0, $badResponse);

        $userAdmin->expects($this->once())->method('send')->willThrowException($exception);
        $this->setExpectedException(UserException::class, 'error');

        $userAdmin->edit($userEntity, $userEntity);
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testDelete(UserEntity $userEntity)
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $response = new ResponseDescriptor();

        $userAdmin->expects($this->once())->method('send')->willReturn($response);
        $userAdmin->delete($userEntity);
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testDeleteUserException(UserEntity $userEntity)
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $exception = new UserException();
        $userAdmin->expects($this->once())->method('send')->willThrowException($exception);
        $this->setExpectedException(UserException::class, $exception);

        $userAdmin->delete($userEntity);
    }

    /**
     * @param UserEntity $userEntity
     * @dataProvider providerPersist
     */
    public function testDeleteBadResponseException(UserEntity $userEntity)
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $response = new TextResponse(json_encode([
            'error' => 'error',
            'code' => 500
        ]));
        $badResponse = new \GuzzleHttp\Exception\BadResponseException('', new Request(), $response);
        $exception = new \Exception('exception', 0, $badResponse);

        $userAdmin->expects($this->once())->method('send')->willThrowException($exception);
        $this->setExpectedException(UserException::class, 'error');

        $userAdmin->delete($userEntity);
    }

    public function testGetToken()
    {
        $userAdmin = $this->getMockBuilder(UserAdmin::class)
            ->disableOriginalConstructor()
            ->setMethods(['createToken', 'send'])
            ->getMock();

        $userAdmin->getToken();
    }
}
