<?php

namespace Redium\Auth\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct(string $message = "Access denied - insufficient permissions", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
