<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$base_url = 'http://localhost:8000'; // Ajusta según tu configuración

echo "🧪 Iniciando pruebas de autenticación...\n\n";

// Test 1: Registro de usuario
echo "1. Probando registro de usuario...\n";
$register_data = [
    'email' => 'test' . time() . '@example.com',
    'password' => 'password123',
    'first_name' => 'Test',
    'last_name' => 'User',
    'phone' => '123456789'
];

$response = makeRequest($base_url . '/api/auth/register', 'POST', $register_data);
if ($response['http_code'] === 201) {
    echo "✅ Registro exitoso - User ID: " . ($response['data']['user_id'] ?? 'N/A') . "\n";
} else {
    echo "❌ Error en registro: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
}

// Test 2: Login
echo "\n2. Probando login...\n";
$login_data = [
    'email' => 'admin@cine.com',
    'password' => 'password' // La contraseña por defecto que pusimos
];

$response = makeRequest($base_url . '/api/auth/login', 'POST', $login_data);
if ($response['http_code'] === 200 && isset($response['data']['token'])) {
    $token = $response['data']['token'];
    echo "✅ Login exitoso - Token recibido\n";
    echo "   User: " . $response['data']['user']['email'] . "\n";
    echo "   Role: " . $response['data']['user']['role_id'] . "\n";
} else {
    echo "❌ Error en login: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

// Test 3: Verificar token
echo "\n3. Probando verificación de token...\n";
$response = makeRequest($base_url . '/api/auth/verify', 'GET', [], $token);
if ($response['http_code'] === 200) {
    echo "✅ Token válido\n";
} else {
    echo "❌ Token inválido: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
}

// Test 4: Health check
echo "\n4. Probando health check...\n";
$response = makeRequest($base_url . '/api/health', 'GET');
if ($response['http_code'] === 200) {
    echo "✅ Health check OK\n";
} else {
    echo "❌ Health check falló\n";
}

echo "\n🎉 Pruebas completadas\n";

function makeRequest($url, $method = 'GET', $data = [], $token = null) {
    $ch = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ];
    
    if ($token) {
        $options[CURLOPT_HTTPHEADER][] = "Authorization: Bearer $token";
    }
    
    if ($method === 'POST' && !empty($data)) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'data' => json_decode($response, true) ?: $response
    ];
}
