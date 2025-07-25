<?php
// src/Web/Views/wa_connect.php
require_once __DIR__ . '/../../Core/Helpers.php';

$instance = $data['instance'] ?? null;
$error = $data['error'] ?? null;
$isFirstLogin = $data['is_first_login'] ?? false;
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center" x-data="whatsappConnect()">
    <div class="max-w-4xl w-full mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967c-.273-.099-.471-.148-.67.15c-.197.297-.767.966-.94 1.164c-.173.199-.347.223-.644.075c-.297-.15-1.255-.463-2.39-1.475c-.883-.788-1.48-1.761-1.653-2.059c-.173-.297-.018-.458.13-.606c.134-.133.298-.347.446-.52c.149-.174.198-.298.298-.497c.099-.198.05-.371-.025-.52c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51c-.173-.008-.371-.01-.57-.01c-.198 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625c.712.227 1.36.195 1.871.118c.571-.085 1.758-.719 2.006-1.413c.248-.694.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214l-3.741.982l.998-3.648l-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.315"/>
                </svg>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white ml-3">WhatsApp Web</h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= $isFirstLogin ? 'Connect your WhatsApp account to get started' : 'Reconnect your WhatsApp account' ?>
            </p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Connection Error</h3>
                    <p class="text-sm text-red-700 dark:text-red-300 mt-1"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="lg:flex">
                
                <!-- Left Side - Instructions -->
                <div class="lg:w-1/2 p-8 bg-green-50 dark:bg-green-900/20">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                        Steps to log in
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4">1</span>
                            <div class="text-gray-700 dark:text-gray-300">
                                <p>Open WhatsApp <svg class="inline w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967c-.273-.099-.471-.148-.67.15c-.197.297-.767.966-.94 1.164c-.173.199-.347.223-.644.075c-.297-.15-1.255-.463-2.39-1.475c-.883-.788-1.48-1.761-1.653-2.059c-.173-.297-.018-.458.13-.606c.134-.133.298-.347.446-.52c.149-.174.198-.298.298-.497c.099-.198.05-.371-.025-.52c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51c-.173-.008-.371-.01-.57-.01c-.198 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625c.712.227 1.36.195 1.871.118c.571-.085 1.758-.719 2.006-1.413c.248-.694.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214l-3.741.982l.998-3.648l-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.315"/></svg></p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4">2</span>
                            <div class="text-gray-700 dark:text-gray-300">
                                <div class="mb-2">
                                    <p><strong>Android:</strong> tap <strong>Menu</strong> <svg class="inline w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg></p>
                                    <p><strong>iPhone:</strong> tap <strong>Settings</strong> <svg class="inline w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 24 24"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg></p>
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
                    
                    <div class="mt-8">
                        <button 
                            @click="checkStatus()"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                            :disabled="loading"
                        >
                            <span x-show="!loading">Check Connection Status</span>
                            <span x-show="loading" class="inline-flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Checking...
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Right Side - QR Code -->
                <div class="lg:w-1/2 p-8 flex flex-col items-center justify-center">
                    
                    <!-- Connection Status -->
                    <div class="mb-6 text-center">
                        <div x-show="status === 'connecting'" class="flex items-center text-yellow-600 dark:text-yellow-400">
                            <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="font-medium">Connecting...</span>
                        </div>
                        
                        <div x-show="status === 'open'" class="flex items-center text-green-600 dark:text-green-400">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="font-medium">Connected! Redirecting...</span>
                        </div>
                        
                        <div x-show="status === 'disconnected' || status === 'failed'" class="flex items-center text-red-600 dark:text-red-400">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="font-medium">Disconnected</span>
                        </div>
                    </div>
                    
                    <!-- QR Code Container -->
                    <div class="relative">
                        <div 
                            x-show="!qrCode && !loading" 
                            class="w-64 h-64 bg-gray-100 dark:bg-gray-700 rounded-lg flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600"
                        >
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 16h4.01M12 8h4.01M16 8h.01m-8 8h.01m0-4h.01m4 4h.01m0-4h.01m-4-4h.01m0-4h.01m4 0h4.01M8 4h.01M8 8h.01"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-center text-sm">
                                Click "Generate QR Code" to start
                            </p>
                        </div>
                        
                        <div 
                            x-show="loading" 
                            class="w-64 h-64 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center"
                        >
                            <svg class="animate-spin w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        
                        <img 
                            x-show="qrCode" 
                            :src="qrCode" 
                            class="w-64 h-64 rounded-lg border border-gray-200 dark:border-gray-600"
                            alt="QR Code"
                        />
                        
                        <!-- QR Code Expired Overlay -->
                        <div 
                            x-show="qrExpired" 
                            class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center"
                        >
                            <div class="text-center text-white">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-medium">QR Code Expired</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Generate QR Button -->
                    <button 
                        @click="generateQR()"
                        x-show="!qrCode || qrExpired"
                        class="mt-6 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors"
                        :disabled="loading"
                    >
                        <span x-show="!loading">Generate QR Code</span>
                        <span x-show="loading">Generating...</span>
                    </button>
                    
                    <!-- Refresh QR Button -->
                    <button 
                        @click="generateQR()"
                        x-show="qrCode && !qrExpired"
                        class="mt-4 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
                        :disabled="loading"
                    >
                        Refresh QR Code
                    </button>
                    
                    <!-- Manual Actions -->
                    <div class="mt-8 flex space-x-4">
                        <button 
                            @click="restartInstance()"
                            class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 text-sm font-medium"
                            :disabled="loading"
                        >
                            Restart Connection
                        </button>
                        
                        <button 
                            @click="disconnect()"
                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 text-sm font-medium"
                            :disabled="loading"
                        >
                            Disconnect
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Your personal messages are end-to-end encrypted
            </p>
        </div>
    </div>
</div>

<script>
function whatsappConnect() {
    return {
        loading: false,
        status: '<?= $data['connection_state'] ?? 'unknown' ?>',
        qrCode: '<?= $data['qr_code'] ?? '' ?>',
        qrExpired: false,
        statusCheckInterval: null,
        qrExpiryTimer: null,
        autoRedirect: <?= json_encode($data['auto_redirect'] ?? false) ?>,
        redirectUrl: '<?= $data['redirect_url'] ?? '/dashboard' ?>',
        redirectDelay: <?= $data['redirect_delay'] ?? 2000 ?>,
        
        init() {
            console.log('WhatsApp Connect initialized with state:', this.status);
            
            // Handle auto-redirect for already connected users
            if (this.autoRedirect && this.status === 'open') {
                console.log('Auto-redirecting to dashboard in', this.redirectDelay, 'ms');
                setTimeout(() => {
                    window.location.href = this.redirectUrl;
                }, this.redirectDelay);
                return;
            }
            
            // Start real-time connection monitoring
            this.startStatusChecking();
            
            // Initialize QR expiry timer if QR code exists
            if (this.qrCode) {
                this.startQRExpiryTimer();
            }
            
            // Auto-generate QR if needed
            if (!this.qrCode && this.status !== 'open') {
                setTimeout(() => this.generateQR(), 1000);
            }
        },
        
        async checkStatus() {
            try {
                const response = await fetch('/whatsapp/getConnectionState');
                const data = await response.json();
                
                if (data.success) {
                    const newStatus = data.state;
                    console.log('Connection state update:', this.status, '->', newStatus);
                    
                    this.status = newStatus;
                    
                    // Auto-redirect to dashboard when connection is established
                    if (newStatus === 'open') {
                        console.log('Connection established! Redirecting to dashboard...');
                        setTimeout(() => {
                            window.location.href = '/dashboard';
                        }, 2000);
                    }
                } else {
                    console.error('Status check failed:', data.error);
                    // Handle specific error states
                    if (data.state) {
                        this.status = data.state;
                    }
                }
            } catch (error) {
                console.error('Status check error:', error);
                this.status = 'unknown';
            }
        },
        
        async generateQR() {
            this.loading = true;
            this.qrExpired = false;
            
            try {
                const response = await fetch('/whatsapp/generateQR', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    this.qrCode = data.qr_code;
                    this.status = data.state || this.status;
                    this.startQRExpiryTimer();
                    
                    // Handle redirect if already connected
                    if (data.should_redirect && data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1000);
                    }
                } else {
                    // Handle specific error states
                    if (data.should_redirect && data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                    
                    if (data.state) {
                        this.status = data.state;
                    }
                    
                    alert('Failed to generate QR code: ' + data.error);
                }
            } catch (error) {
                console.error('QR generation error:', error);
                alert('Failed to generate QR code. Please try again.');
            }
            
            this.loading = false;
        },
        
        async restartInstance() {
            this.loading = true;
            
            try {
                const response = await fetch('/whatsapp/restartInstance', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    console.log('Instance restarted:', data.instance_name);
                    this.status = 'connecting';
                    this.qrCode = null;
                    this.qrExpired = false;
                    
                    // Generate new QR after restart
                    setTimeout(() => this.generateQR(), 2000);
                } else {
                    alert('Failed to restart instance: ' + data.error);
                }
            } catch (error) {
                console.error('Restart error:', error);
                alert('Failed to restart instance. Please try again.');
            }
            
            this.loading = false;
        },
        
        async disconnect() {
            if (!confirm('Are you sure you want to disconnect WhatsApp?')) {
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch('/api/whatsapp/disconnect', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    this.status = 'disconnected';
                    this.qrCode = null;
                    this.qrExpired = false;
                } else {
                    alert('Failed to disconnect: ' + data.error);
                }
            } catch (error) {
                console.error('Disconnect error:', error);
                alert('Failed to disconnect. Please try again.');
            }
            
            this.loading = false;
        },
        
        startStatusChecking() {
            // Check status every 3 seconds for better responsiveness
            this.statusCheckInterval = setInterval(() => {
                this.checkStatus();
            }, 3000);
        },
        
        startQRExpiryTimer() {
            // QR codes expire after 60 seconds
            this.qrExpiryTimer = setTimeout(() => {
                this.qrExpired = true;
            }, 60000);
        },
        
        destroy() {
            if (this.statusCheckInterval) {
                clearInterval(this.statusCheckInterval);
            }
            if (this.qrExpiryTimer) {
                clearTimeout(this.qrExpiryTimer);
            }
        }
    }
}
</script>