<?php

namespace Redium\Core;

use Throwable;
use Redium\Exceptions\HttpException;
use Redium\Utils\Config;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use Redium\Validation\ValidationException;
use Redium\Auth\Exceptions\UnauthenticatedException;
use Redium\Auth\Exceptions\UnauthorizedException;
use PDOException;
use TypeError;

class ExceptionHandler
{
    public static function handle(Throwable $e): void
    {
        $code = 500;
        $title = "Server Error";
        $message = $e->getMessage();
        $errors = [];

        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();
            $title = "Request Error";
            $errors = $e->getErrors();
        } elseif ($e instanceof HttpNotFoundException) {
            $code = 404;
            $title = "Not Found";
        } elseif ($e instanceof HttpMethodNotAllowedException) {
            $code = 405;
            $title = "Method Not Allowed";
            $errors = ["Allowed methods: " . implode(', ', $e->getAllowedMethods())];
        } elseif ($e instanceof ValidationException) {
            $code = $e->getCode() ?: 422;
            $title = "Validation Error";
            $errors = $e->getErrors();
        } elseif ($e instanceof UnauthenticatedException) {
            $code = $e->getCode() ?: 401;
            $title = "Authentication Required";
        } elseif ($e instanceof UnauthorizedException) {
            $code = $e->getCode() ?: 403;
            $title = "Access Denied";
        } elseif ($e instanceof PDOException) {
            $code = 500;
            $title = "Database Error";
            if (!Config::isDev()) {
                $message = "A database error occurred.";
            }
        } elseif ($e instanceof TypeError) {
            $code = 500;
            $title = "Type Error";
        }

        self::renderResponse($code, $title, $message, $errors, $e);
    }

    private static function renderResponse(int $code, string $title, string $message, array $errors, Throwable $e): void
    {
        header("Content-Type: application/json", true, $code);
        
        $response = [
            "success" => false,
            "status" => $code,
            "error" => [
                "title" => $title,
                "message" => $message
            ]
        ];

        if (!empty($errors)) {
            $response["error"]["details"] = $errors;
        }

        if (Config::isDev()) {
            $response["debug"] = [
                "exception" => get_class($e),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => array_slice($e->getTrace(), 0, 5) // Limit trace
            ];
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
