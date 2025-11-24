<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "🔍 Verificando configuración del sistema...\n\n";

// 1. Verificar variables de entorno
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✅ Variables de entorno cargadas\n";
} catch (Exception $e) {
    echo "❌ Error cargando .env: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar PHP
echo "📋 PHP Version: " . PHP_VERSION . "\n";
echo "📋 Extensions: " . implode(', ', get_loaded_extensions()) . "\n\n";

// 3. Verificar base de datos
try {
    $db = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "✅ Conexión a BD exitosa\n";
    
    // Verificar tablas
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    echo $stmt->rowCount() > 0 ? "✅ Tabla 'users' existe\n" : "❌ Tabla 'users' no existe\n";
    
} catch (PDOException $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "\n";
}

// 4. Verificar directorios
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
        echo "✅ Directorio $dir existe\n";
    }
}

echo "\n🎯 Verificación completada\n";
