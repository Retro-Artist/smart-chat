<?php
// src/Web/Views/whatsapp-connect.php - Fixed layout usage

// Start output buffering to capture content
ob_start();
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8" x-data="whatsappConnect()">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Connect WhatsApp</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Connect your WhatsApp account to enable AI-powered responses
            </p>
        </div>

        <!-- Connection Status Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="p-6">
                
                <!-- Current Status -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full mr-3" 
                             :class="connectionStatus === 'connected' ? 'bg-green-500' : 
                                     connectionStatus === 'connecting' ? 'bg-yellow-500' : 'bg-red-500'">
                        </div>
                        <span class="text-lg font-medium text-gray-900 dark:text-white">
                            WhatsApp Status: <span x-text="connectionStatus"></span>
                        </span>
                    </div>
                    
                    <?php if ($existingInstance): ?>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Instance: <?= htmlspecialchars($existingInstance['instance_name']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Connected State -->
                <div x-show="connectionStatus === 'connected'" class="text-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">WhatsApp Connected!</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Your WhatsApp is connected and ready to receive AI-powered responses.
                    </p>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Phone:</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="instanceData.phone_number || 'Not available'"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Conversations:</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="instanceStats.threads || 0"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center space-x-3">
                        <a href="/chat" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            Open Chat
                        </a>
                        <button @click="restartInstance()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                            Restart
                        </button>
                    </div>
                </div>

                <!-- QR Code State -->
                <div x-show="connectionStatus === 'connecting'" class="text-center">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Scan QR Code</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Open WhatsApp on your phone and scan this QR code to connect
                    </p>
                    
                    <!-- QR Code Display -->
                    <div class="bg-white p-6 rounded-lg inline-block shadow-sm border-2 border-gray-200 mb-6">
                        <div x-show="qrCode" class="qr-code-container">
                            <img :src="qrCode" alt="WhatsApp QR Code" class="w-64 h-64">
                        </div>
                        <div x-show="!qrCode" class="w-64 h-64 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-500">Generating QR Code...</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        QR code refreshes automatically every 60 seconds
                    </div>
                    
                    <button @click="refreshQR()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        Refresh QR Code
                    </button>
                </div>

                <!-- Disconnected State -->
                <div x-show="connectionStatus === 'disconnected'" class="text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Connect Your WhatsApp</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Connect your WhatsApp account to start using AI-powered responses in your conversations.
                    </p>
                    
                    <button @click="createInstance()" 
                            :disabled="isLoading"
                            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-medium">
                        <span x-show="!isLoading">Connect WhatsApp</span>
                        <span x-show="isLoading" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Connecting...
                        </span>
                    </button>
                </div>

                <!-- Error State -->
                <div x-show="connectionStatus === 'error'" class="text-center">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Connection Error</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6" x-text="errorMessage">
                        There was an error connecting to WhatsApp. Please try again.
                    </p>
                    <div class="flex justify-center space-x-3">
                        <button @click="createInstance()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            Try Again
                        </button>
                        <button @click="testConnection()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                            Test API
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">How to Connect</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mr-3">
                            <span class="text-blue-600 dark:text-blue-400 font-semibold text-sm">1</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Click "Connect WhatsApp"</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">This will create a new WhatsApp instance and generate a QR code.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mr-3">
                            <span class="text-blue-600 dark:text-blue-400 font-semibold text-sm">2</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Open WhatsApp on your phone</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Go to Settings > Linked Devices > Link a Device.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mr-3">
                            <span class="text-blue-600 dark:text-blue-400 font-semibold text-sm">3</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Scan the QR code</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Point your phone's camera at the QR code to connect.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mr-3">
                            <span class="text-green-600 dark:text-green-400 font-semibold text-sm">✓</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Start chatting with AI!</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Your WhatsApp conversations will appear in the chat interface with AI responses.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Card (shown when connected) -->
        <div x-show="connectionStatus === 'connected'" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">WhatsApp Settings</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="font-medium text-gray-900 dark:text-white">Auto-respond to messages</label>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Automatically respond to incoming WhatsApp messages with AI.</p>
                        </div>
                        <input type="checkbox" x-model="settings.auto_respond" @change="updateSettings()" 
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="font-medium text-gray-900 dark:text-white">Respond to groups</label>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Enable AI responses in group chats.</p>
                        </div>
                        <input type="checkbox" x-model="settings.respond_to_groups" @change="updateSettings()"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block font-medium text-gray-900 dark:text-white mb-2">Greeting message</label>
                        <textarea x-model="settings.greeting_message" @blur="updateSettings()"
                                  class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                  rows="3"
                                  placeholder="Hello! I'm your AI assistant. How can I help you today?"></textarea>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Danger Zone</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">Permanently delete this WhatsApp connection.</p>
                        </div>
                        <button @click="deleteInstance()" 
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                            Disconnect
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function whatsappConnect() {
    return {
        connectionStatus: '<?= $existingInstance ? $existingInstance['status'] : 'disconnected' ?>',
        qrCode: null,
        instanceData: <?= $existingInstance ? json_encode($existingInstance) : 'null' ?>,
        instanceStats: {},
        isLoading: false,
        errorMessage: '',
        qrInterval: null,
        statusInterval: null,
        settings: {
            auto_respond: true,
            respond_to_groups: false,
            greeting_message: "Hello! I'm your AI assistant. How can I help you today?"
        },

        init() {
            if (this.instanceData) {
                this.loadSettings();
                this.startStatusCheck();
                if (this.connectionStatus === 'connecting') {
                    this.getQRCode();
                    this.startQRRefresh();
                }
            }
        },

        async createInstance() {
            this.isLoading = true;
            this.errorMessage = '';
            
            try {
                const response = await fetch('/api/whatsapp/create-instance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.instanceData = result.instance;
                    this.connectionStatus = 'connecting';
                    this.loadSettings();
                    this.getQRCode();
                    this.startQRRefresh();
                    this.startStatusCheck();
                } else {
                    this.errorMessage = result.error;
                    this.connectionStatus = 'error';
                }
            } catch (error) {
                this.errorMessage = 'Failed to create instance: ' + error.message;
                this.connectionStatus = 'error';
            }
            
            this.isLoading = false;
        },

        async getQRCode() {
            if (!this.instanceData) return;
            
            try {
                const response = await fetch(`/api/whatsapp/qr?instance=${this.instanceData.instance_name}`);
                const result = await response.json();
                
                if (result.success && result.qr_code) {
                    this.qrCode = result.qr_code;
                }
            } catch (error) {
                console.error('Failed to get QR code:', error);
            }
        },

        async checkStatus() {
            if (!this.instanceData) return;
            
            try {
                const response = await fetch(`/api/whatsapp/status?instance=${this.instanceData.instance_name}`);
                const result = await response.json();
                
                if (result.success) {
                    const newStatus = result.status;
                    
                    if (newStatus !== this.connectionStatus) {
                        this.connectionStatus = newStatus;
                        
                        if (newStatus === 'connected') {
                            this.qrCode = null;
                            this.stopQRRefresh();
                            this.instanceStats = result.stats || {};
                        } else if (newStatus === 'connecting') {
                            this.getQRCode();
                            this.startQRRefresh();
                        }
                    }
                }
            } catch (error) {
                console.error('Failed to check status:', error);
            }
        },

        async restartInstance() {
            if (!this.instanceData) return;
            
            try {
                const response = await fetch('/api/whatsapp/restart', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ instance_name: this.instanceData.instance_name })
                });
                
                const result = await response.json();
                if (result.success) {
                    this.connectionStatus = 'connecting';
                    this.getQRCode();
                    this.startQRRefresh();
                }
            } catch (error) {
                console.error('Failed to restart instance:', error);
            }
        },

        async deleteInstance() {
            if (!this.instanceData || !confirm('Are you sure you want to disconnect WhatsApp? This will delete all synced conversations.')) {
                return;
            }
            
            try {
                const response = await fetch('/api/whatsapp/delete', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ instance_name: this.instanceData.instance_name })
                });
                
                const result = await response.json();
                if (result.success) {
                    this.instanceData = null;
                    this.connectionStatus = 'disconnected';
                    this.qrCode = null;
                    this.stopQRRefresh();
                    this.stopStatusCheck();
                }
            } catch (error) {
                console.error('Failed to delete instance:', error);
            }
        },

        async updateSettings() {
            if (!this.instanceData) return;
            
            try {
                const response = await fetch('/api/whatsapp/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        instance_id: this.instanceData.id,
                        settings: this.settings
                    })
                });
                
                const result = await response.json();
                if (!result.success) {
                    console.error('Failed to update settings:', result.error);
                }
            } catch (error) {
                console.error('Failed to update settings:', error);
            }
        },

        async testConnection() {
            try {
                const response = await fetch('/api/whatsapp/test-connection');
                const result = await response.json();
                
                if (result.success) {
                    alert('Evolution API connection successful!');
                } else {
                    alert('Evolution API connection failed: ' + result.error);
                }
            } catch (error) {
                alert('Failed to test connection: ' + error.message);
            }
        },

        loadSettings() {
            if (this.instanceData && this.instanceData.settings) {
                try {
                    const savedSettings = typeof this.instanceData.settings === 'string' 
                        ? JSON.parse(this.instanceData.settings) 
                        : this.instanceData.settings;
                    this.settings = { ...this.settings, ...savedSettings };
                } catch (error) {
                    console.error('Failed to parse settings:', error);
                }
            }
        },

        refreshQR() {
            this.getQRCode();
        },

        startQRRefresh() {
            this.stopQRRefresh();
            this.qrInterval = setInterval(() => this.getQRCode(), 60000);
        },

        stopQRRefresh() {
            if (this.qrInterval) {
                clearInterval(this.qrInterval);
                this.qrInterval = null;
            }
        },

        startStatusCheck() {
            this.stopStatusCheck();
            this.statusInterval = setInterval(() => this.checkStatus(), 5000);
        },

        stopStatusCheck() {
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
                this.statusInterval = null;
            }
        }
    }
}
</script>

<?php
// Capture the content and pass it to layout
$content = ob_get_clean();

// Set page title
$pageTitle = $pageTitle ?? 'Connect WhatsApp - Smart Chat';

// Include the layout with our content
include __DIR__ . '/layout.php';
?>