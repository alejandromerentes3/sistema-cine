<?php
namespace App\Models;

use App\Core\Database;
use App\Core\AppLogger;

class User {
    private $db;
    private $logger;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = AppLogger::getLogger('models');
    }

    public function findByEmail($email) {
        try {
            return $this->db->queryOne(
                "SELECT u.*, r.name as role_name 
                 FROM users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 WHERE u.email = ?", 
                [$email]
            );
        } catch (\Exception $e) {
            $this->logger->error('Error finding user by email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function findById($id) {
        try {
            return $this->db->queryOne(
                "SELECT u.*, r.name as role_name 
                 FROM users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ?", 
                [$id]
            );
        } catch (\Exception $e) {
            $this->logger->error('Error finding user by ID', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function create($data) {
        try {
            return $this->db->insert('users', $data);
        } catch (\Exception $e) {
            $this->logger->error('Error creating user', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function update($id, $data) {
        try {
            return $this->db->update('users', $data, ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Error updating user', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
