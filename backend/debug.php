<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "🐛 Iniciando diagnóstico del sistema...\n\n";

// 1. Verificar .env
echo "1. Verificando archivo .env...\n";
if (!file_exists(__DIR__ . '/.env')) {
    echo "❌ Archivo .env no encontrado\n";
    exit(1);
}
echo "✅ Archivo .env encontrado\n";

// 2. Cargar variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✅ Variables de entorno cargadas\n";
} catch (Exception $e) {
    echo "❌ Error cargando .env: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Verificar variables críticas
$requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET'];
foreach ($requiredVars as $var) {
    if (empty($_ENV[$var])) {
        echo "❌ Variable requerida faltante: $var\n";
    } else {
        echo "✅ $var = " . ($var === 'DB_PASS' ? '***' : $_ENV[$var]) . "\n";
    }
}

// 4. Verificar conexión a BD
echo "\n2. Verificando conexión a base de datos...\n";
try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
    echo "✅ Conexión a BD exitosa\n";
    
    // Verificar tablas esenciales
    $tables = ['users', 'roles', 'currencies'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$table' existe\n";
        } else {
            echo "❌ Tabla '$table' NO existe\n";
        }
    }
    
    // Verificar usuario admin
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE email = 'admin@cine.com'");
    $result = $stmt->fetch();
    echo "✅ Usuarios en BD: " . $result['count'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión a BD: " . $e->getMessage() . "\n";
    echo "💡 Sugerencias:\n";
    echo "   - Verifica que MySQL esté ejecutándose\n";
    echo "   - Verifica el nombre de la BD: {$_ENV['DB_NAME']}\n";
    echo "   - Verifica usuario/contraseña\n";
}

// 5. Verificar directorios
echo "\n3. Verificando directorios...\n";
$dirs = [
    'storage/uploads' => 'rw',
    'storage/qrcodes' => 'rw',
    'logs' => 'rw'
];

foreach ($dirs as $dir => $perms) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        echo "❌ Directorio $dir no existe\n";
    } else {
        $writable = is_writable($path);
        echo ($writable ? "✅" : "❌") . " Directorio $dir " . ($writable ? "escribible" : "NO escribible") . "\n";
    }
}

echo "\n🎯 Diagnóstico completado\n";
