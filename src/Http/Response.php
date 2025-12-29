<?php

namespace Redium\Http;

class Response
{
    /**
     * Create a standardized success response
     */
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200): array
    {
        $response = [
            'success' => true,
            'message' => $message,
            'code' => $code
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        http_response_code($code);
        return $response;
    }

    /**
     * Create a standardized error response
     */
    public static function error(string $message, int $code = 400, array $errors = []): array
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        http_response_code($code);
        return $response;
    }

    /**
     * Create a paginated response
     */
    public static function paginated(array $data, int $total, int $page, int $size): array
    {
        return self::success([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'size' => $size,
                'pages' => (int) ceil($total / $size)
            ]
        ]);
    }

    /**
     * Create a created response (201)
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): array
    {
        return self::success($data, $message, 201);
    }

    /**
     * Create a no content response (204)
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /**
     * Create a not found response (404)
     */
    public static function notFound(string $message = 'Resource not found'): array
    {
        return self::error($message, 404);
    }

    /**
     * Create an unauthorized response (401)
     */
    public static function unauthorized(string $message = 'Unauthorized'): array
    {
        return self::error($message, 401);
    }

    /**
     * Create a forbidden response (403)
     */
    public static function forbidden(string $message = 'Forbidden'): array
    {
        return self::error($message, 403);
    }

    /**
     * Create a validation error response (422)
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): array
    {
        return self::error($message, 422, $errors);
    }
}
