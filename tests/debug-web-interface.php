<?php
/**
 * Debug Web Interface - Check what's happening in the web calls
 */

// Use same includes as web interface
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Helpers.php';
require_once __DIR__ . '/../src/Api/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../src/Api/EvolutionAPI/Instances.php';
require_once __DIR__ . '/../src/Web/Models/WhatsAppInstance.php';

$config = require_once __DIR__ . '/../config/config.php';

echo "🔍 Debug Web Interface API Calls\n";
echo "=================================\n\n";

echo "Config loaded:\n";
echo "  API URL: " . $config['evolutionAPI']['api_url'] . "\n";
echo "  API Key: " . substr($config['evolutionAPI']['api_key'], 0, 8) . "...\n\n";

// Test 1: Direct EvolutionAPI test (same as web interface)
echo "1️⃣ Testing EvolutionAPI class directly...\n";
try {
    $api = new EvolutionAPI(
        $config['evolutionAPI']['api_url'],
        $config['evolutionAPI']['api_key'],
        'test-web-debug'
    );
    
    echo "   API object created successfully\n";
    echo "   Server URL: " . $api->getServerUrl() . "\n";
    
    $apiTest = $api->getInformation();
    echo "   getInformation() result:\n";
    echo "     Success: " . ($apiTest['success'] ? 'YES' : 'NO') . "\n";
    echo "     HTTP Code: " . ($apiTest['http_code'] ?? 'N/A') . "\n";
    echo "     Error: " . ($apiTest['error'] ?? 'None') . "\n";
    echo "     Raw Response: " . substr($apiTest['raw_response'] ?? '', 0, 100) . "...\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n\n";
}

// Test 2: WhatsAppInstance class test
echo "2️⃣ Testing WhatsAppInstance class...\n";
try {
    $whatsappInstance = new WhatsAppInstance();
    echo "   WhatsAppInstance created successfully\n";
    
    // Test instance creation (but don't actually create)
    echo "   Testing create method call structure...\n";
    
    // We'll just test the API initialization part
    $testApi = new EvolutionAPI(
        $config['evolutionAPI']['api_url'],
        $config['evolutionAPI']['api_key'],
        'test-instance-debug'
    );
    
    $instances = new Instances($testApi);
    echo "   Instances class created successfully\n";
    
    // Test createBasicInstance endpoint construction
    echo "   Testing createBasicInstance endpoint...\n";
    
    // Instead of actually creating, let's see what URL would be constructed
    $testInstanceName = 'debug-test-url-check';
    
    echo "     Would call: createBasicInstance('$testInstanceName')\n";
    echo "     This calls: createInstance() with basic options\n";
    echo "     Which makes request to: instance/create\n";
    echo "     Full URL would be: " . $testApi->getServerUrl() . "/instance/create\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n\n";
}

// Test 3: Direct endpoint test
echo "3️⃣ Testing instance/create endpoint directly...\n";
$testUrl = $config['evolutionAPI']['api_url'] . '/instance/create';
echo "   Testing URL: $testUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "apikey: " . $config['evolutionAPI']['api_key']
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'instanceName' => 'debug-direct-test-' . time(),
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   cURL Error: " . ($error ?: 'None') . "\n";
echo "   Response: " . substr($response, 0, 200) . "...\n\n";

if ($httpCode === 201 || $httpCode === 200) {
    echo "✅ Direct endpoint test WORKED!\n";
    echo "💡 The issue is in the class implementation, not the endpoint\n";
} else {
    echo "❌ Direct endpoint test FAILED\n";
    echo "💡 The endpoint itself has issues\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Debug Complete\n";
echo "\nNext steps:\n";
echo "1. If direct endpoint worked: Fix class implementation\n";
echo "2. If direct endpoint failed: Check Evolution API configuration\n";
echo "3. Check if your Evolution API supports instance creation\n";