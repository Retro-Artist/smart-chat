<?php
// src/Web/Views/whatsapp-connect.php - SIMPLE WORKING VERSION
// Based on the exact working pattern from your tests

ob_start();

// Initialize the WhatsApp instance - same as working tests
require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../Models/WhatsAppInstance.php';

$userId = Helpers::getCurrentUserId();
$whatsappInstance = new WhatsAppInstance();

// Get session messages and QR code data
$message = $_SESSION['whatsapp_success'] ?? '';
$error = $_SESSION['whatsapp_error'] ?? '';
$qrCode = $_SESSION['whatsapp_qr_code'] ?? '';
$currentInstance = $_SESSION['whatsapp_current_instance'] ?? '';

// Clear session messages
unset($_SESSION['whatsapp_success']);
unset($_SESSION['whatsapp_error']);
unset($_SESSION['whatsapp_qr_code']);
unset($_SESSION['whatsapp_current_instance']);

// Load instances
$instances = $whatsappInstance->getUserInstances($userId);
$activeInstance = $whatsappInstance->getUserActiveInstance($userId);

?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                WhatsApp Connection
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Simple and working WhatsApp connection interface
            </p>
        </div>

        <!-- Debug Section (remove in production) -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Debug Info</h3>
            <div class="text-xs text-yellow-700 dark:text-yellow-300 space-y-1">
                <p>Message: <?= htmlspecialchars($message ?: 'None') ?></p>
                <p>Error: <?= htmlspecialchars($error ?: 'None') ?></p>
                <p>Has QR Code: <?= $qrCode ? 'Yes (' . strlen($qrCode) . ' chars)' : 'No' ?></p>
                <?php if ($qrCode): ?>
                    <p>QR Code starts with: <?= htmlspecialchars(substr($qrCode, 0, 50)) ?>...</p>
                    <p>Is Data URI: <?= strpos($qrCode, 'data:image/') === 0 ? 'Yes' : 'No' ?></p>
                <?php endif; ?>
                <p>Current Instance: <?= htmlspecialchars($currentInstance ?: 'None') ?></p>
                <p>Total Instances: <?= count($instances) ?></p>
                <p>Active Instance: <?= $activeInstance ? htmlspecialchars($activeInstance['instance_name']) : 'None' ?></p>
                <?php if (!empty($instances)): ?>
                    <p>Instance Statuses:</p>
                    <?php foreach ($instances as $inst): ?>
                        <p class="ml-4">- <?= htmlspecialchars($inst['instance_name']) ?>: <?= htmlspecialchars($inst['status']) ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Connection Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Connection Status</h2>
            
            <?php if ($activeInstance): ?>
                <?php if ($activeInstance['status'] === 'deleted'): ?>
                    <div class="flex items-center space-x-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                        <div>
                            <p class="text-red-800 dark:text-red-200 font-medium">Instance Deleted or Altered</p>
                            <p class="text-red-600 dark:text-red-400 text-sm">
                                Instance: <?= htmlspecialchars($activeInstance['instance_name']) ?> was deleted externally
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex items-center space-x-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <div>
                            <p class="text-green-800 dark:text-green-200 font-medium">WhatsApp Connected</p>
                            <p class="text-green-600 dark:text-green-400 text-sm">
                                Instance: <?= htmlspecialchars($activeInstance['instance_name']) ?>
                                <?php if ($activeInstance['phone_number']): ?>
                                    | Phone: <?= htmlspecialchars($activeInstance['phone_number']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <div>
                        <p class="text-gray-700 dark:text-gray-300 font-medium">No WhatsApp Connection</p>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Create an instance to get started</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- QR Code Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">QR Code Connection</h2>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="create">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create New Instance
                    </button>
                </form>
            </div>

            <?php if ($qrCode): ?>
                <div class="text-center py-8">
                    <div class="relative">
                        <div class="bg-white p-4 rounded-lg shadow-md mb-4 inline-block relative">
                            <?php 
                            // Ensure QR code is in proper data URI format
                            $qrCodeSrc = $qrCode;
                            if (strpos($qrCode, 'data:image/') !== 0) {
                                // If it's not already a data URI, try to format it
                                $qrCodeSrc = 'data:image/png;base64,' . $qrCode;
                            }
                            ?>
                            <img id="qr-image" src="<?= $qrCodeSrc ?>" alt="WhatsApp QR Code" class="w-64 h-64 object-contain" onerror="this.style.display='none'; document.getElementById('qr-error').style.display='block';">
                            <div id="qr-error" style="display: none;" class="w-64 h-64 flex items-center justify-center bg-gray-100 text-red-500">
                                <div class="text-center">
                                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <p class="text-sm">QR Code display error</p>
                                </div>
                            </div>
                            <!-- Expiration overlay -->
                            <div id="qr-expired" style="display: none;" class="absolute inset-0 bg-gray-900 bg-opacity-75 rounded-lg flex items-center justify-center">
                                <div class="text-center text-white">
                                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm font-medium">QR Code Expired</p>
                                </div>
                            </div>
                        </div>
                        <!-- Timer display -->
                        <div id="qr-timer" class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                QR Code expires in: <span id="timer-display" class="font-mono font-bold">2:00</span>
                            </p>
                            <div class="w-64 mx-auto mt-2 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div id="timer-progress" class="bg-blue-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Scan QR Code with WhatsApp
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-4">
                            Open WhatsApp on your phone → Settings → Linked Devices → Link a Device → Scan this QR code
                        </p>
                        <?php if ($currentInstance): ?>
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Instance: <?= htmlspecialchars($currentInstance) ?>
                                </p>
                                <!-- Regenerate QR Code button -->
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="action" value="get_qr">
                                    <input type="hidden" name="instance_name" value="<?= htmlspecialchars($currentInstance) ?>">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Regenerate QR Code
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">
                        No QR code available. Create an instance to get started.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Instance Management -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Instance Management</h2>

            <?php if (empty($instances)): ?>
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m0 0V9a2 2 0 012-2h8a2 2 0 012 2v4m-6 0a2 2 0 100 4 2 2 0 000-4zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v4"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No instances created yet</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($instances as $instance): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?= 
                                    $instance['status'] === 'connected' ? 'bg-green-100 dark:bg-green-800' : 
                                    ($instance['status'] === 'connecting' ? 'bg-yellow-100 dark:bg-yellow-800' : 
                                    ($instance['status'] === 'deleted' ? 'bg-red-100 dark:bg-red-800' : 'bg-gray-100 dark:bg-gray-700')) 
                                ?>">
                                    <?php if ($instance['status'] === 'deleted'): ?>
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-5 h-5 <?= 
                                            $instance['status'] === 'connected' ? 'text-green-600 dark:text-green-400' : 
                                            ($instance['status'] === 'connecting' ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-400')
                                        ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($instance['instance_name']) ?>
                                    </p>
                                    <p class="text-xs <?= $instance['status'] === 'deleted' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' ?> capitalize">
                                        <?= $instance['status'] === 'deleted' ? 'Deleted externally' : htmlspecialchars($instance['status']) ?>
                                        <?php if ($instance['phone_number'] && $instance['status'] !== 'deleted'): ?>
                                            | <?= htmlspecialchars($instance['phone_number']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($instance['status'] === 'connecting'): ?>
                                    <button onclick="checkInstanceStatus('<?= htmlspecialchars($instance['instance_name']) ?>')" class="text-green-600 hover:text-green-700 text-sm font-medium">
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
            <?php endif; ?>
        </div>

        <!-- Auto-refresh script for QR codes -->
        <script>
            // Success handler - hide QR code and show success message
            function handleConnectionSuccess(instanceName, phoneNumber, message) {
                console.log('🎉 Connection successful!', { instanceName, phoneNumber, message });
                
                // Hide QR code section
                const qrSection = document.querySelector('.bg-white.dark\\:bg-gray-800.rounded-lg.shadow-lg.p-6.mb-6');
                if (qrSection && qrSection.querySelector('#qr-image')) {
                    qrSection.innerHTML = `
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                WhatsApp Connected Successfully!
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Instance: ${instanceName}${phoneNumber ? `<br>Phone: ${phoneNumber}` : ''}
                            </p>
                            <button onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Refresh Page
                            </button>
                        </div>
                    `;
                }
                
                // Update connection status section
                const statusSection = document.querySelector('.bg-white.dark\\:bg-gray-800.rounded-lg.shadow-lg.p-6.mb-6 h2');
                if (statusSection && statusSection.textContent.includes('Connection Status')) {
                    const statusContent = statusSection.parentElement;
                    const newStatusHtml = `
                        <div class="flex items-center space-x-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <div>
                                <p class="text-green-800 dark:text-green-200 font-medium">WhatsApp Connected</p>
                                <p class="text-green-600 dark:text-green-400 text-sm">
                                    Instance: ${instanceName}${phoneNumber ? ` | Phone: ${phoneNumber}` : ''}
                                </p>
                            </div>
                        </div>
                    `;
                    statusContent.innerHTML = '<h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Connection Status</h2>' + newStatusHtml;
                }
            }
            
            // Polling function using Evolution API directly
            function pollConnectionStatus(instanceName) {
                console.log('📡 Polling connection status for:', instanceName);
                
                // Call Evolution API directly like the test files do
                const evolutionUrl = '<?= $this->config['evolutionAPI']['api_url'] ?? 'https://evo.ubilabs.com.br' ?>';
                const apiKey = '<?= $this->config['evolutionAPI']['api_key'] ?? '' ?>';
                
                fetch(`${evolutionUrl}/instance/connectionState/${encodeURIComponent(instanceName)}`, {
                    headers: {
                        'apikey': apiKey,
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('📡 Evolution API response:', data);
                        
                        if (data.instance && data.instance.state) {
                            const state = data.instance.state;
                            console.log(`📡 Instance state: ${state}`);
                            
                            if (state === 'open') {
                                // Connection successful!
                                const phoneNumber = data.instance.wuid || null;
                                const profileName = data.instance.profileName || null;
                                handleConnectionSuccess(instanceName, phoneNumber, 'WhatsApp connected successfully!');
                            } else {
                                console.log(`⏳ Status: ${state} for ${instanceName}`);
                            }
                        } else {
                            console.log(`❌ Poll error: ${data.error || 'Unknown error'}`);
                        }
                    })
                    .catch(error => {
                        console.error(`Error polling ${instanceName}:`, error);
                    });
            }
            
            // Function to manually check instance status
            function checkInstanceStatus(instanceName) {
                console.log('🔍 Manual status check for:', instanceName);
                pollConnectionStatus(instanceName);
            }
            
            // Simple polling for connecting instances
            <?php if (!empty($instances)): ?>
                <?php 
                $connectingInstances = array_filter($instances, function($inst) { 
                    return $inst['status'] === 'connecting'; 
                });
                ?>
                <?php if (!empty($connectingInstances)): ?>
                    console.log('🔍 Found connecting instances:', <?= json_encode(array_column($connectingInstances, 'instance_name')) ?>);
                    
                    // Poll every 10 seconds for any connecting instance
                    const connectingInstanceNames = <?= json_encode(array_column($connectingInstances, 'instance_name')) ?>;
                    
                    function checkAllConnectingInstances() {
                        connectingInstanceNames.forEach(instanceName => {
                            pollConnectionStatus(instanceName);
                        });
                    }
                    
                    // Start polling immediately and then every 10 seconds
                    checkAllConnectingInstances();
                    const globalPollingInterval = setInterval(checkAllConnectingInstances, 10000);
                    
                    // Clean up when page unloads
                    window.addEventListener('beforeunload', () => {
                        clearInterval(globalPollingInterval);
                    });
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($qrCode && $currentInstance): ?>
                // QR Code expiration timer (120 seconds)
                let qrExpirationTime = 120; // seconds
                let remainingTime = qrExpirationTime;
                let timerInterval;
                let refreshInterval;
                
                function updateTimer() {
                    remainingTime--;
                    
                    if (remainingTime <= 0) {
                        clearInterval(timerInterval);
                        clearInterval(refreshInterval);
                        
                        // Show expired overlay
                        document.getElementById('qr-expired').style.display = 'flex';
                        document.getElementById('qr-timer').style.display = 'none';
                        
                        return;
                    }
                    
                    // Update timer display
                    const minutes = Math.floor(remainingTime / 60);
                    const seconds = remainingTime % 60;
                    document.getElementById('timer-display').textContent = 
                        minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                    
                    // Update progress bar
                    const progress = (remainingTime / qrExpirationTime) * 100;
                    document.getElementById('timer-progress').style.width = progress + '%';
                    
                    // Change progress bar color based on remaining time
                    const progressBar = document.getElementById('timer-progress');
                    if (remainingTime <= 20) {
                        progressBar.classList.remove('bg-blue-600', 'bg-yellow-500');
                        progressBar.classList.add('bg-red-500');
                    } else if (remainingTime <= 60) {
                        progressBar.classList.remove('bg-blue-600', 'bg-red-500');
                        progressBar.classList.add('bg-yellow-500');
                    }
                }
                
                // Start timer
                timerInterval = setInterval(updateTimer, 1000);
                
                // Auto-refresh every 10 seconds to check for connection
                refreshInterval = setInterval(function() {
                    // Only refresh if QR hasn't expired and page is visible
                    if (remainingTime > 0 && document.visibilityState === 'visible') {
                        console.log('🔍 Checking connection status for QR instance...');
                        pollConnectionStatus('<?= htmlspecialchars($currentInstance) ?>');
                    }
                }, 10000); // 10 seconds
                
                // Stop timers when page is hidden
                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'hidden') {
                        clearInterval(timerInterval);
                        clearInterval(refreshInterval);
                    } else if (document.visibilityState === 'visible' && remainingTime > 0) {
                        // Resume timers when page becomes visible again
                        timerInterval = setInterval(updateTimer, 1000);
                        refreshInterval = setInterval(function() {
                            if (remainingTime > 0) {
                                pollConnectionStatus('<?= htmlspecialchars($currentInstance) ?>');
                            }
                        }, 10000);
                    }
                });
            <?php endif; ?>
        </script>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $pageTitle ?? 'Connect WhatsApp - Smart Chat';
include __DIR__ . '/layout.php';
?>