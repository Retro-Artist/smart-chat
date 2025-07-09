<?php
/**
 * Simple Instance List
 * Non-interactive way to list instances
 */

// Load required classes
require_once __DIR__ . '/../src/Api/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../src/Api/EvolutionAPI/Instances.php';

$config = require_once __DIR__ . '/../config/config.php';

// Configuration
$serverUrl = $config['evolutionAPI']['api_url'];
$apiKey = $config['evolutionAPI']['api_key'];
$testing = $config['testing']['enabled'];

// Validation
if (!$testing) {
    die("❌ Testing disabled. Set TEST_EVOLUTION_API_ENABLED=true in .env\n");
}

if (empty($apiKey) || empty($serverUrl)) {
    die("❌ Missing API configuration. Check your .env file\n");
}

// Initialize
$api = new EvolutionAPI($serverUrl, $apiKey, 'temp-init');
$instances = new Instances($api);

echo "📋 Listing all instances...\n";
$result = $instances->fetchInstances();

if ($result['success'] && isset($result['data'])) {
    echo "Found " . count($result['data']) . " instances:\n\n";
    
    foreach ($result['data'] as $i => $instanceData) {
        // Handle different possible data structures
        if (isset($instanceData['instance'])) {
            $instance = $instanceData['instance'];
        } else {
            $instance = $instanceData;
        }
        
        $name = $instance['instanceName'] ?? $instance['name'] ?? 'Unknown';
        $basicStatus = $instance['status'] ?? $instance['state'] ?? 'Unknown';
        $id = $instance['instanceId'] ?? $instance['id'] ?? 'N/A';
        
        echo ($i + 1) . ". $name\n";
        echo "   Status: $basicStatus\n";
        echo "   ID: $id\n";
        
        // Get real-time connection state
        $connectionState = $instances->connectionState($name);
        if ($connectionState['success'] && isset($connectionState['data']['instance']['state'])) {
            $realStatus = $connectionState['data']['instance']['state'];
            echo "   Connection State: $realStatus\n";
        }
        
        echo "\n";
    }
} else {
    echo "❌ Failed to list instances: " . ($result['error'] ?? 'Unknown error') . "\n";
}
?>