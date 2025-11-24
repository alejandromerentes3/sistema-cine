<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "🔧 DIAGNÓSTICO ESPECÍFICO DE DATABASE HÍBRIDO\n";
echo "=============================================\n\n";

// 1. Verificar .env
echo "1. Verificando archivo .env...\n";
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$envVars = [
    'DB_HOST' => $_ENV['DB_HOST'] ?? 'NO DEFINIDO',
    'DB_NAME' => $_ENV['DB_NAME'] ?? 'NO DEFINIDO', 
    'DB_USER' => $_ENV['DB_USER'] ?? 'NO DEFINIDO',
    'DB_PASS' => $_ENV['DB_PASS'] ? '***' . substr($_ENV['DB_PASS'], -3) : 'NO DEFINIDO',
    'DB_PORT' => $_ENV['DB_PORT'] ?? '3306'
];

foreach ($envVars as $var => $value) {
    echo "   $var = $value\n";
}

// 2. Probar conexión PDO directamente
echo "\n2. Probando conexión PDO directa...\n";
try {
    $dsn = "mysql:host={$envVars['DB_HOST']};port={$envVars['DB_PORT']};dbname={$envVars['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $envVars['DB_USER'], $_ENV['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "   ✅ Conexión PDO directa EXITOSA\n";
    
    // Verificar tablas
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    echo "   ✅ Tabla 'users': " . ($stmt->rowCount() > 0 ? "EXISTE" : "NO EXISTE") . "\n";
    
} catch (PDOException $e) {
    echo "   ❌ Error PDO directo: " . $e->getMessage() . "\n";
}

// 3. Probar nuestro Database class
echo "\n3. Probando Database class híbrida...\n";
try {
    $db = App\Core\Database::getInstance();
    echo "   ✅ Instancia de Database creada\n";
    
    if ($db->isConnected()) {
        echo "   ✅ Database->isConnected() = TRUE\n";
        
        // Probar consulta simple
        $result = $db->queryOne("SELECT COUNT(*) as count FROM users");
        echo "   ✅ Consulta simple: " . ($result['count'] ?? 'N/A') . " usuarios\n";
    } else {
        echo "   ❌ Database->isConnected() = FALSE\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error en Database class: " . $e->getMessage() . "\n";
    echo "   💡 Posible causa: Las variables en Database.php no coinciden con .env\n";
}

echo "\n🎯 Diagnóstico completado\n";
