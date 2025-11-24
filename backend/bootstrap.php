<?php
declare(strict_types=1);

// Inicio seguro de sesión y cabeceras recomendadas
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24, // 1 día o ajustar
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax', // o 'Strict' si no usas cross-site
    ]);
    session_start();
}

// Cabeceras de seguridad
// Ajusta CSP según recursos reales del frontend en producción
header("Referrer-Policy: no-referrer-when-downgrade");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data:; connect-src 'self' https:");

// Manejo básico de excepciones no mostradas en producción
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    error_log((string)$e);
    if (getenv('APP_DEBUG') === 'true') {
        echo "Unhandled exception: " . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    } else {
        echo "Ha ocurrido un error. Contacte al administrador.";
    }
});

// Autoload si usas composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
