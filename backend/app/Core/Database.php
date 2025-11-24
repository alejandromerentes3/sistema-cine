<?php
namespace App\Core;

use PDO;
use PDOException;
use App\Core\AppLogger;

class Database {
    
    private static $instancia = null;
    private $conexion = null;
    private $logger;
    
    // ============================================================
    // 1. CONSTRUCTOR PRIVADO (SINGLETON) - CORREGIDO
    // ============================================================
    
    private function __construct() {
        $this->logger = AppLogger::getLogger('database');
        $this->connect();
    }
    
    // ============================================================
    // 2. SINGLETON - OBTENER INSTANCIA
    // ============================================================
    
    public static function getInstance() {
        if (self::$instancia === null) {
            self::$instancia = new Database();
        }
        return self::$instancia;
    }
    
    // ============================================================
    // 3. CONEXIÓN SEGURA CON PDO - CORREGIDO
    // ============================================================
    
    private function connect() {
        try {
            // ✅ CORREGIDO: Usar directamente las variables de entorno
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'cine_system';
            $username = $_ENV['DB_USER'] ?? 'cine_user';
            $password = $_ENV['DB_PASS'] ?? '';
            $port = $_ENV['DB_PORT'] ?? 3306;
            $charset = 'utf8mb4';
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
            
            $this->conexion = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
            ]);
            
            // Configuraciones de seguridad
            $this->conexion->exec("SET sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
            $this->conexion->exec("SET time_zone = '-04:00'");
            
            $this->logger->info('Conexión PDO a BD establecida', [
                'host' => $host,
                'database' => $dbname
            ]);
            
        } catch (PDOException $e) {
            $this->logger->error('Error de conexión PDO a BD', [
                'error' => $e->getMessage(),
                'dsn' => $dsn ?? 'No definido'
            ]);
            
            // Mensaje más descriptivo
            $errorMsg = "No se pudo conectar a la base de datos: " . $e->getMessage();
            throw new \Exception($errorMsg);
        }
    }
    
    // ... (el resto de los métodos se mantiene igual)
    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error('Error en query', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function queryOne(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Error en queryOne', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function executeQuery(string $sql, array $params = []) {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->error('Error en executeQuery', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function execute(string $sql, array $params = []): bool {
        try {
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logger->error('Error en execute', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($data);
            return (int) $this->conexion->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error('Error en insert', [
                'table' => $table,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function update(string $table, array $data, array $where): bool {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $whereClause = implode(' = ? AND ', array_keys($where)) . ' = ?';
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            $params = array_merge(array_values($data), array_values($where));
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logger->error('Error en update', [
                'table' => $table,
                'data' => $data,
                'where' => $where,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function delete(string $table, array $where): bool {
        $whereClause = implode(' = ? AND ', array_keys($where)) . ' = ?';
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute(array_values($where));
        } catch (PDOException $e) {
            $this->logger->error('Error en delete', [
                'table' => $table,
                'where' => $where,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function beginTransaction(): bool {
        return $this->conexion->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->conexion->commit();
    }
    
    public function rollback(): bool {
        return $this->conexion->rollBack();
    }
    
    public function transaction(callable $callback) {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            $this->logger->error('Transaction failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function exists(string $table, array $where): bool {
        $whereClause = implode(' = ? AND ', array_keys($where)) . ' = ?';
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$whereClause}";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute(array_values($where));
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->logger->error('Error en exists', [
                'table' => $table,
                'where' => $where,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getLastInsertId(): int {
        return (int) $this->conexion->lastInsertId();
    }
    
    public function rowCount(string $sql, array $params = []): int {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logger->error('Error en rowCount', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function isConnected(): bool {
        try {
            if ($this->conexion === null) {
                return false;
            }
            
            // ✅ CORREGIDO: Usar una consulta más simple y confiable
            $stmt = $this->conexion->query('SELECT 1');
            return $stmt !== false && $stmt->fetchColumn() == 1;
            
        } catch (PDOException $e) {
            $this->logger->warning('isConnected check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function getConnection(): PDO {
        return $this->conexion;
    }
    
    public function __destruct() {
        $this->conexion = null;
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
