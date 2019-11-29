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
use ReflectionMethod;

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
     * @throws Exception
     */
    public function testDeleteNotImplemented()
    {
        $this->expectExceptionObject(new Exception('Not implemented'));
        $this->instance->delete('');
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
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . '?validation-email=1')
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
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . '?validation-email=1')
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
    public function testCreateJsonError()
    {
        $user = new User();

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
                      ->willReturn('{"user":"plop"')
        ;

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . '?validation-email=1')
            ->setMethod("POST")
            ->setRawData(json_encode($user->toArray()))
        ;

        $this->instance->expects($this->once())
                       ->method('send')
                       ->with($request)
                       ->willReturn($psrResponseMock)
        ;

        $this->expectExceptionObject(new Exception('Syntax error', JSON_ERROR_SYNTAX));
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
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . '?validation-email=1')
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

    /**
     * @throws ApiClientException
     * @throws UserAdminException
     * @throws ValidatorException
     * @throws Exception
     */
    public function testGenerateResetPasswordTokenSuccessWithUserInstance()
    {
        $email = "user1@b.c";
        $token = "1234";
        $tokenData = ['token' => $token];
        $user = (new User())->setEmail($email);

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
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/" . urlencode($email) . "/password/reset-token")
            ->setMethod("GET")
        ;

        $this->instance->expects($this->once())
            ->method('send')
            ->with($request)
            ->willReturn($psrResponseMock)
        ;

        $result = $this->instance->generateResetPasswordToken($user);
        $this->assertEquals($token, $result);
    }

    /**
     * @throws ApiClientException
     * @throws Exception
     */
    public function testValidateResetPasswordTokenSuccess()
    {
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
            ->willReturn(<<<JSON
{
  "user_name": "ftaggart",
  "email": "ftaggart@idsoftware.com",
  "created_at": "2019-11-28T09:04:36.578Z",
  "status": 1,
  "first_name": "Flynn",
  "last_name": "Taggart",
  "register_token": "4b9ea159acb316fe9204e9164db22521",
  "language": "fr",
  "user_groups": [
    {
      "name": "string"
    }
  ]
}
JSON
            );

        $request = (new RequestDescriptor())
            ->setUrl($this->baseUrl . UserAdmin::API_USERS_PATH_INFO . "/password/reset-token?token=mytoken")
            ->setMethod("GET")
        ;

        $this->instance->expects($this->once())
            ->method('send')
            ->with($request)
            ->willReturn($psrResponseMock)
        ;

        $result = $this->instance->validateResetPasswordToken('mytoken');

        $user = new User(json_decode(<<<JSON
{
  "user_name": "ftaggart",
  "email": "ftaggart@idsoftware.com",
  "created_at": "2019-11-28T09:04:36.578Z",
  "status": 1,
  "first_name": "Flynn",
  "last_name": "Taggart",
  "register_token": "4b9ea159acb316fe9204e9164db22521",
  "language": "fr",
  "user_groups": [
    {
      "name": "string"
    }
  ]
}
JSON
            , true));

        $this->assertEquals($user, $result);
    }

    public function testAddAuthorization()
    {
        $requestDescriptor = new RequestDescriptor();

        $reflection = new ReflectionMethod(UserAdmin::class, 'addAuthorization');
        $reflection->setAccessible(true);
        $reflection->invoke($this->instance, $requestDescriptor);

        $this->assertEquals('Basic ' . base64_encode($this->username . ":" . $this->password), $requestDescriptor->getHeader('Authorization'));
    }

    public function testAddAuthorizationEmpty()
    {
        $requestDescriptor = new RequestDescriptor();
        $this->instance->setUsername('');

        $reflection = new ReflectionMethod(UserAdmin::class, 'addAuthorization');
        $reflection->setAccessible(true);
        $reflection->invoke($this->instance, $requestDescriptor);

        $this->assertArrayNotHasKey('Authorization', $requestDescriptor->getHeaders());
    }
}
