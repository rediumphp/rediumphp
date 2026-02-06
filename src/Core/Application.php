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

    public function __construct(private ?string $basePath, private string $prefix = "api")
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
        $this->prefix = $prefix;
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

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

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
     * Automatically register all controllers from a directory
     * 
     * @return self
     */
    public function registerControllers(): self
    {
        $directory = $this->basePath . '/src';
        if (!is_dir($directory)) {
            return $this;
        }

        $ctrls = $this->scanDirectoryControllers($directory);

        foreach ($ctrls as $ctrl) {
            $parts = explode("/", $ctrl);
            $index = array_key_last($parts);
            $class = $parts[$index];
            $className = $this->getControllerNamespace($ctrl) . '\\' . str_replace('.php', '', $class);

            $this->registerController($className);
        }

        return $this;
    }

    private function scanDirectoryControllers($directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $items = scandir($directory);
        $controllers = [];
        foreach ($items as $item) {
            $path = str_replace("\\", "/", $directory . '/' . $item);

            if ($item === '.' || $item === '..') {
                continue;
            } elseif (is_dir($path)) {
                array_push($controllers, ...$this->scanDirectoryControllers($path));
            } elseif (!str_ends_with($item, '.php')) {
                continue;
            } elseif ($this->isController($path)) {

                array_push($controllers, $path);
            }
        }

        return $controllers;
    }

    /**
     * Check if a file is a valid controller
     * 
     * @param string $path Path to the PHP file
     * @return bool
     */
    private function isController(string $path): bool
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $pattern = '/class\s+\w+\s+extends\s+(?:\\\\Redium\\\\Core\\\\)?Controller\b/i';

        return (bool)preg_match($pattern, $content);
    }

    private function getControllerNamespace(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        $pattern = '/namespace\s+([\\\\\w]+)/i';

        preg_match($pattern, $content, $matches);

        return $matches[1] ?? '';
    }

    /**
     * Set API version prefix (default: 'api')
     * 
     * @param string $prefix API version
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
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
            $router = new Router($this->app);
            foreach ($this->controllers as $controller) {
                $router->registerController($controller);
            }

            $this->app->run();
        } catch (\Throwable $e) {
            ExceptionHandler::handle($e);
        }
    }
}
