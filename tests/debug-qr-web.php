<?php
/**
 * Debug QR Code for Web View
 * This script tests the exact same flow as the web view
 */

// Initialize session
session_start();
$_SESSION['user_id'] = 1;

// Load dependencies
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Helpers.php';
require_once __DIR__ . '/../src/Web/Models/WhatsAppInstance.php';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>QR Debug</title></head><body>\n";
echo "<h1>QR Code Debug Test</h1>\n";
echo "<pre>\n";

try {
    $whatsappInstance = new WhatsAppInstance();
    
    // Get instances
    $instances = $whatsappInstance->getUserInstances(1);
    echo "Found " . count($instances) . " instances:\n";
    foreach ($instances as $instance) {
        echo "  - " . $instance['instance_name'] . " (Status: " . $instance['status'] . ")\n";
    }
    echo "\n";
    
    // Find a connecting instance
    $connectingInstance = null;
    foreach ($instances as $instance) {
        if ($instance['status'] === 'connecting') {
            $connectingInstance = $instance;
            break;
        }
    }
    
    if (!$connectingInstance) {
        echo "No connecting instance found. Creating a new one...\n";
        $result = $whatsappInstance->create(1);
        if ($result['success']) {
            $connectingInstance = ['instance_name' => $result['instance_name']];
            echo "Created instance: " . $result['instance_name'] . "\n";
            // Wait for instance to be ready
            sleep(3);
        } else {
            echo "Failed to create instance: " . $result['error'] . "\n";
            exit;
        }
    }
    
    $instanceName = $connectingInstance['instance_name'];
    echo "\nTesting QR code for: $instanceName\n";
    
    // Get QR code using the same method as the controller
    $qrResult = $whatsappInstance->getQRCode($instanceName);
    
    echo "\nQR Result:\n";
    echo "Success: " . ($qrResult['success'] ? 'YES' : 'NO') . "\n";
    echo "Error: " . ($qrResult['error'] ?? 'None') . "\n";
    
    if ($qrResult['success']) {
        $qrCode = $qrResult['qr_code'];
        echo "QR Code Length: " . strlen($qrCode) . " characters\n";
        echo "QR Code Preview: " . substr($qrCode, 0, 50) . "...\n";
        echo "Is Data URI: " . (strpos($qrCode, 'data:image/') === 0 ? 'YES' : 'NO') . "\n";
        
        // Display the QR code
        echo "</pre>\n";
        echo "<h2>QR Code Image:</h2>\n";
        echo '<img src="' . $qrCode . '" alt="QR Code" style="border: 1px solid #ccc; padding: 10px;">';
        echo "<pre>\n";
        
        // Additional debug info
        if (isset($qrResult['pairing_code'])) {
            echo "\nPairing Code: " . $qrResult['pairing_code'] . "\n";
        }
        if (isset($qrResult['count'])) {
            echo "Count: " . $qrResult['count'] . "\n";
        }
    } else {
        echo "\nDebug Data:\n";
        print_r($qrResult);
    }
    
} catch (Exception $e) {
    echo "\nException: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "</body></html>\n";
?>