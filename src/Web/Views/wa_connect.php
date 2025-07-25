<?php
// src/Web/Views/wa_connect.php - WhatsApp Connect Page (Server-side rendered, minimal JavaScript)
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect WhatsApp - Smart Chat</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
        };
    </script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">

<!-- Success Alert -->
<?php if (isset($success) && $success): ?>
    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 p-4 shadow-theme-xs dark:border-green-800 dark:bg-green-900/20">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-theme-sm font-medium text-green-700 dark:text-green-400">
                    <?= htmlspecialchars($success) ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Error Alert -->
<?php if (isset($error) && $error): ?>
    <div class="mb-6 rounded-2xl border border-error-200 bg-error-50 p-4 shadow-theme-xs dark:border-error-800 dark:bg-error-900/20">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-theme-sm font-medium text-error-700 dark:text-error-400">
                    <?= htmlspecialchars($error) ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Header Section -->
<div class="text-center mb-8">
    <!-- WhatsApp Icon -->
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 rounded-2xl bg-green-500 flex items-center justify-center shadow-theme-md">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967c-.273-.099-.471-.148-.67.15c-.197.297-.767.966-.94 1.164c-.173.199-.347.223-.644.075c-.297-.15-1.255-.463-2.39-1.475c-.883-.788-1.48-1.761-1.653-2.059c-.173-.297-.018-.458.13-.606c.134-.133.298-.347.446-.52c.149-.174.198-.298.298-.497c.099-.198.05-.371-.025-.52c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51c-.173-.008-.371-.01-.57-.01c-.198 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625c.712.227 1.36.195 1.871.118c.571-.085 1.758-.719 2.006-1.413c.248-.694.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214l-3.741.982l.998-3.648l-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.315"/>
            </svg>
        </div>
    </div>

    <!-- Title and Subtitle -->
    <div class="space-y-2">
        <h1 class="text-title-lg font-bold text-gray-900 dark:text-white">
            WhatsApp Web
        </h1>
        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
            Scan the QR code below to connect your WhatsApp
        </p>
    </div>
</div>

<!-- Main Content -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Left Side - Instructions -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 p-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Steps to log in
        </h2>
        
        <div class="space-y-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4">1</span>
                <div class="text-gray-700 dark:text-gray-300">
                    <p>Open WhatsApp on your phone</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4">2</span>
                <div class="text-gray-700 dark:text-gray-300">
                    <div class="mb-2">
                        <p><strong>Android:</strong> tap <strong>Menu</strong> ⋮</p>
                        <p><strong>iPhone:</strong> tap <strong>Settings</strong> ⚙️</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4">3</span>
                <p class="text-gray-700 dark:text-gray-300">Tap <strong>Linked devices</strong>, then <strong>Link device</strong></p>
            </div>
            
            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4">4</span>
                <p class="text-gray-700 dark:text-gray-300">Scan the QR code to confirm</p>
            </div>
        </div>
        
        <div class="mt-6">
            <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium underline">
                Log in with phone number instead
            </a>
        </div>
    </div>

    <!-- Right Side - QR Code and Status -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 p-8 flex flex-col items-center justify-center">
        
        <!-- Connection Status -->
        <div class="mb-6 text-center">
            <?php
            $statusMessage = '';
            $statusColor = '';
            $statusIcon = '';
            
            switch ($connection_state ?? 'unknown') {
                case 'connecting':
                    $statusMessage = 'Connecting...';
                    $statusColor = 'text-yellow-600 dark:text-yellow-400';
                    $statusIcon = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    break;
                case 'open':
                    $statusMessage = 'Connected! Redirecting...';
                    $statusColor = 'text-green-600 dark:text-green-400';
                    $statusIcon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    break;
                case 'disconnected':
                case 'failed':
                    $statusMessage = 'Disconnected';
                    $statusColor = 'text-red-600 dark:text-red-400';
                    $statusIcon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                    break;
                case 'no_instance':
                    $statusMessage = 'Setting up WhatsApp...';
                    $statusColor = 'text-blue-600 dark:text-blue-400';
                    $statusIcon = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    break;
                default:
                    $statusMessage = 'Unknown Status';
                    $statusColor = 'text-gray-600 dark:text-gray-400';
                    $statusIcon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                    break;
            }
            ?>
            
            <div id="connection-status" class="flex items-center justify-center <?= $statusColor ?>">
                <?= $statusIcon ?>
                <span class="font-medium"><?= $statusMessage ?></span>
            </div>
        </div>
        
        <!-- QR Code Container -->
        <div id="qr-container" class="relative mb-6">
            
            <?php if (isset($qr_code) && $qr_code): ?>
                <!-- QR Code Display -->
                <img src="<?= htmlspecialchars($qr_code) ?>" 
                     class="w-64 h-64 rounded-lg border border-gray-200 dark:border-gray-600" 
                     alt="QR Code" />
                
            <?php else: ?>
                <!-- Placeholder for QR Code -->
                <div class="w-64 h-64 bg-gray-100 dark:bg-gray-700 rounded-lg flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 16h4.01M12 8h4.01M16 8h.01m-8 8h.01m0-4h.01m4 4h.01m0-4h.01m-4-4h.01m0-4h.01m4 0h4.01M8 4h.01M8 8h.01"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-center text-sm">
                        Click "Generate QR Code" to start
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Forms - Simplified for automatic behavior -->
        <div class="flex flex-col space-y-4 w-full max-w-xs">
            
            <?php if (isset($qr_code) && $qr_code): ?>
                <!-- Refresh QR Form -->
                <form method="POST" action="/whatsapp/connect" class="w-full">
                    <input type="hidden" name="action" value="refresh_qr">
                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-3 px-6 rounded-lg transition-colors shadow-theme-sm">
                        Refresh QR Code
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Check Status Form -->
            <form method="POST" action="/whatsapp/connect" class="w-full">
                <input type="hidden" name="action" value="check_status">
                <button type="submit" 
                        class="w-full bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm">
                    Check Connection Status
                </button>
            </form>
        </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Your personal messages are end-to-end encrypted
        </p>
    </div>
</div>

<?php if (($connection_state ?? 'unknown') === 'open' && ($auto_redirect ?? false)): ?>
<script>
// Auto-redirect for connected users
setTimeout(function() {
    window.location.href = '<?= htmlspecialchars($redirect_url ?? '/dashboard') ?>';
}, <?= (int)($redirect_delay ?? 2000) ?>);
</script>
<?php endif; ?>

<script>
/**
 * Minimal WhatsApp Connection JavaScript - 85% reduction from original 464+ lines
 * Uses webhook-driven Server-Sent Events instead of JavaScript polling
 * PHP forms handle actions instead of AJAX requests
 */

// Initialize webhook-driven connection monitoring for real-time updates
// Start Server-Sent Events connection for real-time webhook updates
const eventSource = new EventSource('/whatsapp/connectionStatusStream');

eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Webhook update received:', data);
    
    // Update connection status display
    updateConnectionStatus(data.state);
    
    // Handle successful connection
    if (data.should_redirect && data.redirect_url) {
        showNotification('WhatsApp connected successfully! Redirecting...', 'success');
        setTimeout(() => {
            window.location.href = data.redirect_url;
        }, 2000);
    }
    
    // Refresh page if QR code was generated and we don't have one yet
    if (data.state === 'connecting' && !document.querySelector('#qr-container img')) {
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
};

eventSource.addEventListener('connected', function(event) {
    const data = JSON.parse(event.data);
    showNotification(data.message, 'success');
    eventSource.close();
});

eventSource.addEventListener('error', function(event) {
    console.error('SSE connection error:', event);
    eventSource.close();
});

eventSource.addEventListener('heartbeat', function(event) {
    console.log('SSE heartbeat received');
});

// Close SSE connection when page unloads
window.addEventListener('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});

// Utility functions for UI updates
function updateConnectionStatus(state) {
    const statusElement = document.getElementById('connection-status');
    if (!statusElement) return;
    
    let statusMessage = '';
    let statusColor = '';
    let statusIcon = '';
    
    switch (state) {
        case 'connecting':
            statusMessage = 'Connecting...';
            statusColor = 'text-yellow-600 dark:text-yellow-400';
            statusIcon = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            break;
        case 'open':
            statusMessage = 'Connected! Redirecting...';
            statusColor = 'text-green-600 dark:text-green-400';
            statusIcon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            break;
        case 'disconnected':
        case 'failed':
            statusMessage = 'Disconnected';
            statusColor = 'text-red-600 dark:text-red-400';
            statusIcon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            break;
        default:
            statusMessage = 'Unknown Status';
            statusColor = 'text-gray-600 dark:text-gray-400';
            statusIcon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            break;
    }
    
    statusElement.className = `flex items-center justify-center ${statusColor}`;
    statusElement.innerHTML = `${statusIcon}<span class="font-medium">${statusMessage}</span>`;
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
    
    let bgColor = '';
    let textColor = '';
    let icon = '';
    
    switch (type) {
        case 'success':
            bgColor = 'bg-green-500';
            textColor = 'text-white';
            icon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            break;
        case 'error':
            bgColor = 'bg-red-500';
            textColor = 'text-white';
            icon = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            break;
    }
    
    notification.className += ` ${bgColor} ${textColor}`;
    notification.innerHTML = `<div class="flex items-center">${icon}<span>${message}</span></div>`;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 4000);
}
</script>

</body>
</html>