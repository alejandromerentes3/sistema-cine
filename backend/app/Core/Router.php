<?php
namespace App\Core;

use App\Middleware\AuthMiddleware;

class Router {
    private $routes = [];
    private $authMiddleware;

    public function __construct() {
        $this->authMiddleware = new AuthMiddleware();
    }

    public function add($method, $path, $handler, $requireAuth = false, $requiredRoles = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'requireAuth' => $requireAuth,
            'requiredRoles' => is_array($requiredRoles) ? $requiredRoles : [$requiredRoles]
        ];
    }

    public function dispatch($method, $uri) {
        $method = strtoupper($method);
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH));

        // Headers CORS para desarrollo
        header('Access-Control-Allow-Origin: http://localhost:3000');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        // Manejar preflight OPTIONS
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                $userData = null;

                // Verificar autenticación si es requerida
                if ($route['requireAuth']) {
                    $userData = $this->authMiddleware->handle($route['requiredRoles']);
                }

                return $this->callHandler($route['handler'], $userData);
            }
        }

        // 404 - No encontrado
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Endpoint not found']);
        exit;
    }

    private function matchRoute($route, $method, $path) {
        if ($route['method'] !== $method) {
            return false;
        }

        // Convertir parámetros de ruta {id} a regex
        $pattern = preg_replace('/\{([a-z]+)\}/', '([^/]+)', $route['path']);
        $pattern = "#^$pattern$#";

        return preg_match($pattern, $path) === 1;
    }

    private function callHandler($handler, $userData = null) {
        list($controllerName, $methodName) = explode('@', $handler);

        $controllerClass = "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller $controllerClass not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new \Exception("Method $methodName not found in $controllerClass");
        }

        // Inyectar datos de usuario si están disponibles
        if ($userData && method_exists($controller, 'setUserData')) {
            $controller->setUserData($userData);
        }

        call_user_func([$controller, $methodName]);
    }

    private function normalizePath($path) {
        return '/' . trim($path, '/');
    }
}
