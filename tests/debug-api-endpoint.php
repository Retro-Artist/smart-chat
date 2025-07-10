<?php
/**
 * Debug API Endpoint Test
 * Test the exact same endpoint that JavaScript calls
 */

// Simulate the same environment as the web interface
session_start();
$_SESSION['user_id'] = 1;

// Load the same dependencies
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Helpers.php';
require_once __DIR__ . '/../src/Web/Controllers/InstanceController.php';

echo "🔍 Debug API Endpoint Test\n";
echo "============================\n\n";

// Test the exact same endpoint that JavaScript calls
$instanceName = 'smart-chat-1-1752083089'; // Replace with your instance name
echo "Testing: /api/whatsapp/status?instance=$instanceName\n\n";

// Simulate the API call
$_GET['instance'] = $instanceName;

try {
    $controller = new InstanceController();
    
    echo "1. Calling InstanceController->getStatus()...\n";
    
    // Capture output
    ob_start();
    $controller->getStatus();
    $output = ob_get_clean();
    
    echo "2. API Response:\n";
    echo $output . "\n\n";
    
    // Parse the JSON response
    $response = json_decode($output, true);
    
    echo "3. Parsed Response:\n";
    echo "   Success: " . ($response['success'] ? 'YES' : 'NO') . "\n";
    
    if ($response['success'] && isset($response['data']['instance'])) {
        $instance = $response['data']['instance'];
        echo "   Instance State: " . ($instance['state'] ?? 'Not set') . "\n";
        echo "   Instance Name: " . ($instance['instanceName'] ?? 'Not set') . "\n";
        
        if ($instance['state'] === 'open') {
            echo "   ✅ Instance is CONNECTED!\n";
        } else {
            echo "   ❌ Instance is NOT connected (state: " . $instance['state'] . ")\n";
        }
    } else {
        echo "   Error: " . ($response['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Debug Complete\n";
?>