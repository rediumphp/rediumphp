<?php

/**
 * Redium Framework - Utility Helper Functions
 * 
 * These functions are automatically loaded via composer.json files configuration
 */

use Ramsey\Uuid\Uuid;

if (!function_exists('writeBody')) {
    /**
     * Format data as JSON response
     * 
     * @param mixed $data Data to encode
     * @param bool $pretty Pretty print JSON (default: false)
     * @return string|null JSON string or null on error
     */
    function writeBody(mixed $data, bool $pretty = false): ?string
    {
        $options = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE;
        $result = json_encode($data, $options);
        return $result ?: null;
    }
}

if (!function_exists('throwError')) {
    /**
     * Send standardized error response and exit
     * 
     * @param int $code HTTP status code
     * @param string $title Error title
     * @param string $message Error message
     * @param array $debug Additional debug information
     * @param array $trace Stack trace (for development)
     */
    function throwError(
        int $code,
        string $title,
        string $message = "",
        array $debug = [],
        array $trace = []
    ): void {
        header("HTTP/1.1 {$code} {$title}");

        $response = [
            "code" => $code,
            "error" => $title,
            "message" => $message,
        ];

        if (!empty($debug)) {
            $response["debug"] = $debug;
        }

        if (!empty($trace) && ($_ENV['ENV'] ?? 'production') === 'dev') {
            $response["trace"] = $trace;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('generateUniqueIdentifier')) {
    /**
     * Generate unique identifier from UUID
     * 
     * @param int $length Length of identifier (default: 10)
     * @return string Unique identifier
     */
    function generateUniqueIdentifier(int $length = 10): string
    {
        $uuid = Uuid::uuid4()->toString();
        return substr(str_replace('-', '', $uuid), 0, $length);
    }
}

if (!function_exists('generateRandomSerialNumber')) {
    /**
     * Generate random alphanumeric serial number
     * 
     * @param int $length Length of serial number (default: 6)
     * @return string Random serial number
     */
    function generateRandomSerialNumber(int $length = 6): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $serial = '';

        for ($i = 0; $i < $length; $i++) {
            $serial .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $serial;
    }
}

if (!function_exists('password_encrypt')) {
    /**
     * Encrypt password using modern Argon2id hash
     * 
     * @param string $password Plain text password
     * @return string Encrypted password hash
     */
    function password_encrypt(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
}

if (!function_exists('password_verify_custom')) {
    /**
     * Verify password against Argon2id hash
     * 
     * @param string $password Plain text password
     * @param string $hash Password hash
     * @return bool True if password matches
     */
    function password_verify_custom(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

if (!function_exists('getTextInput')) {
    /**
     * Sanitize text input to prevent XSS
     * 
     * @param string $input Raw input string
     * @return string Sanitized string
     */
    function getTextInput(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getIpAddress')) {
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    function getIpAddress(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
}


if (!function_exists('var_die')) {
    /**
     * 
     * @return void
     */
    function var_die(mixed ...$values): void
    {
        var_dump(...$values);
        die();
    }
}
