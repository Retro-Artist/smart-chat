<?php
/**
 * Simple Debug Script - Test the exact API call that should work
 */

$config = require_once __DIR__ . '/../config/config.php';

$serverUrl = $config['evolutionAPI']['api_url'];
$apiKey = $config['evolutionAPI']['api_key'];

echo "🔍 Simple API Debug Test\n";
echo "========================\n\n";

echo "Server: $serverUrl\n";
echo "API Key: " . substr($apiKey, 0, 8) . "...\n\n";

// Test 1: Basic API connection
echo "1️⃣ Testing basic API connection...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serverUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "apikey: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Error: " . ($error ?: 'None') . "\n";
echo "   Response: " . substr($response, 0, 100) . "...\n\n";

if ($httpCode !== 200) {
    echo "❌ Basic API connection failed - stopping here\n";
    exit(1);
}

// Test 2: Test instance creation endpoint directly
$testInstance = 'simple-test-' . time();
echo "2️⃣ Testing instance creation directly...\n";
echo "   Instance: $testInstance\n";
echo "   Endpoint: $serverUrl/instance/create\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$serverUrl/instance/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "apikey: $apiKey"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'instanceName' => $testInstance,
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Instance Creation Result:\n";
echo "   HTTP Code: $httpCode\n";
echo "   cURL Error: " . ($error ?: 'None') . "\n";
echo "   Response: $response\n\n";

if ($httpCode === 201 || $httpCode === 200) {
    echo "✅ Instance creation worked!\n";
    
    // Clean up - try to delete the test instance
    echo "🧹 Cleaning up...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$serverUrl/instance/delete/$testInstance");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "apikey: $apiKey"
    ]);
    
    $deleteResponse = curl_exec($ch);
    $deleteCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   Delete HTTP Code: $deleteCode\n";
    echo "   Delete Response: $deleteResponse\n";
    
} else {
    echo "❌ Instance creation failed\n";
    echo "🔍 Let's debug the error...\n\n";
    
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "📋 Decoded Response:\n";
        print_r($decoded);
    } else {
        echo "⚠️ Response is not JSON:\n";
        echo "Raw response: $response\n";
    }
    
    // Check if the endpoint exists
    echo "\n🌐 Testing if endpoint exists...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$serverUrl/instance/create");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apiKey"
    ]);
    
    $headResponse = curl_exec($ch);
    $headCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   HEAD request code: $headCode\n";
    
    if ($headCode === 404) {
        echo "   ❌ Endpoint does not exist!\n";
        echo "   💡 Your Evolution API might not support this endpoint\n";
        echo "   💡 Try checking: $serverUrl/manager for available endpoints\n";
    } elseif ($headCode === 405) {
        echo "   ⚠️ Method not allowed - endpoint exists but POST might not be supported\n";
    } else {
        echo "   ✅ Endpoint seems to exist\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Debug Complete\n";