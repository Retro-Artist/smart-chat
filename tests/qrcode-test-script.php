<?php
/**
 * QR Code Test Script
 * 
 * Test script to verify QR code generation and display functionality
 */

// Load required classes
require_once __DIR__ . '/../src/Api/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../src/Api/EvolutionAPI/Instances.php';

$config = require_once __DIR__ . '/../config/config.php';

// Configuration
$serverUrl = $config['evolutionAPI']['api_url'];
$apiKey = $config['evolutionAPI']['api_key'];
$instance = $config['testing']['instance'];
$testing = $config['testing']['enabled'];

// Validation
if (!$testing) {
    die("❌ Testing disabled. Set TEST_EVOLUTION_API_ENABLED=true in .env\n");
}

if (empty($apiKey) || empty($serverUrl) || empty($instance)) {
    die("❌ Missing configuration. Check your .env file\n");
}

echo "🔗 QR Code Test for Evolution API\n";
echo "Instance: $instance\n";
echo "Server: $serverUrl\n\n";

// Initialize
$api = new EvolutionAPI($serverUrl, $apiKey, $instance);
$instances = new Instances($api);

// Test 1: Check API connection
echo "1. Testing API connection...\n";
$apiInfo = $api->getInformation();

echo "   HTTP Code: " . ($apiInfo['http_code'] ?? 'Unknown') . "\n";
echo "   Raw Response: " . substr($apiInfo['raw_response'] ?? '', 0, 100) . "...\n";

if ($apiInfo['success']) {
    echo "   ✅ API is working\n";
    if (isset($apiInfo['data'])) {
        echo "   Response Data: " . json_encode($apiInfo['data'], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "   ❌ API connection failed: " . ($apiInfo['error'] ?? 'Unknown error') . "\n";
    echo "   Full response: " . ($apiInfo['raw_response'] ?? 'No response') . "\n";
    
    // Don't exit, continue with debugging
    echo "   Continuing with QR code test anyway...\n";
}

// Test 2: Get QR code
echo "2. Getting QR code...\n";
$qrResult = $instances->instanceConnect($instance);

if ($qrResult['success']) {
    echo "   ✅ QR code request successful\n";
    
    $data = $qrResult['data'];
    
    // Check what fields are available
    echo "   📋 Available fields: " . implode(', ', array_keys($data)) . "\n";
    
    // Check for QR code data
    $qrCode = $data['code'] ?? $data['qrcode'] ?? $data['qr'] ?? null;
    $pairingCode = $data['pairingCode'] ?? null;
    
    if ($qrCode) {
        echo "   📱 QR Code found!\n";
        echo "   🔗 QR Code length: " . strlen($qrCode) . " characters\n";
        echo "   🔗 QR Code preview: " . substr($qrCode, 0, 50) . "...\n";
        
        // Check if it's base64
        if (base64_decode($qrCode, true) !== false) {
            echo "   ✅ QR Code appears to be valid base64\n";
            
            // Create data URI
            $dataUri = 'data:image/png;base64,' . $qrCode;
            echo "   🖼️  Data URI created: " . substr($dataUri, 0, 60) . "...\n";
            
            // Test HTML output
            echo "\n   📄 HTML for QR Code display:\n";
            echo "   <img src=\"{$dataUri}\" alt=\"WhatsApp QR Code\" style=\"width: 256px; height: 256px;\">\n";
            
        } else {
            echo "   ⚠️  QR Code is not base64 - might need special handling\n";
        }
    } else {
        echo "   ❌ No QR code found in response\n";
    }
    
    if ($pairingCode) {
        echo "   🔗 Pairing Code: " . $pairingCode . "\n";
    }
    
    // Show full response for debugging
    echo "\n   🐛 Full response data:\n";
    echo "   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
} else {
    echo "   ❌ Failed to get QR code: " . ($qrResult['error'] ?? 'Unknown error') . "\n";
    if (isset($qrResult['http_code'])) {
        echo "   HTTP Code: " . $qrResult['http_code'] . "\n";
    }
}

// Test 3: Check connection state