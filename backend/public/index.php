<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Database;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configurar zona horaria
date_default_timezone_set('America/Caracas');

// Inicializar base de datos
try {
    Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Configurar rutas API
$router = new Router();

// ============================================================
// RUTAS PÚBLICAS
// ============================================================

// Health check
$router->add('GET', '/api/health', 'SystemController@health');

// Autenticación API (JWT) - ✅ CORREGIDO: Usar los nombres correctos
$router->add('POST', '/api/auth/login', 'AuthController@loginAPI');
$router->add('POST', '/api/auth/register', 'AuthController@registerAPI');
$router->add('GET', '/api/auth/verify', 'AuthController@verifyAPI', true); // Requiere auth

// ============================================================
// RUTAS PROTEGIDAS (EJEMPLOS)
// ============================================================

// Usuarios (solo admin y superadmin)
$router->add('GET', '/api/admin/users', 'UserController@index', true, [1, 2]);

// Películas (cualquier usuario autenticado puede ver, solo admin puede crear)
$router->add('GET', '/api/movies', 'MovieController@index', true);
$router->add('POST', '/api/movies', 'MovieController@create', true, [1, 2]);

// Manejar la solicitud
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error']);
}
