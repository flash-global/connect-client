<?php

namespace Fei\Service\Connect\Client\Admin;

use Exception;
use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\RequestDescriptor;
use Fei\Service\Connect\Client\Exception\UserAdminException;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Exception\ValidatorException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Class UserAdmin
 *
 * @package Fei\Service\Connect\Client
 */
class UserAdmin extends AbstractApiClient implements UserAdminInterface
{
    const API_USERS_PATH_INFO = '/api/v3/users';
    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';

    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * Persist a user entity
     *
     * @param User $user
     * @param bool $sendValidationEmail
     *
     * @return User
     *
     * @throws ApiClientException
     * @throws UserAdminException
     */
    public function create(User $user, bool $sendValidationEmail = true): User
    {
        $query = http_build_query([
            'validation-email' => $sendValidationEmail,
        ]);

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO) . '?' . $query)
            ->setMethod("POST")
            ->setRawData(json_encode($user->toArray()))
        ;

        return $this->sendReturnUser($request);
    }

    /**
     * Delete a user entity by entity, its username or email
     *
     * @param User|string $user
     *
     * @return User
     *
     * @throws Exception
     */
    public function delete($user): User
    {
        if ($user instanceof User) {
            $user = $user->getEmail();
        }

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO . "/" . $user))
            ->setMethod("DELETE")
        ;

        return $this->sendReturnUser($request);
    }

    /**
     * Edit a user entity
     *
     * @param User $formerUser
     * @param User $newUser
     *
     * @return User
     *
     * @throws ApiClientException
     * @throws UserAdminException
     */
    public function edit(User $formerUser, User $newUser): User
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO . "/"  . $formerUser->getUserName()))
            ->setMethod("PUT")
            ->setRawData(json_encode($newUser->toArray()))
        ;

        return $this->sendReturnUser($request);
    }

    /**
     * Retrieve a user entity by its username or email
     *
     * @param string $user
     *
     * @return User
     *
     * @throws UserAdminException
     * @throws ApiClientException
     * @throws ValidatorException
     */
    public function retrieve(string $user): User
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO . "/" . $user))
            ->setMethod("GET")
        ;

        return $this->sendReturnUser($request);
    }

    /**
     * Generate a reset password token by user email or username or user entity
     *
     * @param string|User $user
     * @return string
     *
     * @throws UserAdminException
     * @throws ApiClientException
     */
    public function generateResetPasswordToken($user): string
    {
        if ($user instanceof User) {
            $user = $user->getEmail();
        }

        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO . "/" . urlencode($user) . "/password/reset-token"))
            ->setMethod("GET")
        ;

        try {
            $content = $this->send($request);
            $decodedContent = json_decode($content->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new UserAdminException(json_last_error_msg(), json_last_error());
            }

            return $decodedContent['token'];
        } catch (Throwable $exception) {
            throw $this->parseSendException($exception);
        }
    }

    /**
     * Validate a reset password token and return a User entity instance
     *
     * @param string $token
     *
     * @return User
     *
     * @throws ApiClientException
     * @throws UserAdminException
     */
    public function validateResetPasswordToken(string $token): User
    {
        $request = (new RequestDescriptor())
            ->setUrl(
                $this->buildUrl(self::API_USERS_PATH_INFO)
                . '/password/reset-token?'
                . http_build_query(['token' => $token])
            )
            ->setMethod("GET");

        return $this->sendReturnUser($request);
    }

    /**
     * @param RequestDescriptor $request
     */
    protected function addAuthorization(RequestDescriptor $request)
    {
        if (empty($this->getUsername()) || empty($this->getPassword())) {
            return;
        }

        $credentials = base64_encode($this->getUsername() . ":" . $this->getPassword());
        $request->addHeader("Authorization", "Basic " . $credentials);
    }

    /**
     * {@inheritDoc}
     */
    public function send(RequestDescriptor $request, $flags = 0)
    {
        $this->addAuthorization($request);
        return parent::send($request, $flags);
    }

    /**
     * @param RequestDescriptor $request
     *
     * @return User
     *
     * @throws UserAdminException
     * @throws ValidatorException
     */
    protected function sendReturnUser(RequestDescriptor $request): User
    {
        try {
            $content = $this->send($request);
            $decodedContent = json_decode($content->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new UserAdminException(json_last_error_msg(), json_last_error());
            }

            return new User($decodedContent);
        } catch (Throwable $exception) {
            throw $this->parseSendException($exception);
        }
    }

    /**
     * @param Throwable $exception
     * @return UserAdminException|ValidatorException
     */
    protected function parseSendException(Throwable $exception)
    {
        if (!$exception->getPrevious()) {
            return new UserAdminException($exception->getMessage(), $exception->getCode(), $exception);
        }
        if (!($exception->getPrevious() instanceof RequestException)) {
            return new UserAdminException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }

        $exception = $exception->getPrevious();

        if ($exception->getCode() !== 400) {
            return new UserAdminException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $response = $exception->getResponse();
        $exception = new ValidatorException($exception->getMessage(), $exception->getCode(), $exception);

        if (!($response instanceof ResponseInterface)) {
            return $exception;
        }

        $content = json_decode((string)$response->getBody(), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $exception->setErrors($content['errors'] ?? []);
        }

        return $exception;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return UserAdmin
     */
    public function setUsername($username): UserAdmin
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return UserAdmin
     */
    public function setPassword($password): UserAdmin
    {
        $this->password = $password;
        return $this;
    }
}
