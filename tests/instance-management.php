<?php

/**
 * Interactive Instance Manager for Evolution API
 * 
 * Terminal-based interactive interface for managing Evolution API instances
 * Provides a menu-driven interface for creating, managing, and deleting instances
 */

// Load required classes
require_once __DIR__ . '/../src/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../src/EvolutionAPI/Instances.php';
require_once __DIR__ . '/../src/EvolutionAPI/Settings.php';

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

// Initialize (using a temporary instance for initialization)
$api = new EvolutionAPI($serverUrl, $apiKey, 'temp-init');
$instances = new Instances($api);
$settings = new Settings($api);

// Global variables
$currentInstance = null;
$running = true;

// ======================
// UTILITY FUNCTIONS
// ======================

function clearScreen() {
    if (PHP_OS_FAMILY === 'Windows') {
        system('cls');
    } else {
        system('clear');
    }
}

function printHeader() {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║              🏗️  EVOLUTION API INSTANCE MANAGER            ║\n";
    echo "║                    Interactive Terminal                    ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "Server: " . substr($_SERVER['argv'][0] ?? 'Unknown', 0, 50) . "\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    if ($GLOBALS['currentInstance']) {
        echo "Current Instance: " . $GLOBALS['currentInstance'] . "\n";
    }
    echo "\n";
}

function printMenu() {
    echo "┌─────────────────── MAIN MENU ───────────────────┐\n";
    echo "│                                                 │\n";
    echo "│  1. 📋 List all instances                       │\n";
    echo "│  2. 🏗️  Create new instance                     │\n";
    echo "│  3. 🔍 Get instance details                     │\n";
    echo "│  4. 🔗 Get QR code/Pairing code                │\n";
    echo "│  5. 📊 Check connection state                   │\n";
    echo "│  6. 🔄 Restart instance                         │\n";
    echo "│  7. 🚪 Logout instance                          │\n";
    echo "│  8. 🗑️  Delete instance                         │\n";
    echo "│  9. ⚙️  Instance settings                       │\n";
    echo "│ 10. 🎯 Set current instance                     │\n";
    echo "│  0. 🚪 Exit                                     │\n";
    echo "│                                                 │\n";
    echo "└─────────────────────────────────────────────────┘\n";
    echo "\n";
}

function getUserInput($prompt) {
    echo $prompt;
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    return $input;
}

function waitForEnter() {
    echo "\nPress Enter to continue...";
    fgets(STDIN);
}

function displayResult($title, $result) {
    echo "\n" . str_repeat("─", 50) . "\n";
    echo "📋 $title\n";
    echo str_repeat("─", 50) . "\n";
    
    if ($result['success']) {
        echo "✅ SUCCESS\n";
        if (isset($result['data']) && !empty($result['data'])) {
            echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    } else {
        echo "❌ FAILED\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        if (isset($result['http_code'])) {
            echo "HTTP Code: " . $result['http_code'] . "\n";
        }
    }
    echo str_repeat("─", 50) . "\n";
}

// ======================
// MENU FUNCTIONS
// ======================

function listInstances() {
    global $instances;
    
    echo "📋 Fetching all instances...\n";
    $result = $instances->fetchInstances();
    
    if ($result['success'] && isset($result['data'])) {
        echo "\n┌─── INSTANCES LIST ───┐\n";
        echo "│ Found " . count($result['data']) . " instances:\n";
        echo "│\n";
        
        foreach ($result['data'] as $i => $instanceData) {
            // Handle different possible data structures
            if (isset($instanceData['instance'])) {
                // Standard structure: data -> instance -> details
                $instance = $instanceData['instance'];
            } else {
                // Direct structure: data -> details
                $instance = $instanceData;
            }
            
            $name = $instance['instanceName'] ?? $instance['name'] ?? 'Unknown';
            $basicStatus = $instance['status'] ?? $instance['state'] ?? 'Unknown';
            $id = $instance['instanceId'] ?? $instance['id'] ?? 'N/A';
            
            // Get real-time connection state
            echo "│ " . ($i + 1) . ". $name (checking status...)\r";
            $connectionState = $instances->connectionState($name);
            $realStatus = 'Unknown';
            $statusIcon = '⚪';
            
            if ($connectionState['success'] && isset($connectionState['data']['instance']['state'])) {
                $realStatus = $connectionState['data']['instance']['state'];
                switch ($realStatus) {
                    case 'open':
                        $statusIcon = '🟢';
                        $realStatus = 'CONNECTED';
                        break;
                    case 'close':
                        $statusIcon = '🔴';
                        $realStatus = 'DISCONNECTED';
                        break;
                    case 'connecting':
                        $statusIcon = '🟡';
                        $realStatus = 'CONNECTING';
                        break;
                    default:
                        $statusIcon = '⚪';
                        $realStatus = strtoupper($realStatus);
                }
            } else {
                // Fallback to basic status
                $realStatus = strtoupper($basicStatus);
                if ($basicStatus === 'created') {
                    $statusIcon = '🔵';
                }
            }
            
            // Clear the checking line and show final result
            echo str_repeat(' ', 50) . "\r";
            echo "│ " . ($i + 1) . ". $name\n";
            echo "│    Status: $statusIcon $realStatus\n";
            echo "│    ID: " . substr($id, 0, 20) . (strlen($id) > 20 ? "..." : "") . "\n";
            
            // Show additional info if available
            if (isset($instance['owner'])) {
                echo "│    Owner: " . substr($instance['owner'], 0, 20) . "...\n";
            }
            if (isset($instance['profileName'])) {
                echo "│    Profile: " . $instance['profileName'] . "\n";
            }
            
            echo "│\n";
        }
        echo "└─────────────────────┘\n";
    } else {
        displayResult("List Instances", $result);
    }
}

function createInstance() {
    global $instances;
    
    $instanceName = getUserInput("Enter instance name: ");
    if (empty($instanceName)) {
        echo "❌ Instance name cannot be empty\n";
        return;
    }
    
    echo "🏗️  Creating instance '$instanceName'...\n";
    $result = $instances->createBasicInstance($instanceName);
    displayResult("Create Instance", $result);
    
    if ($result['success']) {
        $GLOBALS['currentInstance'] = $instanceName;
        echo "🎯 Current instance set to: $instanceName\n";
    }
}

function getInstanceDetails() {
    global $instances, $currentInstance;
    
    $instanceName = getUserInput("Enter instance name (or press Enter for current): ");
    if (empty($instanceName) && $currentInstance) {
        $instanceName = $currentInstance;
    }
    
    if (empty($instanceName)) {
        echo "❌ No instance specified\n";
        return;
    }
    
    echo "🔍 Getting details for '$instanceName'...\n";
    $result = $instances->getInstanceInfo($instanceName);
    
    if ($result['success'] && isset($result['data'])) {
        // Handle different possible data structures
        if (isset($result['data']['instance'])) {
            $instance = $result['data']['instance'];
        } else {
            $instance = $result['data'];
        }
        
        // Get real-time connection state
        echo "📊 Checking connection state...\n";
        $connectionState = $instances->connectionState($instanceName);
        $realStatus = 'Unknown';
        $statusIcon = '⚪';
        
        if ($connectionState['success'] && isset($connectionState['data']['instance']['state'])) {
            $realStatus = $connectionState['data']['instance']['state'];
            switch ($realStatus) {
                case 'open':
                    $statusIcon = '🟢';
                    $realStatus = 'CONNECTED';
                    break;
                case 'close':
                    $statusIcon = '🔴';
                    $realStatus = 'DISCONNECTED';
                    break;
                case 'connecting':
                    $statusIcon = '🟡';
                    $realStatus = 'CONNECTING';
                    break;
                default:
                    $statusIcon = '⚪';
                    $realStatus = strtoupper($realStatus);
            }
        } else {
            // Fallback to basic status
            $basicStatus = $instance['status'] ?? $instance['state'] ?? 'Unknown';
            $realStatus = strtoupper($basicStatus);
            if ($basicStatus === 'created') {
                $statusIcon = '🔵';
            }
        }
        
        echo "\n┌─── INSTANCE DETAILS ───┐\n";
        echo "│ Instance: " . ($instance['instanceName'] ?? $instance['name'] ?? 'Unknown') . "\n";
        echo "│ Status: $statusIcon $realStatus\n";
        echo "│ ID: " . ($instance['instanceId'] ?? $instance['id'] ?? 'N/A') . "\n";
        
        if (isset($instance['owner'])) {
            echo "│ Owner: " . $instance['owner'] . "\n";
        }
        if (isset($instance['profileName'])) {
            echo "│ Profile: " . $instance['profileName'] . "\n";
        }
        if (isset($instance['profileStatus'])) {
            echo "│ Bio: " . substr($instance['profileStatus'], 0, 50) . "...\n";
        }
        if (isset($instance['serverUrl'])) {
            echo "│ Server: " . $instance['serverUrl'] . "\n";
        }
        if (isset($instance['integration'])) {
            if (is_array($instance['integration'])) {
                echo "│ Integration: " . ($instance['integration']['integration'] ?? 'Unknown') . "\n";
            } else {
                echo "│ Integration: " . $instance['integration'] . "\n";
            }
        }
        
        echo "└────────────────────────┘\n";
        
        // Show connection helper status
        $isConnected = $instances->isInstanceConnected($instanceName);
        echo "\n🔗 Connection Helper: " . ($isConnected ? "✅ Connected" : "❌ Not Connected") . "\n";
        
        // Show raw data option
        $showRaw = getUserInput("\nShow raw JSON data? (y/N): ");
        if (strtolower($showRaw) === 'y') {
            echo "\n📋 Instance Data:\n";
            echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            
            if ($connectionState['success']) {
                echo "\n📊 Connection State Data:\n";
                echo json_encode($connectionState['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            }
        }
    } else {
        displayResult("Instance Details", $result);
    }
}

function getQRCode() {
    global $instances, $currentInstance;
    
    $instanceName = getUserInput("Enter instance name (or press Enter for current): ");
    if (empty($instanceName) && $currentInstance) {
        $instanceName = $currentInstance;
    }
    
    if (empty($instanceName)) {
        echo "❌ No instance specified\n";
        return;
    }
    
    $phoneNumber = getUserInput("Enter phone number for pairing code (optional): ");
    
    echo "📱 Getting QR/Pairing code for '$instanceName'...\n";
    
    if (!empty($phoneNumber)) {
        $result = $instances->getPairingCode($phoneNumber, $instanceName);
        echo "🔗 Requesting pairing code for: $phoneNumber\n";
    } else {
        $result = $instances->getQRCode($instanceName);
        echo "📱 Requesting QR code\n";
    }
    
    if ($result['success']) {
        echo "✅ SUCCESS\n";
        if (isset($result['data']['code'])) {
            echo "📱 QR Code: " . substr($result['data']['code'], 0, 50) . "... (length: " . strlen($result['data']['code']) . ")\n";
        }
        if (isset($result['data']['pairingCode'])) {
            echo "🔗 Pairing Code: " . $result['data']['pairingCode'] . "\n";
        }
    } else {
        displayResult("QR/Pairing Code", $result);
    }
}

function checkConnectionState() {
    global $instances, $currentInstance;
    
    $instanceName = getUserInput("Enter instance name (or press Enter for current): ");
    if (empty($instanceName) && $currentInstance) {
        $instanceName = $currentInstance;
    }
    
    if (empty($instanceName)) {
        echo "❌ No instance specified\n";
        return;
    }
    
    echo "📊 Checking connection state for '$instanceName'...\n";
    $result = $instances->connectionState($instanceName);
    
    if ($result['success']) {
        $state = $result['data']['instance']['state'] ?? 'unknown';
        echo "✅ Connection State: " . strtoupper($state) . "\n";
        
        switch ($state) {
            case 'open':
                echo "🟢 Instance is CONNECTED and ready\n";
                break;
            case 'close':
                echo "🔴 Instance is DISCONNECTED\n";
                break;
            case 'connecting':
                echo "🟡 Instance is CONNECTING\n";
                break;
            default:
                echo "⚪ Instance state: $state\n";
        }
    } else {
        displayResult("Connection State", $result);
    }
}

function restartInstance() {
    global $instances, $currentInstance;
    
    $instanceName = getUserInput("Enter instance name (or press Enter for current): ");
    if (empty($instanceName) && $currentInstance) {
        $instanceName = $currentInstance;
    }
    
    if (empty($instanceName)) {
        echo "❌ No instance specified\n";
        return;
    }
    
    $confirm = getUserInput("⚠️  Restart instance '$instanceName'? (y/N): ");
    if (strtolower($confirm) !== 'y') {
        echo "❌ Restart cancelled\n";
        return;
    }
    
    echo "🔄 Restarting '$instanceName'...\n";
    $result = $instances->restartInstance($instanceName);
    displayResult("Restart Instance", $result);
}

function logoutInstance() {
    global $instances, $currentInstance;
    
    $instanceName = getUserInput("Enter instance name (or press Enter for current): ");
    if (empty($instanceName) && $currentInstance) {
        $instanceName = $currentInstance;
    }
    
    if (empty($instanceName)) {
        echo "❌ No instance specified\n";
        return;
    }
    
    $confirm = getUserInput("⚠️  Logout instance '$instanceName'? (y/N): ");
    if (strtolower($confirm) !== 'y') {
        echo "❌ Logout cancelled\n";
        return;
    }
    
    echo "🚪 Logging out '$instanceName'...\n";
    $result = $instances->logoutInstance($instanceName);
    displayResult("Logout Instance", $result);
}

function deleteInstance() {
    global $instances, $currentInstance;
    
    $instanceName = getUserInput("Enter instance name to DELETE: ");
    if (empty($instanceName)) {
        echo "❌ Instance name cannot be empty\n";
        return;
    }
    
    echo "⚠️  WARNING: This will permanently delete '$instanceName'\n";
    $confirm1 = getUserInput("Type 'DELETE' to confirm: ");
    if ($confirm1 !== 'DELETE') {
        echo "❌ Deletion cancelled\n";
        return;
    }
    
    $confirm2 = getUserInput("Are you absolutely sure? (yes/no): ");
    if (strtolower($confirm2) !== 'yes') {
        echo "❌ Deletion cancelled\n";
        return;
    }
    
    echo "🗑️  Deleting '$instanceName'...\n";
    $result = $instances->deleteInstance($instanceName);
    displayResult("Delete Instance", $result);
    
    if ($result['success'] && $currentInstance === $instanceName) {
        $GLOBALS['currentInstance'] = null;
        echo "🎯 Current instance cleared\n";
    }
}

function manageSettings() {
    global $settings, $currentInstance;
    
    $instanceName = getUserInput("Enter instance name (or press Enter for current): ");
    if (empty($instanceName) && $currentInstance) {
        $instanceName = $currentInstance;
    }
    
    if (empty($instanceName)) {
        echo "❌ No instance specified\n";
        return;
    }
    
    echo "⚙️  Getting current settings for '$instanceName'...\n";
    $result = $settings->getCurrentSettings($instanceName);
    
    if ($result['success']) {
        echo "📋 Current Settings:\n";
        foreach ($result['settings'] as $key => $value) {
            $status = $value ? '✅' : '❌';
            echo "  $status $key: " . ($value ? 'true' : 'false') . "\n";
        }
        
        echo "\nAvailable actions:\n";
        echo "1. Toggle call rejection\n";
        echo "2. Toggle group ignore\n";
        echo "3. Toggle always online\n";
        echo "4. Toggle read messages\n";
        echo "5. Reset to defaults\n";
        echo "0. Back to main menu\n";
        
        $choice = getUserInput("\nChoose action (0-5): ");
        
        switch ($choice) {
            case '1':
                $newValue = !$result['settings']['rejectCall'];
                $updateResult = $settings->setCallSettings($newValue, '', $instanceName);
                displayResult("Toggle Call Rejection", $updateResult);
                break;
            case '2':
                $newValue = !$result['settings']['groupsIgnore'];
                $updateResult = $settings->setGroupSettings($newValue, $instanceName);
                displayResult("Toggle Groups Ignore", $updateResult);
                break;
            case '3':
                $newValue = !$result['settings']['alwaysOnline'];
                $updateResult = $settings->setOnlineSettings($newValue, $instanceName);
                displayResult("Toggle Always Online", $updateResult);
                break;
            case '4':
                $newValue = !$result['settings']['readMessages'];
                $updateResult = $settings->setReadSettings($newValue, $result['settings']['readStatus'], $instanceName);
                displayResult("Toggle Read Messages", $updateResult);
                break;
            case '5':
                $resetResult = $settings->resetToDefaults($instanceName);
                displayResult("Reset Settings", $resetResult);
                break;
            case '0':
                return;
            default:
                echo "❌ Invalid choice\n";
        }
    } else {
        displayResult("Get Settings", $result);
    }
}

function setCurrentInstance() {
    $instanceName = getUserInput("Enter instance name to set as current: ");
    if (empty($instanceName)) {
        echo "❌ Instance name cannot be empty\n";
        return;
    }
    
    // Verify instance exists
    $result = $GLOBALS['instances']->getInstanceInfo($instanceName);
    if ($result['success']) {
        $GLOBALS['currentInstance'] = $instanceName;
        echo "🎯 Current instance set to: $instanceName\n";
    } else {
        echo "❌ Instance '$instanceName' not found\n";
    }
}

// ======================
// MAIN LOOP
// ======================

function mainLoop() {
    global $running;
    
    while ($running) {
        clearScreen();
        printHeader();
        printMenu();
        
        $choice = getUserInput("Choose an option (0-10): ");
        
        echo "\n";
        
        switch ($choice) {
            case '1':
                listInstances();
                break;
            case '2':
                createInstance();
                break;
            case '3':
                getInstanceDetails();
                break;
            case '4':
                getQRCode();
                break;
            case '5':
                checkConnectionState();
                break;
            case '6':
                restartInstance();
                break;
            case '7':
                logoutInstance();
                break;
            case '8':
                deleteInstance();
                break;
            case '9':
                manageSettings();
                break;
            case '10':
                setCurrentInstance();
                break;
            case '0':
                echo "👋 Goodbye!\n";
                $running = false;
                break;
            default:
                echo "❌ Invalid choice. Please try again.\n";
        }
        
        if ($running) {
            waitForEnter();
        }
    }
}

// ======================
// START APPLICATION
// ======================

// Initial API test
echo "🚀 Initializing Evolution API Instance Manager...\n";
$apiTest = $GLOBALS['api']->getInformation();
if (!$apiTest['success']) {
    die("❌ Could not connect to Evolution API: " . $apiTest['error'] . "\n");
}

echo "✅ Connected to Evolution API\n";
echo "📋 Version: " . ($apiTest['data']['version'] ?? 'Unknown') . "\n";
sleep(1);

// Start the interactive loop
mainLoop();