<?php
/**
 * Debug API Connection Script
 * 
 * Comprehensive debugging for Evolution API connection issues
 */

// Load configuration
$config = require_once __DIR__ . '/../config/config.php';

// Configuration
$serverUrl = $config['evolutionAPI']['api_url'];
$apiKey = $config['evolutionAPI']['api_key'];
$instance = $config['testing']['instance'];
$testing = $config['testing']['enabled'];

echo "🔍 Evolution API Debug Script\n";
echo "==============================\n\n";

// Check if testing is enabled
if (!$testing) {
    echo "❌ Testing disabled. Set TEST_EVOLUTION_API_ENABLED=true in .env\n";
    exit(1);
}

// Check configuration
echo "📋 Configuration Check:\n";
echo "   Server URL: " . ($serverUrl ?: 'NOT SET') . "\n";
echo "   API Key: " . ($apiKey ? substr($apiKey, 0, 8) . '...' : 'NOT SET') . "\n";
echo "   Instance: " . ($instance ?: 'NOT SET') . "\n\n";

if (empty($apiKey) || empty($serverUrl)) {
    echo "❌ Missing configuration. Check your .env file\n";
    exit(1);
}

// Test 1: Basic curl connection to root endpoint
echo "🔗 Test 1: Basic API Connection\n";
echo "   Testing: {$serverUrl}\n";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $serverUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $apiKey
    ],
    CURLOPT_CUSTOMREQUEST => "GET"
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
$info = curl_getinfo($curl);
curl_close($curl);

echo "   HTTP Code: " . $httpCode . "\n";
echo "   cURL Error: " . ($error ?: 'None') . "\n";
echo "   Response Length: " . strlen($response) . " bytes\n";
echo "   Content Type: " . ($info['content_type'] ?? 'Unknown') . "\n";

// Show first 200 characters of response
if ($response) {
    echo "   Response Preview: " . substr($response, 0, 200) . "\n";
    
    // Try to decode JSON
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   ✅ Valid JSON response\n";
        echo "   Response Data: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Invalid JSON: " . json_last_error_msg() . "\n";
        echo "   Raw Response: " . $response . "\n";
    }
} else {
    echo "   ❌ No response received\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 2: Test with EvolutionAPI class if basic connection works
if ($httpCode >= 200 && $httpCode < 400) {
    echo "🔗 Test 2: EvolutionAPI Class Test\n";
    
    // Load the class
    require_once __DIR__ . '/../src/Api/EvolutionAPI/EvolutionAPI.php';
    
    try {
        $api = new EvolutionAPI($serverUrl, $apiKey, $instance);
        echo "   ✅ EvolutionAPI class instantiated\n";
        
        // Test getInformation method
        echo "   Testing getInformation() method...\n";
        $result = $api->getInformation();
        
        echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
        echo "   HTTP Code: " . ($result['http_code'] ?? 'Unknown') . "\n";
        echo "   Error: " . ($result['error'] ?? 'None') . "\n";
        
        if (isset($result['raw_response'])) {
            echo "   Raw Response: " . substr($result['raw_response'], 0, 200) . "\n";
        }
        
        if (isset($result['data'])) {
            echo "   Decoded Data: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Test 3: Network diagnostics
echo "🌐 Test 3: Network Diagnostics\n";

// Parse URL
$urlParts = parse_url($serverUrl);
$host = $urlParts['host'] ?? '';
$port = $urlParts['port'] ?? ($urlParts['scheme'] === 'https' ? 443 : 80);

echo "   Host: {$host}\n";
echo "   Port: {$port}\n";

// Test DNS resolution
if ($host) {
    $ip = gethostbyname($host);
    echo "   DNS Resolution: " . ($ip !== $host ? "✅ {$ip}" : "❌ Failed") . "\n";
    
    // Test port connectivity
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($connection) {
        echo "   Port Connectivity: ✅ Port {$port} is open\n";
        fclose($connection);
    } else {
        echo "   Port Connectivity: ❌ Cannot connect to port {$port} ({$errstr})\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 4: SSL/TLS check for HTTPS
if (strpos($serverUrl, 'https://') === 0) {
    echo "🔒 Test 4: SSL/TLS Check\n";
    
    $context = stream_context_create([
        "ssl" => [
            "capture_peer_cert" => true,
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ]);
    
    $stream = @stream_socket_client(
        "ssl://{$host}:{$port}",
        $errno,
        $errstr,
        5,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if ($stream) {
        echo "   ✅ SSL connection successful\n";
        $cert = stream_context_get_params($stream);
        if (isset($cert['options']['ssl']['peer_certificate'])) {
            $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
            echo "   Certificate Subject: " . ($certinfo['subject']['CN'] ?? 'Unknown') . "\n";
            echo "   Certificate Issuer: " . ($certinfo['issuer']['CN'] ?? 'Unknown') . "\n";
            echo "   Valid Until: " . date('Y-m-d H:i:s', $certinfo['validTo_time_t']) . "\n";
        }
        fclose($stream);
    } else {
        echo "   ❌ SSL connection failed: {$errstr}\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Test 5: Environment check
echo "🔧 Test 5: Environment Check\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   cURL Enabled: " . (extension_loaded('curl') ? '✅ Yes' : '❌ No') . "\n";
echo "   OpenSSL Enabled: " . (extension_loaded('openssl') ? '✅ Yes' : '❌ No') . "\n";
echo "   JSON Enabled: " . (extension_loaded('json') ? '✅ Yes' : '❌ No') . "\n";

// Check if running in Docker
if (file_exists('/.dockerenv')) {
    echo "   Environment: 🐳 Docker container\n";
} else {
    echo "   Environment: 💻 Local machine\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Debug Complete\n";
echo "\nIf you're still having issues, please share the output above.\n";
echo "Common solutions:\n";
echo "1. Check if Evolution API server is running\n";
echo "2. Verify API key is correct\n";
echo "3. Check firewall/network settings\n";
echo "4. Ensure server URL includes proper scheme (http/https)\n";