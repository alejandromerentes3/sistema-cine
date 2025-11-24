<?php
namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTAuth {
    private static $secretKey;
    private static $algorithm = 'HS256';

    public static function init() {
        self::$secretKey = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
        if (empty(self::$secretKey)) {
            throw new Exception('JWT secret key not configured');
        }
    }

    public static function generateToken($userId, $userData = []) {
        self::init();
        
        $issuedAt = time();
        $expire = $issuedAt + ($_ENV['JWT_EXPIRE'] ?? 86400);

        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => [
                'user_id' => $userId,
                'email' => $userData['email'] ?? '',
                'role_id' => $userData['role_id'] ?? '',
                'branch_id' => $userData['branch_id'] ?? null
            ]
        ];

        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }

    public static function validateToken($token) {
        self::init();
        
        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return (array) $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getBearerToken() {
        $headers = apache_request_headers();
        
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}
