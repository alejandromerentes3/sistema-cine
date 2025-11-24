<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\AppLogger;

class SystemController {
    private $db;
    private $logger;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = AppLogger::getLogger('system');
    }

    public function health() {
        try {
            // Verificar base de datos mediante consulta directa (más confiable)
            $dbStatus = 'connected';
            $dbError = null;
            $userCount = 0;
            
            try {
                $result = $this->db->queryOne("SELECT COUNT(*) as user_count FROM users");
                $userCount = $result['user_count'] ?? 0;
                $dbStatus = 'connected';
            } catch (\Exception $e) {
                $dbStatus = 'error';
                $dbError = $e->getMessage();
            }

            // Verificar directorios
            $storageDirs = [
                'uploads' => is_writable(__DIR__ . '/../../storage/uploads'),
                'qrcodes' => is_writable(__DIR__ . '/../../storage/qrcodes'),
                'logs' => is_writable(__DIR__ . '/../../logs')
            ];

            $status = 'healthy';
            $issues = [];

            if ($dbStatus !== 'connected') {
                $status = 'unhealthy';
                $issues[] = "Database: " . ($dbError ?? 'Connection failed');
            }

            foreach ($storageDirs as $dir => $writable) {
                if (!$writable) {
                    $status = $status === 'healthy' ? 'degraded' : $status;
                    $issues[] = "Storage: Directory $dir not writable";
                }
            }

            $this->logger->info('Health check performed', [
                'status' => $status,
                'db_status' => $dbStatus,
                'user_count' => $userCount
            ]);

            $responseCode = $status === 'healthy' ? 200 : ($status === 'degraded' ? 200 : 503);
            http_response_code($responseCode);
            
            echo json_encode([
                'status' => $status,
                'timestamp' => date('c'),
                'service' => 'Cine System API',
                'version' => '1.0.0',
                'database' => [
                    'status' => $dbStatus,
                    'user_count' => $userCount,
                    'error' => $dbError
                ],
                'storage' => $storageDirs,
                'issues' => $issues
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Health check failed completely', ['error' => $e->getMessage()]);
            
            http_response_code(503);
            echo json_encode([
                'status' => 'unhealthy',
                'error' => 'Health check system failure: ' . $e->getMessage(),
                'timestamp' => date('c')
            ]);
        }
    }
}
