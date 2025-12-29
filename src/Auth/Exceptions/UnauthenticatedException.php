<?php

namespace Redium\Auth\Exceptions;

use Exception;

class UnauthenticatedException extends Exception
{
    public function __construct(string $message = "Authentication required", int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
