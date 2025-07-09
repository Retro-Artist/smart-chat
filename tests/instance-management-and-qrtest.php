<?php
/**
 * Instance Management & QR Test Script
 * 
 * This script will help you test QR code functionality by:
 * 1. Creating a new test instance
 * 2. Getting the QR code
 * 3. Cleaning up the test instance
 */

// Load required classes
require_once __DIR__ . '/../src/Api/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../src/Api/EvolutionAPI/Instances.php';

$config = require_once __DIR__ . '/../config/config.php';

// Configuration
$serverUrl = $config['evolutionAPI']['api_url'];
$apiKey = $config['evolutionAPI']['api_key'];
$testing = $config['testing']['enabled'];

// Generate unique test instance name
$testInstance = 'qr-test-' . time();

echo "🔗 Instance Management & QR Code Test\n";
echo "=====================================\n\n";

if (!$testing) {
    die("❌ Testing disabled. Set TEST_EVOLUTION_API_ENABLED=true in .env\n");
}

if (empty($apiKey) || empty($serverUrl)) {
    die("❌ Missing configuration. Check your .env file\n");
}

// Initialize
$api = new EvolutionAPI($serverUrl, $apiKey, $testInstance);
$instances = new Instances($api);

echo "🚀 Test Instance: {$testInstance}\n";
echo "🌐 Server: {$serverUrl}\n\n";

try {
    // Step 1: Create a new test instance
    echo "1️⃣ Creating test instance...\n";
    $createResult = $instances->createInstance($testInstance, [
        'qrcode' => true,
        'integration' => 'WHATSAPP-BAILEYS'
    ]);

    if ($createResult['success']) {
        echo "   ✅ Test instance created successfully\n";
        echo "   📊 Instance data: " . json_encode($createResult['data'], JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "   ❌ Failed to create instance: " . ($createResult['error'] ?? 'Unknown error') . "\n";
        echo "   📊 Response: " . json_encode($createResult, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

    // Step 2: Wait a moment for instance to initialize
    echo "2️⃣ Waiting for instance to initialize...\n";
    sleep(3);

    // Step 3: Check instance state
    echo "3️⃣ Checking instance connection state...\n";
    $stateResult = $instances->connectionState($testInstance);
    
    if ($stateResult['success']) {
        $state = $stateResult['data']['instance']['state'] ?? 'unknown';
        echo "   📊 Instance state: {$state}\n\n";
    } else {
        echo "   ⚠️ Could not get instance state\n\n";
    }

    // Step 4: Get QR code
    echo "4️⃣ Getting QR code...\n";
    $qrResult = $instances->instanceConnect($testInstance);

    if ($qrResult['success']) {
        echo "   ✅ QR code request successful\n";
        
        $data = $qrResult['data'];
        echo "   📋 Available fields: " . implode(', ', array_keys($data)) . "\n";
        
        // Check for QR code data
        $qrCode = $data['code'] ?? $data['qrcode'] ?? $data['qr'] ?? null;
        $pairingCode = $data['pairingCode'] ?? null;
        
        if ($qrCode) {
            echo "   🎉 QR Code found!\n";
            echo "   📱 QR Code length: " . strlen($qrCode) . " characters\n";
            echo "   🔗 QR Code preview: " . substr($qrCode, 0, 50) . "...\n";
            
            // Check if it's base64
            if (base64_decode($qrCode, true) !== false) {
                echo "   ✅ QR Code appears to be valid base64\n";
                
                // Create data URI
                $dataUri = 'data:image/png;base64,' . $qrCode;
                echo "   🖼️ Data URI created successfully\n";
                
                // Test HTML output
                echo "\n   📄 HTML for QR Code display:\n";
                echo "   <img src=\"{$dataUri}\" alt=\"WhatsApp QR Code\" style=\"width: 256px; height: 256px;\">\n\n";
                
                // Save QR code as image file for testing
                $qrImagePath = __DIR__ . '/qr-code-test.png';
                $qrImageData = base64_decode($qrCode);
                if (file_put_contents($qrImagePath, $qrImageData)) {
                    echo "   💾 QR code saved as: {$qrImagePath}\n";
                    echo "   🖼️ You can open this file to see the QR code\n\n";
                }
                
            } else {
                echo "   ⚠️ QR Code is not base64 - might need special handling\n";
                echo "   🔍 Raw QR data: {$qrCode}\n\n";
            }
        } else {
            echo "   ❌ No QR code found in response\n";
            echo "   📊 This might happen if instance connected too quickly\n\n";
        }
        
        if ($pairingCode) {
            echo "   🔗 Pairing Code: {$pairingCode}\n\n";
        }
        
        // Show full response for debugging
        echo "   🐛 Full response data:\n";
        echo "   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
        
    } else {
        echo "   ❌ Failed to get QR code: " . ($qrResult['error'] ?? 'Unknown error') . "\n";
        if (isset($qrResult['http_code'])) {
            echo "   📊 HTTP Code: " . $qrResult['http_code'] . "\n";
        }
        echo "   📊 Full response: " . json_encode($qrResult, JSON_PRETTY_PRINT) . "\n\n";
    }

} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n\n";
} finally {
    // Cleanup: Delete the test instance
    echo "🧹 Cleaning up test instance...\n";
    
    try {
        $deleteResult = $instances->deleteInstance($testInstance);
        if ($deleteResult['success']) {
            echo "   ✅ Test instance deleted successfully\n";
        } else {
            echo "   ⚠️ Could not delete test instance: " . ($deleteResult['error'] ?? 'Unknown error') . "\n";
            echo "   💡 You may need to delete '{$testInstance}' manually from Evolution API manager\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️ Error during cleanup: " . $e->getMessage() . "\n";
        echo "   💡 You may need to delete '{$testInstance}' manually from Evolution API manager\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 QR Code Test Complete\n\n";

echo "📋 Summary:\n";
echo "   - If QR code was found: The fix is working correctly! ✅\n";
echo "   - If no QR code: Instance might have connected too quickly\n";
echo "   - For production: Use a fresh instance that hasn't been connected yet\n\n";

echo "🔧 Next steps for your web interface:\n";
echo "   1. Make sure to create new instances (not reuse existing ones)\n";
echo "   2. The QR code will only appear when state is 'connecting'\n";
echo "   3. Once connected, the QR code disappears and status becomes 'open'\n";