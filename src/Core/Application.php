<?php

namespace Redium\Core;

use Slim\App;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Redium\Utils\Config;


class Application
{
    private App $app;
    private array $controllers = [];
    private string $basePath;
    private string $apiVersion = 'v1';

    public function __construct(?string $basePath)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
        $this->loadEnvironment();
        $this->setupCors();
        $this->app = AppFactory::create();
        Config::load();
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnvironment(): void
    {
        if (file_exists($this->basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($this->basePath);
            $dotenv->load();
        }
    }

    /**
     * Setup CORS headers
     */
    private function setupCors(): void
    {
        $allowedOrigins = Config::get('cors.allowed_origins', ['*']);
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        $env = $_ENV['ENV'] ?? 'production';

        if ($env === 'dev') {
            header("Access-Control-Allow-Origin: *");
        } else {
            if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
                header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
            } else {
                http_response_code(401);
                exit(0);
            }
        }

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Credentials: ' . (Config::get('cors.credentials') ? 'true' : 'false'));
            header('Access-Control-Max-Age: 86400');
        }

        header("Access-Control-Allow-Methods: " . Config::get('cors.allowed_methods'));
        header("Access-Control-Allow-Headers: " . Config::get('cors.allowed_headers'));

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

        // Set content type for non-dev environments
        if ($env !== 'dev') {
            header('Content-Type: application/json');
        }
    }

    /**
     * Register a controller
     * 
     * @param string $controllerClass Fully qualified controller class name
     * @return self
     */
    public function registerController(string $controllerClass): self
    {
        $this->controllers[] = $controllerClass;
        return $this;
    }

    /**
     * Register multiple controllers
     * 
     * @param array $controllers Array of controller class names
     * @return self
     */
    public function registerControllers(array $controllers): self
    {
        foreach ($controllers as $controller) {
            $this->registerController($controller);
        }
        return $this;
    }

    /**
     * Automatically register all controllers from a directory
     * 
     * @param string $directory Path to controllers directory
     * @param string $namespacePrefix Namespace prefix for controllers
     * @return self
     */
    public function registerControllersFromDirectory(string $directory, string $namespacePrefix = 'App\\Controllers'): self
    {
        if (!is_dir($directory)) {
            return $this;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.php')) {
                continue;
            }

            $className = $namespacePrefix . '\\' . str_replace('.php', '', $file);
            if (class_exists($className)) {
                $this->registerController($className);
            }
        }

        return $this;
    }

    /**
     * Set API version prefix (default: 'v1')
     * 
     * @param string $version API version
     * @return self
     */
    public function setApiVersion(string $version): self
    {
        $this->apiVersion = $version;
        return $this;
    }

    /**
     * Get the Slim application instance
     * 
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            // Register all controllers
            $router = new Router($this->app, $this->apiVersion);
            foreach ($this->controllers as $controller) {
                $router->registerController($controller);
            }

            // Run the Slim app
            $this->app->run();
        } catch (\Throwable $e) {
            ExceptionHandler::handle($e);
        }
    }
}
