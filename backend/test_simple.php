<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

echo "🧪 PRUEBA SIMPLE DEL SISTEMA\n";
echo "============================\n\n";

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

// 2. Login con admin
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
    
    // Verificar usuario en BD
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=cine_system1", 
            "cine_user1", 
            "Hqwdirnp9h-8$75"
        );
        
        $stmt = $pdo->query("SELECT id, email, password, role_id FROM users WHERE email = 'admin@cine.com'");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "   ✅ Usuario encontrado en BD:\n";
            echo "      ID: " . $user['id'] . "\n";
            echo "      Email: " . $user['email'] . "\n";
            echo "      Role: " . $user['role_id'] . "\n";
            echo "      Password hash: " . substr($user['password'], 0, 20) . "...\n";
            
            // Verificar contraseña
            $isValid = password_verify('password', $user['password']);
            echo "      ¿Password 'password' válida? " . ($isValid ? "✅ SÍ" : "❌ NO") . "\n";
            
            if (!$isValid) {
                echo "   💡 SOLUCIÓN: Actualizar contraseña del admin\n";
                $newHash = password_hash('password', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@cine.com'");
                $stmt->execute([$newHash]);
                echo "   🔄 Contraseña actualizada. Intenta login nuevamente.\n";
            }
        } else {
            echo "   ❌ Usuario admin@cine.com NO encontrado en BD\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error BD: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✅ Login exitoso!\n";
    echo "   Token: " . substr($response['data']['token'] ?? '', 0, 50) . "...\n";
}

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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
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
