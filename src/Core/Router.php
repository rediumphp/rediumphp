<?php

namespace Redium\Core;

use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use ReflectionClass;
use ReflectionException;
use Redium\Attributes\Route;
use Redium\Auth\AuthService;
use Redium\Auth\Exceptions\UnauthenticatedException;
use Redium\Auth\Exceptions\UnauthorizedException;

class Router
{
    private App $app;
    private string $apiVersion;
    private AuthService $authService;

    public function __construct(App $app, string $apiVersion = 'v1')
    {
        $this->app = $app;
        $this->apiVersion = $apiVersion;
        $this->authService = new AuthService();
    }

    /**
     * Register a controller and its routes
     * 
     * @param string $controllerClass Fully qualified controller class name
     * @throws ReflectionException
     */
    public function registerController(string $controllerClass): void
    {
        $class = new ReflectionClass($controllerClass);

        // Get controller-level route prefix
        $prefix = "";
        $attributes = $class->getAttributes(Route::class);
        if (!empty($attributes)) {
            $prefix = $attributes[0]->newInstance()->getPath();
        }

        // Register each method with Route attribute
        foreach ($class->getMethods() as $method) {
            $routeAttributes = $method->getAttributes(Route::class);
            
            if (empty($routeAttributes)) {
                continue;
            }

            foreach ($routeAttributes as $routeAttribute) {
                /** @var Route $route */
                $route = $routeAttribute->newInstance();

                $httpMethod = $route->getMethod();
                $fullPath = "/{$this->apiVersion}" . $prefix . $route->getPath();

                // Register route with Slim
                $this->app->$httpMethod($fullPath, function (Request $request, Response $response, array $args) 
                    use ($method, $controllerClass, $route) {
                    
                    // Handle authentication and permissions
                    $permission = $route->getPermission();
                    
                    if ($permission !== "none") {
                        $this->handleAuthentication($request, $permission);
                    }

                    // Parse request body
                    $body = (array) json_decode($request->getBody()->getContents(), true);
                    $queryParams = self::getUrlParams($request->getServerParams()['REQUEST_URI'] ?? '');

                    // Build method parameters
                    $params = $this->buildMethodParameters($method, $request, $response, $args, $body, $queryParams);

                    // Call controller method
                    $controllerInstance = new $controllerClass();
                    $result = call_user_func_array([$controllerInstance, $method->getName()], $params);

                    // Write response
                    if ($result !== null) {
                        $response->getBody()->write(writeBody($result));
                    }

                    return $response;
                });
            }
        }
    }

    /**
     * Parse URL query parameters
     * 
     * @param string $url URL with query string
     * @return array Associative array of parameters
     */
    public static function getUrlParams(string $url): array
    {
        $urlParts = explode("?", $url);
        $params = [];

        if (isset($urlParts[1])) {
            $queryParams = explode("&", $urlParts[1]);
            foreach ($queryParams as $param) {
                $parts = explode("=", $param);
                if (count($parts) === 2) {
                    $params[$parts[0]] = urldecode($parts[1]);
                }
            }
        }

        return $params;
    }

    /**
     * Handle authentication and permission checking
     * 
     * @param Request $request
     * @param string $requiredPermission
     * @throws UnauthenticatedException
     * @throws UnauthorizedException
     */
    private function handleAuthentication(Request $request, string $requiredPermission): void
    {
        $headers = $request->getHeaders();
        
        if (!isset($headers['Authorization'])) {
            throw new UnauthenticatedException("No authorization token provided");
        }

        $authHeader = $headers['Authorization'][0] ?? '';
        
        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new UnauthenticatedException("Invalid authorization header format");
        }

        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix
        $auth = $this->authService->validateToken($token);

        if (!$auth) {
            throw new UnauthenticatedException("Invalid or expired token");
        }

        // Check permissions
        $userPermissions = $auth['permissions'] ?? [];
        
        if (!$this->authService->hasPermission($userPermissions, $requiredPermission)) {
            throw new UnauthorizedException("You don't have permission to perform this action");
        }
    }

    /**
     * Build method parameters from request data
     * 
     * @param \ReflectionMethod $method
     * @param Request $request
     * @param Response $response
     * @param array $args Path parameters
     * @param array $body Request body
     * @param array $queryParams Query parameters
     * @return array
     */
    private function buildMethodParameters(
        \ReflectionMethod $method,
        Request $request,
        Response $response,
        array $args,
        array $body,
        array $queryParams
    ): array {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();

            // Special parameter injection
            $value = match ($paramName) {
                "request" => $request,
                "response" => $response,
                default => $body[$paramName] 
                    ?? $args[$paramName] 
                    ?? $queryParams[$paramName] 
                    ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null),
            };

            $params[] = $value;
        }

        return $params;
    }
}
