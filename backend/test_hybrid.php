<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "🧪 PRUEBA SISTEMA HÍBRIDO - CORREGIDO\n";
echo "=====================================\n\n";

// Cargar entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$base_url = 'http://localhost:8000';

// 1. Health Check
echo "1. Health Check...\n";
$response = makeRequest($base_url . '/api/health', 'GET');
echo "   Status: " . $response['http_code'] . "\n";
echo "   Response: " . json_encode($response['data']) . "\n\n";

if ($response['http_code'] !== 200) {
    echo "❌ El servidor no responde correctamente\n";
    exit(1);
}

// 2. Login con admin - ✅ CORREGIDO: Usar el endpoint correcto
echo "2. Login con admin@cine.com...\n";
$login_data = [
    'email' => 'admin@cine.com',
    'password' => 'password'
];

$response = makeRequest($base_url . '/api/auth/login', 'POST', $login_data);
echo "   Status: " . $response['http_code'] . "\n";

if (isset($response['data']['error'])) {
    echo "   Error: " . $response['data']['error'] . "\n";
    
    // Debug adicional
    echo "   🔍 Debug adicional:\n";
    echo "   Endpoint probado: POST " . $base_url . "/api/auth/login\n";
    echo "   Método esperado: AuthController@loginAPI\n";
} else {
    echo "   ✅ Login exitoso!\n";
    echo "   Token: " . substr($response['data']['token'] ?? '', 0, 50) . "...\n";
    echo "   User ID: " . ($response['data']['user']['id'] ?? 'N/A') . "\n";
    
    // 3. Verificar token
    if (isset($response['data']['token'])) {
        echo "\n3. Verificando token...\n";
        $token = $response['data']['token'];
        $verify_response = makeRequest($base_url . '/api/auth/verify', 'GET', [], $token);
        echo "   Status: " . $verify_response['http_code'] . "\n";
        
        if (isset($verify_response['data']['valid']) && $verify_response['data']['valid']) {
            echo "   ✅ Token verificado correctamente\n";
        } else {
            echo "   ❌ Error verificando token: " . ($verify_response['data']['error'] ?? 'Unknown error') . "\n";
        }
    }
}

// 4. Test de Database directo
echo "\n4. Probando Database directamente...\n";
try {
    $db = App\Core\Database::getInstance();
    
    // Test queryOne
    $user = $db->queryOne("SELECT COUNT(*) as count FROM users");
    echo "   ✅ queryOne: " . ($user['count'] ?? 'N/A') . " usuarios\n";
    
    // Test exists
    $exists = $db->exists('users', ['email' => 'admin@cine.com']);
    echo "   ✅ exists: " . ($exists ? 'SÍ' : 'NO') . "\n";
    
    // Test insert (transaccional)
    $testEmail = 'test_' . time() . '@example.com';
    $userId = $db->transaction(function($db) use ($testEmail) {
        return $db->insert('users', [
            'email' => $testEmail,
            'password' => password_hash('temp123', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'role_id' => 7
        ]);
    });
    echo "   ✅ insert transaccional: ID $userId\n";
    
    // Cleanup
    $db->delete('users', ['email' => $testEmail]);
    echo "   ✅ cleanup: usuario temporal eliminado\n";
    
} catch (Exception $e) {
    echo "   ❌ Error en tests de Database: " . $e->getMessage() . "\n";
}

echo "\n🎉 Pruebas del sistema híbrido completadas\n";

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
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ];
    
    if ($token) {
        $options[CURLOPT_HTTPHEADER][] = "Authorization: Bearer $token";
    }
    
    if (($method === 'POST' || $method === 'PUT') && !empty($data)) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'http_code' => 0,
            'data' => ['error' => 'CURL Error: ' . $error]
        ];
    }
    
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'data' => json_decode($response, true) ?: $response
    ];
}
