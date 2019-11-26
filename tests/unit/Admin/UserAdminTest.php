<?php

namespace Test\Fei\Service\Connect\Client\Admin;

use DateTime;
use Exception;
use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\RequestDescriptor;
use Fei\Service\Connect\Client\Admin\UserAdmin;
use Fei\Service\Connect\Client\Exception\UserAdminException;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Exception\ValidatorException;
use GrumPHP\Util\Str;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class UserTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class UserAdminTest extends TestCase
{
    /**
     * @var string
     */
    protected $username = 'user1';

    /**
     * @var string
     */
    protected $password = 'pass1';

    /**
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * @var MockObject|UserAdmin
     */
    protected $instance;

    public function setUp()
    {
        parent::setUp();

        $this->instance = $this->getMockBuilder(UserAdmin::class)->setMethods(['send'])->getMock();
        $this->instance->setUsername($this->username);
        $this->instance->setPassword($this->password);
        $this->instance->setBaseUrl($this->baseUrl);
    }

    public function testConstructWithUsernamePasswordOption()
    {
        $instance = new UserAdmin([
            UserAdmin::OPTION_USERNAME => $this->username,
            UserAdmin::OPTION_PASSWORD => $this->password,
        ]);

        $this->assertEquals($this->username, $instance->getUsername());
        $this->assertEquals($this->password, $instance->getPassword());
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     */
    public function testCreateError500()
    {
        $user = new User();

        /** @var MockObject|RequestInterface $psrRequestMock */
        $psrRequestMock = $this->createMock(RequestInterface::class);
        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getStatusCode')
                        ->willReturn(500)
        ;

        $requestException = new RequestException("Error 1", $psrRequestMock, $psrResponseMock);
        $apiClientException = new ApiClientException("Error  2", 0, $requestException);
        $userAdminException = new UserAdminException("Error 1", 500, $requestException);

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO)
            ->setMethod("POST")
            ->setRawData(json_encode($user->toArray()))
        ;

        $this->instance->expects($this->once())
            ->method('send')
            ->with($request)
            ->willThrowException($apiClientException);

        $this->expectExceptionObject($userAdminException);
        $this->instance->create($user);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     */
    public function testCreateError400()
    {
        $errors = ['username' => 'bullshit'];
        $user = new User();

        /** @var MockObject|RequestInterface $psrRequestMock */
        $psrRequestMock = $this->createMock(RequestInterface::class);
        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);
        /** @var MockObject|Str $psrStreamMock */
        $psrStreamMock = $this->createMock(StreamInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getStatusCode')
                        ->willReturn(400)
        ;

        $psrResponseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($psrStreamMock)
        ;

        $psrStreamMock->expects($this->once())
            ->method('__toString')
            ->willReturn(json_encode(['errors' => $errors]))
        ;

        $requestException = new RequestException("Error 1", $psrRequestMock, $psrResponseMock);
        $apiClientException = new ApiClientException("Error  2", 0, $requestException);
        $validationException = (new ValidatorException("Error 1", 400, $requestException))->setErrors($errors);

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO)
            ->setMethod("POST")
            ->setRawData(json_encode($user->toArray()))
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willThrowException($apiClientException)
        ;

        $this->expectExceptionObject($validationException);
        $this->instance->create($user);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     * @throws Exception
     */
    public function testCreateSuccess()
    {
        $user = new User();
        $userData = $user->toArray();

        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);
        /** @var MockObject|Str $psrStreamMock */
        $psrStreamMock = $this->createMock(StreamInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getBody')
                        ->willReturn($psrStreamMock)
        ;

        $psrStreamMock->expects($this->once())
                      ->method('__toString')
                      ->willReturn(json_encode($userData))
        ;

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO)
            ->setMethod("POST")
            ->setRawData(json_encode($user->toArray()))
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willReturn($psrResponseMock)
        ;

        $result = $this->instance->create($user);
        $this->assertInstanceOf(User::class, $result);

        $dateTime = new DateTime();
        $user->setCreatedAt($dateTime);
        $result->setCreatedAt($dateTime);

        $this->assertEquals($user, $result);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     */
    public function testEditError500()
    {
        $username = "user1";
        $user = (new User())->setUserName($username);

        /** @var MockObject|RequestInterface $psrRequestMock */
        $psrRequestMock = $this->createMock(RequestInterface::class);
        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getStatusCode')
                        ->willReturn(500)
        ;

        $requestException = new RequestException("Error 1", $psrRequestMock, $psrResponseMock);
        $apiClientException = new ApiClientException("Error  2", 0, $requestException);
        $userAdminException = new UserAdminException("Error 1", 500, $requestException);

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$username")
            ->setMethod("PUT")
            ->setRawData(json_encode($user->toArray()))
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willThrowException($apiClientException);

        $this->expectExceptionObject($userAdminException);
        $this->instance->edit($user, $user);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     */
    public function testEditError400()
    {
        $errors = ['username' => 'bullshit'];
        $username = "user1";
        $user = (new User())->setUserName($username);

        /** @var MockObject|RequestInterface $psrRequestMock */
        $psrRequestMock = $this->createMock(RequestInterface::class);
        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);
        /** @var MockObject|Str $psrStreamMock */
        $psrStreamMock = $this->createMock(StreamInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getStatusCode')
                        ->willReturn(400)
        ;

        $psrResponseMock->expects($this->once())
                        ->method('getBody')
                        ->willReturn($psrStreamMock)
        ;

        $psrStreamMock->expects($this->once())
                      ->method('__toString')
                      ->willReturn(json_encode(['errors' => $errors]))
        ;

        $requestException = new RequestException("Error 1", $psrRequestMock, $psrResponseMock);
        $apiClientException = new ApiClientException("Error  2", 0, $requestException);
        $validationException = (new ValidatorException("Error 1", 400, $requestException))->setErrors($errors);

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$username")
            ->setMethod("PUT")
            ->setRawData(json_encode($user->toArray()))
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willThrowException($apiClientException)
        ;

        $this->expectExceptionObject($validationException);
        $this->instance->edit($user, $user);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     * @throws Exception
     */
    public function testEditSuccess()
    {
        $username = "user1";
        $user = (new User())->setUserName($username);
        $userData = $user->toArray();

        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);
        /** @var MockObject|Str $psrStreamMock */
        $psrStreamMock = $this->createMock(StreamInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getBody')
                        ->willReturn($psrStreamMock)
        ;

        $psrStreamMock->expects($this->once())
                      ->method('__toString')
                      ->willReturn(json_encode($userData))
        ;

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$username")
            ->setMethod("PUT")
            ->setRawData(json_encode($user->toArray()))
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willReturn($psrResponseMock)
        ;

        $result = $this->instance->edit($user, $user);
        $this->assertInstanceOf(User::class, $result);

        $dateTime = new DateTime();
        $user->setCreatedAt($dateTime);
        $result->setCreatedAt($dateTime);

        $this->assertEquals($user, $result);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     */
    public function testRetrieveError500()
    {
        $user = 'user1';

        /** @var MockObject|RequestInterface $psrRequestMock */
        $psrRequestMock = $this->createMock(RequestInterface::class);
        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getStatusCode')
                        ->willReturn(500);

        $requestException = new RequestException("Error 1", $psrRequestMock, $psrResponseMock);
        $apiClientException = new ApiClientException("Error  2", 0, $requestException);
        $userAdminException = new UserAdminException("Error 1", 500, $requestException);

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$user")
            ->setMethod("GET")
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willThrowException($apiClientException)
        ;

        $this->expectExceptionObject($userAdminException);
        $this->instance->retrieve($user);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     * @throws Exception
     */
    public function testRetrieveSuccess()
    {
        $username = "user1";
        $user = new User();
        $userData = $user->toArray();

        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);
        /** @var MockObject|Str $psrStreamMock */
        $psrStreamMock = $this->createMock(StreamInterface::class);

        $psrResponseMock->expects($this->once())
                        ->method('getBody')
                        ->willReturn($psrStreamMock)
        ;

        $psrStreamMock->expects($this->once())
                      ->method('__toString')
                      ->willReturn(json_encode($userData))
        ;

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$username")
            ->setMethod("GET")
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willReturn($psrResponseMock)
        ;

        $result = $this->instance->retrieve($username);
        $this->assertInstanceOf(User::class, $result);

        $dateTime = new DateTime();
        $user->setCreatedAt($dateTime);
        $result->setCreatedAt($dateTime);

        $this->assertEquals($user, $result);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     */
    public function testGenerateResetPasswordTokenError500()
    {
        $user = 'user1';

        /** @var MockObject|RequestInterface $psrRequestMock */
        $psrRequestMock = $this->createMock(RequestInterface::class);
        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);

        $psrResponseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(500);

        $requestException = new RequestException("Error 1", $psrRequestMock, $psrResponseMock);
        $apiClientException = new ApiClientException("Error  2", 0, $requestException);
        $userAdminException = new UserAdminException("Error 1", 500, $requestException);

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$user/password/reset-token")
            ->setMethod("GET")
        ;

        $this->instance->expects($this->once())
            ->method('send')
            ->with($request)
            ->willThrowException($apiClientException)
        ;

        $this->expectExceptionObject($userAdminException);
        $this->instance->generateResetPasswordToken($user);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     */
    public function testGenerateResetPasswordTokenError404()
    {
        $user = 'user1';

        /** @var MockObject|RequestInterface $psrRequestMock */
        $psrRequestMock = $this->createMock(RequestInterface::class);
        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);

        $psrResponseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(404);

        $requestException = new RequestException("Error 1", $psrRequestMock, $psrResponseMock);
        $apiClientException = new ApiClientException("Error  2", 0, $requestException);
        $userAdminException = new UserAdminException("Error 1", 404, $requestException);

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$user/password/reset-token")
            ->setMethod("GET")
        ;

        $this->instance->expects($this->once())
            ->method('send')
            ->with($request)
            ->willThrowException($apiClientException)
        ;

        $this->expectExceptionObject($userAdminException);
        $this->instance->generateResetPasswordToken($user);
    }

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     * @throws Exception
     */
    public function testGenerateResetPasswordTokenSuccess()
    {
        $username = "user1";
        $token = "1234";
        $tokenData = ['token' => $token];

        /** @var MockObject|ResponseInterface $psrResponseMock */
        $psrResponseMock = $this->createMock(ResponseInterface::class);
        /** @var MockObject|Str $psrStreamMock */
        $psrStreamMock = $this->createMock(StreamInterface::class);

        $psrResponseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($psrStreamMock)
        ;

        $psrStreamMock->expects($this->once())
            ->method('__toString')
            ->willReturn(json_encode($tokenData))
        ;

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/$username/password/reset-token")
            ->setMethod("GET")
        ;

        $this->instance->expects($this->once())
            ->method('send')
            ->with($request)
            ->willReturn($psrResponseMock)
        ;

        $result = $this->instance->generateResetPasswordToken($username);
        $this->assertEquals($token, $result);
    }
}
