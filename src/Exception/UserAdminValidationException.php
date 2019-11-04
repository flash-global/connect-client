<?php

namespace Fei\Service\Connect\Client\Exception;

use Throwable;

/**
 * Class UserAdminValidationException
 * @package Fei\Service\Connect\Client\Exception
 */
class UserAdminValidationException extends UserAdminException
{
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * UserAdminValidationException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $errors
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, array $errors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->setErrors($errors);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @return UserAdminValidationException
     */
    public function setErrors(array $errors): UserAdminValidationException
    {
        $this->errors = $errors;
        return $this;
    }
}
