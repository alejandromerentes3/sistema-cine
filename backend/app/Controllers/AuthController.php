<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\JWTAuth;
use App\Core\AppLogger;
use App\Services\ValidationService;

class AuthController {
    private $db;
    private $logger;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = AppLogger::getLogger('auth');
    }

    /**
     * LOGIN API (Para React - JWT) - ✅ MÉTODO PRINCIPAL
     */
    public function loginAPI() {
        $input = $this->getJsonInput();
        
        $this->logger->info('API Login attempt', ['email' => $input['email'] ?? 'none']);

        // Validación usando ValidationService
        $validation = ValidationService::validate($input, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!$validation['success']) {
            $this->sendJsonError($validation['firstError'], 400);
        }

        // Buscar usuario usando queryOne del nuevo Database
        $user = $this->db->queryOne(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ? AND u.is_active = 1", 
            [$input['email']]
        );
        
        if (!$user || !password_verify($input['password'], $user['password'])) {
            $this->logger->warning('Failed API login attempt', ['email' => $input['email']]);
            $this->sendJsonError('Invalid credentials', 401);
        }

        // Generar token JWT
        $token = JWTAuth::generateToken($user['id'], [
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'branch_id' => $user['branch_id']
        ]);

        $this->logger->info('API login successful', ['user_id' => $user['id']]);

        // No devolver la contraseña
        unset($user['password']);

        $this->sendJsonResponse([
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * REGISTER API (Para React - JWT)
     */
    public function registerAPI() {
        $input = $this->getJsonInput();

        $this->logger->info('API Register attempt', ['email' => $input['email'] ?? 'none']);

        try {
            // Validar campos requeridos
            $required = ['email', 'password', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new \Exception("Field $field is required");
                }
            }

            // Validar email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email format');
            }

            // Verificar si el email ya existe usando el nuevo método exists
            if ($this->db->exists('users', ['email' => $input['email']])) {
                throw new \Exception('Email already registered');
            }

            // Validar seguridad de contraseña usando ValidationService
            $passwordValidation = ValidationService::validatePasswordSecurity($input['password']);
            if (!$passwordValidation['success']) {
                throw new \Exception($passwordValidation['message']);
            }

            // Usar transacción para asegurar consistencia
            $userId = $this->db->transaction(function($db) use ($input) {
                return $db->insert('users', [
                    'email' => $input['email'],
                    'password' => password_hash($input['password'], PASSWORD_DEFAULT),
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'],
                    'phone' => $input['phone'] ?? null,
                    'role_id' => 7 // Rol cliente
                ]);
            });

            $this->logger->info('API registration successful', ['user_id' => $userId]);

            // Generar token automáticamente después del registro
            $token = JWTAuth::generateToken($userId, [
                'email' => $input['email'],
                'role_id' => 7,
                'branch_id' => null
            ]);

            $this->sendJsonResponse([
                'message' => 'User registered successfully',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'email' => $input['email'],
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'],
                    'role_id' => 7,
                    'branch_id' => null
                ]
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('API registration failed', [
                'email' => $input['email'] ?? 'none',
                'error' => $e->getMessage()
            ]);
            $this->sendJsonError($e->getMessage(), 400);
        }
    }

    /**
     * VERIFY TOKEN API
     */
    public function verifyAPI() {
        try {
            $token = JWTAuth::getBearerToken();
            
            if (!$token) {
                $this->sendJsonError('Token required', 401);
            }

            $userData = JWTAuth::validateToken($token);
            
            if (!$userData) {
                $this->sendJsonError('Invalid token', 401);
            }

            // Obtener datos actualizados del usuario usando queryOne
            $user = $this->db->queryOne(
                "SELECT u.*, r.name as role_name 
                 FROM users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ? AND u.is_active = 1", 
                [$userData['user_id']]
            );

            if (!$user) {
                $this->sendJsonError('User not found', 404);
            }

            unset($user['password']);

            $this->sendJsonResponse([
                'valid' => true,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            $this->logger->warning('Token verification failed', ['error' => $e->getMessage()]);
            $this->sendJsonError('Invalid token', 401);
        }
    }

    /**
     * MÉTODO LEGACY: login (para compatibilidad si es necesario)
     * Solo si necesitas mantener rutas antiguas temporalmente
     */
    public function login() {
        // Redirigir al método API o mostrar error
        $this->sendJsonError('Use /api/auth/login with POST method', 400);
    }

    /**
     * MÉTODOS AUXILIARES
     */
    private function getJsonInput() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendJsonError('Invalid JSON input', 400);
        }

        return $input;
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function sendJsonError($message, $statusCode = 400) {
        $this->sendJsonResponse(['error' => $message], $statusCode);
    }
}
