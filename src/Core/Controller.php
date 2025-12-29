<?php

namespace Redium\Core;

abstract class Controller
{
    /**
     * Return JSON response
     * 
     * @param mixed $data Data to return
     * @param int $statusCode HTTP status code
     * @return array
     */
    protected function json(mixed $data, int $statusCode = 200): array
    {
        http_response_code($statusCode);
        return $data;
    }

    /**
     * Return success response
     * 
     * @param string $message Success message
     * @param mixed $data Optional data
     * @return array
     */
    protected function success(string $message, mixed $data = null): array
    {
        $response = ['success' => true, 'message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Return error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @return array
     */
    protected function error(string $message, int $statusCode = 400): array
    {
        http_response_code($statusCode);
        return [
            'success' => false,
            'error' => $message
        ];
    }

    /**
     * Validate required fields in data
     * 
     * @param array $data Data to validate
     * @param array $required Required field names
     * @throws \InvalidArgumentException
     */
    protected function validate(array $data, array $required): void
    {
        $missing = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                "Missing required fields: " . implode(', ', $missing)
            );
        }
    }
}
