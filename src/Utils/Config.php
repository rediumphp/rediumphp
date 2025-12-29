<?php

namespace Redium\Utils;

class Config
{
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * Load configuration from environment variables
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        // Common configuration mappings
        self::$config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'Redium App',
                'env' => $_ENV['ENV'] ?? 'production',
                'debug' => ($_ENV['ENV'] ?? 'production') === 'dev',
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? '',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? null,
                'issuer' => $_ENV['JWT_ISSUER'] ?? $_ENV['API_SERVICE'] ?? 'Redium',
                'audience' => $_ENV['JWT_AUDIENCE'] ?? $_ENV['SERVICE_HOST'] ?? 'localhost',
                'expiration' => (int) ($_ENV['JWT_EXPIRATION_HOURS'] ?? 8),
            ],
            'cors' => [
                'allowed_origins' => isset($_ENV['ALLOWED_URLS']) 
                    ? explode(',', $_ENV['ALLOWED_URLS']) 
                    : ['*'],
                'allowed_methods' => $_ENV['ALLOWED_METHODS'] ?? 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'allowed_headers' => $_ENV['ALLOWED_HEADERS'] ?? '*',
                'credentials' => filter_var($_ENV['CORS_CREDENTIALS'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ],
        ];

        self::$loaded = true;
    }

    /**
     * Get configuration value using dot notation
     * 
     * @param string $key Configuration key (e.g., 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Get all configuration
     * 
     * @return array All configuration
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * Check if running in development mode
     * 
     * @return bool True if in development mode
     */
    public static function isDev(): bool
    {
        return self::get('app.env') === 'dev';
    }

    /**
     * Check if running in production mode
     * 
     * @return bool True if in production mode
     */
    public static function isProduction(): bool
    {
        return self::get('app.env') === 'production';
    }
}
