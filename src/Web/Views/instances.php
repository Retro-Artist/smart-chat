<?php
// src/Web/Views/instances.php - REFACTORED WITH DYNAMIC CONNECTION CHECKING
// Following the same pattern as tests/instance-management.php

ob_start();

// Initialize dependencies - same as working tests
require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Api/Models/WhatsappInstance.php';

$userId = Helpers::getCurrentUserId();
$whatsappInstance = new WhatsAppInstance();

// Load configuration - same as working test files
$config = require __DIR__ . '/../../../config/config.php';

// Get session data
$message = $_SESSION['whatsapp_success'] ?? '';
$error = $_SESSION['whatsapp_error'] ?? '';
$qrCode = $_SESSION['whatsapp_qr_code'] ?? '';
$currentInstance = $_SESSION['whatsapp_current_instance'] ?? '';

// Clear session messages
unset($_SESSION['whatsapp_success'], $_SESSION['whatsapp_error'], $_SESSION['whatsapp_qr_code'], $_SESSION['whatsapp_current_instance']);

// Load instances
$instances = $whatsappInstance->getUserInstances($userId);
$activeInstance = $whatsappInstance->getUserActiveInstance($userId);

?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6" x-data="whatsappConnection()">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                WhatsApp Connection
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Connect your WhatsApp account to Smart Chat
            </p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <p class="text-green-800 dark:text-green-200"><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-200"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <!-- Dynamic Connection Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Connection Status</h2>
            
            <div x-show="!connectionStatus.checked" class="flex items-center space-x-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                <div>
                    <p class="text-blue-800 dark:text-blue-200 font-medium">Checking Connection Status...</p>
                </div>
            </div>

            <div x-show="connectionStatus.checked && connectionStatus.state === 'open'" class="flex items-center space-x-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                    <p class="text-green-800 dark:text-green-200 font-medium">WhatsApp Connected</p>
                    <p class="text-green-600 dark:text-green-400 text-sm" x-text="connectionStatus.instanceName"></p>
                </div>
            </div>

            <div x-show="connectionStatus.checked && connectionStatus.state === 'connecting'" class="flex items-center space-x-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></div>
                <div>
                    <p class="text-yellow-800 dark:text-yellow-200 font-medium">WhatsApp Connecting...</p>
                    <p class="text-yellow-600 dark:text-yellow-400 text-sm" x-text="connectionStatus.instanceName"></p>
                </div>
            </div>

            <div x-show="connectionStatus.checked && ['close', 'disconnected', 'not_connected'].includes(connectionStatus.state)" class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                <div>
                    <p class="text-red-800 dark:text-red-200 font-medium">WhatsApp Disconnected</p>
                    <p class="text-red-600 dark:text-red-400 text-sm" x-text="connectionStatus.instanceName || 'No active instance'"></p>
                </div>
            </div>
        </div>

        <!-- QR Code Section -->
        <?php if ($qrCode && $currentInstance): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6" x-show="connectionStatus.state !== 'open'">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 text-center">Scan QR Code</h2>
                
                <div class="text-center" x-show="!connectionStatus.connected">
                    <div class="bg-white p-4 rounded-lg inline-block mb-4">
                        <img src="<?= htmlspecialchars($qrCode) ?>" alt="WhatsApp QR Code" class="w-64 h-64 mx-auto">
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Open WhatsApp on your phone and scan this QR code to connect
                    </p>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                        <p class="text-blue-800 dark:text-blue-200 text-sm">
                            <strong>Instance:</strong> <?= htmlspecialchars($currentInstance) ?>
                        </p>
                        <p class="text-blue-600 dark:text-blue-400 text-sm mt-1">
                            Status updates automatically every 3 seconds
                        </p>
                    </div>

                    <button @click="checkConnectionStatus()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2 animate-spin" x-show="checkingStatus" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span x-text="checkingStatus ? 'Checking...' : 'Check Status Now'"></span>
                    </button>
                </div>

                <!-- Connection Success Message -->
                <div x-show="connectionStatus.state === 'open'" class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        WhatsApp Connected Successfully!
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4" x-text="'Instance: ' + connectionStatus.instanceName"></p>
                    <button @click="window.location.reload()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Continue to Dashboard
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Create New Instance -->
        <?php if (!$activeInstance || $activeInstance['status'] === 'deleted'): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Create WhatsApp Instance</h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Click the button below to create a new WhatsApp instance and generate a QR code.
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
                            Instance name will be automatically generated: smart-chat-<?= $userId ?>-<?= time() ?>
                        </p>
                    </div>
                    
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Create Instance & Get QR Code
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Existing Instances List -->
        <?php if (!empty($instances)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Your Instances</h2>
                
                <div class="space-y-3">
                    <?php foreach ($instances as $instance): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 <?= $instance['status'] === 'connected' ? 'bg-green-500' : ($instance['status'] === 'connecting' ? 'bg-yellow-500 animate-pulse' : 'bg-red-500') ?> rounded-full"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($instance['instance_name']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">
                                        <?= htmlspecialchars($instance['status']) ?>
                                        <?php if ($instance['phone_number']): ?>
                                            | <?= htmlspecialchars($instance['phone_number']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <?php if ($instance['status'] === 'connecting'): ?>
                                    <button @click="checkSpecificInstanceStatus('<?= htmlspecialchars($instance['instance_name']) ?>')" 
                                            class="text-green-600 hover:text-green-700 text-sm font-medium">
                                        Check Status
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($instance['status'] !== 'connected' && $instance['status'] !== 'deleted'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="get_qr">
                                        <input type="hidden" name="instance_name" value="<?= htmlspecialchars($instance['instance_name']) ?>">
                                        <button type="submit" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                            Get QR Code
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this instance?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="instance_name" value="<?= htmlspecialchars($instance['instance_name']) ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Alpine.js component for dynamic connection checking - using your existing API endpoints
function whatsappConnection() {
    return {
        connectionStatus: {
            checked: false,
            state: 'unknown',
            instanceName: '<?= $currentInstance ? htmlspecialchars($currentInstance) : '' ?>',
            connected: false
        },
        checkingStatus: false,
        pollingInterval: null,

        init() {
            // Check if we're on the right environment
            
            // Start checking connection status immediately if we have an instance
            <?php if ($currentInstance): ?>
                this.startPolling();
            <?php else: ?>
            <?php endif; ?>
        },

        async checkConnectionStatus() {
            if (!this.connectionStatus.instanceName) {
                return;
            }

            this.checkingStatus = true;
            
            try {
                // Build the API URL - use relative path to avoid localhost issues
                const apiUrl = `/api/whatsapp/status?instance=${encodeURIComponent(this.connectionStatus.instanceName)}`;
                
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                let data;
                const responseText = await response.text();
                
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('Invalid JSON response: ' + responseText.substring(0, 100));
                }
                
                
                if (response.ok && data.success && data.data && data.data.instance) {
                    const instance = data.data.instance;
                    this.connectionStatus.state = instance.state || 'unknown';
                    this.connectionStatus.instanceName = instance.instanceName || this.connectionStatus.instanceName;
                    this.connectionStatus.checked = true;
                    
                    
                    // Handle different states - same logic as tests/instance-management.php
                    switch (this.connectionStatus.state) {
                        case 'open':
                            this.connectionStatus.connected = true;
                            this.stopPolling(); // Stop polling when connected
                            break;
                        case 'close':
                        case 'disconnected':
                            this.connectionStatus.connected = false;
                            break;
                        case 'connecting':
                            this.connectionStatus.connected = false;
                            break;
                        default:
                            this.connectionStatus.connected = false;
                    }
                } else {
                    const errorMsg = data?.error || `HTTP ${response.status}: ${response.statusText}`;
                    
                    this.connectionStatus.checked = true;
                    this.connectionStatus.state = 'error';
                }
            } catch (error) {
                // Error occurred during connection check
                this.connectionStatus.checked = true;
                this.connectionStatus.state = 'error';
            } finally {
                this.checkingStatus = false;
            }
        },

        async checkSpecificInstanceStatus(instanceName) {
            const oldInstanceName = this.connectionStatus.instanceName;
            this.connectionStatus.instanceName = instanceName;
            await this.checkConnectionStatus();
            
            // If the specific instance check failed, restore the old instance name
            if (this.connectionStatus.state === 'error') {
                this.connectionStatus.instanceName = oldInstanceName;
            }
        },

        startPolling() {
            // Initial check
            this.checkConnectionStatus();
            
            // Poll every 3 seconds - same as your test files
            this.pollingInterval = setInterval(() => {
                // Only continue polling if not connected
                if (!this.connectionStatus.connected) {
                    this.checkConnectionStatus();
                }
            }, 3000);
        },

        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        },

        destroy() {
            this.stopPolling();
        }
    }
}

// Handle page visibility change to pause/resume polling
document.addEventListener('visibilitychange', function() {
    const component = Alpine.$data(document.querySelector('[x-data]'));
    if (component) {
        if (document.visibilityState === 'visible') {
            if (!component.connectionStatus.connected && component.connectionStatus.instanceName) {
                component.startPolling();
            }
        } else {
            component.stopPolling();
        }
    }
});
</script>

<?php
$content = ob_get_clean();

// Include layout wrapper
include __DIR__ . '/layout.php';
?>