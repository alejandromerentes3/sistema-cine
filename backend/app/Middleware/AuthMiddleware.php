<?php
namespace App\Middleware;

use App\Core\JWTAuth;
use App\Core\AppLogger;

class AuthMiddleware {
    private $logger;

    public function __construct() {
        $this->logger = AppLogger::getLogger('auth');
    }

    public function handle($requiredRole = null) {
        $token = JWTAuth::getBearerToken();
        
        if (!$token) {
            $this->logger->warning('Access attempt without token');
            $this->sendUnauthorized('Token required');
        }

        $userData = JWTAuth::validateToken($token);
        
        if (!$userData) {
            $this->logger->warning('Invalid token attempt', ['token' => substr($token, 0, 20) . '...']);
            $this->sendUnauthorized('Invalid token');
        }

        // Verificar rol si se requiere
        if ($requiredRole && $userData['role_id'] != $requiredRole) {
            $this->logger->warning('Insufficient permissions', [
                'user_id' => $userData['user_id'],
                'required_role' => $requiredRole,
                'user_role' => $userData['role_id']
            ]);
            $this->sendForbidden('Insufficient permissions');
        }

        return $userData;
    }

    private function sendUnauthorized($message) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    private function sendForbidden($message) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
