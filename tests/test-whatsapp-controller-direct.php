<?php
/**
 * Test WhatsAppController directly to see if the issue is in the controller or routing
 */

// Set up environment
$_SESSION['user_id'] = 1; // Fake a logged-in user

// Load dependencies
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Helpers.php';
require_once __DIR__ . '/../src/Web/Models/WhatsAppInstance.php';

echo "🔍 Testing WhatsAppController Direct\n";
echo "===================================\n\n";

try {
    // Test WhatsAppInstance create method directly
    echo "1️⃣ Testing WhatsAppInstance->create() directly...\n";
    
    $whatsappInstance = new WhatsAppInstance();
    $result = $whatsappInstance->create(1); // Use user ID 1
    
    echo "   Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "   Error: " . ($result['error'] ?? 'None') . "\n";
    
    if ($result['success']) {
        echo "   Instance Name: " . $result['instance_name'] . "\n";
        echo "   Instance ID: " . $result['id'] . "\n";
        
        // Clean up - delete the test instance
        echo "\n🧹 Cleaning up test instance...\n";
        $deleteResult = $whatsappInstance->delete($result['instance_name'], 1);
        echo "   Delete result: " . ($deleteResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    }
    
    echo "\n2️⃣ Testing complete WhatsAppController flow...\n";
    
    // Simulate the controller method
    ob_start(); // Capture any output
    
    try {
        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Load and test controller
        require_once __DIR__ . '/../src/Web/Controllers/WhatsAppController.php';
        
        $controller = new WhatsAppController();
        
        // Call createInstance method (this should work if everything is correct)
        $controller->createInstance();
        
        echo "   ❌ Controller method should have output JSON, but we reached this line\n";
        
    } catch (Exception $e) {
        echo "   ❌ Controller exception: " . $e->getMessage() . "\n";
    }
    
    $output = ob_get_clean();
    
    if ($output) {
        echo "   📤 Controller output: " . substr($output, 0, 200) . "...\n";
        
        // Try to decode as JSON
        $decoded = json_decode($output, true);
        if ($decoded) {
            echo "   ✅ Valid JSON output\n";
            echo "   📊 JSON: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "   ❌ Invalid JSON output\n";
            echo "   📜 Raw output: $output\n";
        }
    } else {
        echo "   ⚠️ No output from controller\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Test Complete\n";