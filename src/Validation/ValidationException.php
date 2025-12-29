<?php

namespace Redium\Validation;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors, int $code = 422)
    {
        $this->errors = $errors;
        $message = "Validation failed: " . $this->formatErrors();
        parent::__construct($message, $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function formatErrors(): string
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            $messages[] = implode(', ', $fieldErrors);
        }
        return implode('; ', $messages);
    }
}
