<?php
/**
 * Debug QR Code Generation - Test with your existing instance
 * Save this as tests/debug-current-instance.php
 */

// Set up environment
$_SESSION['user_id'] = 1;

// Load dependencies
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Helpers.php';
require_once __DIR__ . '/../src/Api/Models/WhatsappInstance.php';

echo "🔍 Debug Current Instance QR Code\n";
echo "==================================\n\n";

try {
    $whatsappInstance = new WhatsAppInstance();
    
    // Get the current instance (the one showing as connecting)
    $instances = $whatsappInstance->getUserInstances(1);
    
    echo "Found " . count($instances) . " instances:\n";
    foreach ($instances as $instance) {
        echo "  - " . $instance['instance_name'] . " (Status: " . $instance['status'] . ")\n";
    }
    echo "\n";
    
    // Find the connecting instance
    $connectingInstance = null;
    foreach ($instances as $instance) {
        if ($instance['status'] === 'connecting') {
            $connectingInstance = $instance;
            break;
        }
    }
    
    if (!$connectingInstance) {
        echo "❌ No connecting instance found\n";
        exit(1);
    }
    
    $instanceName = $connectingInstance['instance_name'];
    echo "🔍 Testing QR code generation for: $instanceName\n\n";
    
    // Test the getQRCode method
    echo "1️⃣ Calling getQRCode() method...\n";
    $qrResult = $whatsappInstance->getQRCode($instanceName);
    
    echo "   Success: " . ($qrResult['success'] ? 'YES' : 'NO') . "\n";
    echo "   Error: " . ($qrResult['error'] ?? 'None') . "\n";
    
    if ($qrResult['success']) {
        echo "   QR Code Length: " . strlen($qrResult['qr_code']) . " characters\n";
        echo "   QR Code Preview: " . substr($qrResult['qr_code'], 0, 50) . "...\n";
        
        // Check if it's a valid data URI
        if (strpos($qrResult['qr_code'], 'data:image/') === 0) {
            echo "   ✅ QR Code is a valid data URI\n";
            
            // Extract base64 part and save as PNG
            $base64Data = substr($qrResult['qr_code'], strpos($qrResult['qr_code'], ',') + 1);
            $imageData = base64_decode($base64Data);
            
            $filename = __DIR__ . '/debug-qr-' . time() . '.png';
            if (file_put_contents($filename, $imageData)) {
                echo "   💾 QR code saved as: $filename\n";
                echo "   🖼️ You can open this file to verify the QR code\n";
            } else {
                echo "   ❌ Could not save QR code file\n";
            }
        } else {
            echo "   ❌ QR code is not in data URI format\n";
            echo "   Raw QR code: " . $qrResult['qr_code'] . "\n";
        }
        
        // Show additional data
        if (isset($qrResult['pairing_code'])) {
            echo "   🔗 Pairing Code: " . $qrResult['pairing_code'] . "\n";
        }
        
        if (isset($qrResult['count'])) {
            echo "   📊 Count: " . $qrResult['count'] . "\n";
        }
        
    } else {
        echo "   ❌ QR code generation failed\n";
        echo "   Full result: " . json_encode($qrResult, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n2️⃣ Testing direct Evolution API call...\n";
    
    // Test direct API call - Fix config loading
    require_once __DIR__ . '/../src/Api/WhatsApp/EvolutionAPI.php';
    require_once __DIR__ . '/../src/Api/WhatsApp/Instances.php';
    
    $config = require __DIR__ . '/../config/config.php';
    
    // Handle string boolean values from environment
    $serverUrl = $config['evolutionAPI']['api_url'] ?? '';
    $apiKey = $config['evolutionAPI']['api_key'] ?? '';
    
    if (empty($serverUrl) || empty($apiKey)) {
        echo "   ❌ Missing Evolution API configuration\n";
        echo "   Server URL: " . ($serverUrl ?: 'NOT SET') . "\n";
        echo "   API Key: " . ($apiKey ? substr($apiKey, 0, 8) . '...' : 'NOT SET') . "\n";
    } else {
        echo "   📋 Config loaded successfully\n";
        echo "   Server URL: $serverUrl\n";
        echo "   API Key: " . substr($apiKey, 0, 8) . "...\n";
        
        try {
            $api = new EvolutionAPI($serverUrl, $apiKey, $instanceName);
            $instances = new Instances($api);
            $directResult = $instances->instanceConnect($instanceName);
            
            echo "   Direct API Success: " . ($directResult['success'] ? 'YES' : 'NO') . "\n";
            echo "   Direct API Error: " . ($directResult['error'] ?? 'None') . "\n";
            
            if ($directResult['success'] && isset($directResult['data'])) {
                $data = $directResult['data'];
                echo "   Available fields: " . implode(', ', array_keys($data)) . "\n";
                echo "   Count: " . ($data['count'] ?? 'Not set') . "\n";
                
                if (isset($data['code'])) {
                    echo "   Raw code length: " . strlen($data['code']) . "\n";
                }
                
                if (isset($data['base64'])) {
                    echo "   Base64 field length: " . strlen($data['base64']) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "   ❌ Direct API Exception: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Debug Complete\n";
?>