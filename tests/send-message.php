<?php

/**
 * Simple Test for Evolution API
 * 
 * Clean and straightforward test file for basic functionality
 */

// Load required classes
require_once __DIR__ . '/../src/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../src/EvolutionAPI/Instances.php';
require_once __DIR__ . '/../src/EvolutionAPI/SendMessage.php';
require_once __DIR__ . '/../src/EvolutionAPI/MediaHandler.php';

$config = require_once __DIR__ . '/../app/config.php';

// Configuration
$serverUrl = $config['evolutionAPI']['api_url'];
$apiKey = $config['evolutionAPI']['api_key'];
$instance = $config['testing']['instance'];
$testNumber = $config['testing']['phone_number'];
$testing = $config['testing']['enabled'];

// Validation
if (!$testing) {
    die("❌ Testing disabled. Set TEST_EVOLUTION_API_ENABLED=true in .env\n");
}

if (empty($apiKey) || empty($serverUrl) || empty($instance)) {
    die("❌ Missing configuration. Check your .env file\n");
}

// Initialize
$api = new EvolutionAPI($serverUrl, $apiKey, $instance);
$instances = new Instances($api);
$sendMessage = new SendMessage($api);
$mediaHandler = new MediaHandler($api, __DIR__ . '/../media/', __DIR__ . '/../temp/');

echo "🚀 Evolution API Simple Test\n";
echo "Instance: $instance\n";
echo "Server: $serverUrl\n\n";

// Test 1: Check API connection
echo "1. Testing API connection...\n";
$apiInfo = $api->getInformation();
if ($apiInfo['success']) {
    echo "   ✅ API is working\n";
} else {
    echo "   ❌ API connection failed: " . $apiInfo['error'] . "\n";
    exit(1);
}

// Test 2: Check instance connection
echo "2. Checking instance connection...\n";
$isConnected = $instances->isInstanceConnected();
if ($isConnected) {
    echo "   ✅ Instance is connected\n";
} else {
    echo "   ❌ Instance not connected\n";
    echo "   📱 Get QR code to connect:\n";
    $qr = $instances->getQRCode();
    if ($qr['success'] && isset($qr['data']['code'])) {
        echo "   QR: " . $qr['data']['code'] . "\n";
    }
    exit(1);
}

// Test 3: Send test message (if number provided)
if (!empty($testNumber)) {
    echo "3. Sending test message...\n";
    
    // Choose your test type:
    // Option A: Simple text message
    //$result = $sendMessage->sendSimpleMessage($testNumber, "Hello from Evolution API! " . date('H:i:s'));
    
    // Option B: Send audio from media directory (uncomment to use)
    // $result = $mediaHandler->sendWhatsAppAudioFromMediaDir($testNumber, "audio/message.mp3");
    
    // Option C: Send image from media directory (uncomment to use)
    // $result = $mediaHandler->sendFromMediaDir($testNumber, "images/photo1.jpg", "Test image");
    
    if ($result['success']) {
        echo "   ✅ Message sent successfully\n";
        if (isset($result['data']['key']['id'])) {
            echo "   📝 Message ID: " . $result['data']['key']['id'] . "\n";
        }
    } else {
        echo "   ❌ Failed to send message: " . $result['error'] . "\n";
    }
} else {
    echo "3. Skipping message test (no TEST_EVOLUTION_API_NUMBER configured)\n";
}

echo "\n✨ Test completed!\n";