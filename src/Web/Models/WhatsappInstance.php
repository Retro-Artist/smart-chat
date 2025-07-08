<?php
// src/Web/Models/WhatsAppInstance.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Api/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../../Api/EvolutionAPI/Instances.php';
require_once __DIR__ . '/../../Api/EvolutionAPI/Settings.php';

class WhatsAppInstance
{
    private $db;
    private $config;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../../../config/config.php';
    }

    /**
     * Create a new WhatsApp instance for user
     */
    public function create($userId, $instanceName = null)
    {
        try {
            // Generate unique instance name if not provided
            if (!$instanceName) {
                $instanceName = 'user_' . $userId . '_' . uniqid();
            }

            // Check if instance name already exists
            if ($this->findByName($instanceName)) {
                throw new Exception("Instance name already exists");
            }

            // Create Evolution API instance
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $instances = new Instances($api);
            
            // Create instance with webhook configuration
            $instanceData = $instances->createInstance($instanceName, [
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS',
                'webhook' => [
                    'url' => $this->getWebhookUrl(),
                    'webhook_by_events' => true,
                    'webhook_base64' => false,
                    'events' => [
                        'QRCODE_UPDATED',
                        'CONNECTION_UPDATE', 
                        'MESSAGES_UPSERT',
                        'MESSAGES_UPDATE',
                        'CONTACTS_SET',
                        'CONTACTS_UPSERT'
                    ]
                ]
            ]);

            if (!$instanceData['success']) {
                throw new Exception("Failed to create Evolution API instance: " . ($instanceData['error'] ?? 'Unknown error'));
            }

            // Store instance in database
            $instanceId = $this->db->insert('whatsapp_instances', [
                'user_id' => $userId,
                'instance_name' => $instanceName,
                'status' => 'connecting',
                'settings' => json_encode([
                    'auto_respond' => true,
                    'greeting_message' => 'Hello! I\'m your AI assistant. How can I help you today?',
                    'business_hours' => null,
                    'human_handoff_enabled' => true
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Configure optimal settings
            $this->configureInstanceSettings($instanceName);

            return [
                'success' => true,
                'id' => $instanceId,
                'instance_name' => $instanceName,
                'data' => $instanceData['data'] ?? []
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get QR code for instance connection
     */
    public function getQRCode($instanceName)
    {
        try {
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $instances = new Instances($api);
            $qrData = $instances->instanceConnect($instanceName);

            if ($qrData['success']) {
                // Update QR code in database
                $this->updateByName($instanceName, [
                    'qr_code' => $qrData['data']['qrcode'] ?? null
                ]);

                return [
                    'success' => true,
                    'qr_code' => $qrData['data']['qrcode'] ?? null,
                    'data' => $qrData['data']
                ];
            }

            return $qrData;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get connection status and update database
     */
    public function getConnectionStatus($instanceName)
    {
        try {
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $instances = new Instances($api);
            $statusData = $instances->connectionState($instanceName);

            if ($statusData['success']) {
                $status = $statusData['data']['instance']['state'] ?? 'disconnected';
                
                // Prepare update data
                $updateData = ['status' => $status];
                
                // If connected, store additional info
                if ($status === 'open') {
                    $instanceInfo = $statusData['data']['instance'];
                    $updateData['phone_number'] = $instanceInfo['wuid'] ?? null;
                    $updateData['profile_name'] = $instanceInfo['profileName'] ?? null;
                    $updateData['profile_picture'] = $instanceInfo['profilePictureUrl'] ?? null;
                    $updateData['last_seen'] = date('Y-m-d H:i:s');
                }

                // Update database
                $this->updateByName($instanceName, $updateData);

                return [
                    'success' => true,
                    'status' => $status,
                    'data' => $statusData['data']
                ];
            }

            return $statusData;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user's instances
     */
    public function getUserInstances($userId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM whatsapp_instances WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Get user's active instance
     */
    public function getUserActiveInstance($userId)
    {
        return $this->db->fetch(
            "SELECT * FROM whatsapp_instances WHERE user_id = ? AND status = 'connected' ORDER BY last_seen DESC LIMIT 1",
            [$userId]
        );
    }

    /**
     * Find instance by name
     */
    public function findByName($instanceName)
    {
        return $this->db->fetch(
            "SELECT * FROM whatsapp_instances WHERE instance_name = ?",
            [$instanceName]
        );
    }

    /**
     * Find instance by ID
     */
    public function findById($instanceId)
    {
        return $this->db->fetch(
            "SELECT * FROM whatsapp_instances WHERE id = ?",
            [$instanceId]
        );
    }

    /**
     * Update instance by name
     */
    public function updateByName($instanceName, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('whatsapp_instances', $data, 'instance_name = ?', [$instanceName]);
    }

    /**
     * Update instance settings
     */
    public function updateSettings($instanceId, $settings)
    {
        $currentInstance = $this->findById($instanceId);
        if (!$currentInstance) {
            return false;
        }

        $currentSettings = json_decode($currentInstance['settings'], true) ?: [];
        $newSettings = array_merge($currentSettings, $settings);

        return $this->db->update('whatsapp_instances', [
            'settings' => json_encode($newSettings),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$instanceId]);
    }

    /**
     * Delete instance
     */
    public function delete($instanceName, $userId = null)
    {
        try {
            // Verify ownership if userId provided
            if ($userId) {
                $instance = $this->db->fetch(
                    "SELECT id FROM whatsapp_instances WHERE instance_name = ? AND user_id = ?",
                    [$instanceName, $userId]
                );

                if (!$instance) {
                    throw new Exception("Instance not found or access denied");
                }
            }

            // Delete from Evolution API
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $instances = new Instances($api);
            $instances->deleteInstance($instanceName);

            // Delete from database (will cascade to threads)
            $this->db->delete('whatsapp_instances', 'instance_name = ?', [$instanceName]);

            return [
                'success' => true,
                'message' => 'Instance deleted successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Restart instance
     */
    public function restart($instanceName)
    {
        try {
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $instances = new Instances($api);
            $result = $instances->restartInstance($instanceName);

            if ($result['success']) {
                $this->updateByName($instanceName, ['status' => 'connecting']);
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get instance statistics
     */
    public function getStats($instanceId)
    {
        $instance = $this->findById($instanceId);
        if (!$instance) {
            return null;
        }

        // Get thread count for this instance
        $threadCountResult = $this->db->fetch(
            "SELECT COUNT(*) as count FROM threads WHERE whatsapp_instance_id = ?",
            [$instanceId]
        );
        $threadCount = $threadCountResult ? $threadCountResult['count'] : 0;

        // Get message count
        $messageCountResult = $this->db->fetch(
            "SELECT SUM(message_count) as total FROM threads WHERE whatsapp_instance_id = ?",
            [$instanceId]
        );
        $messageCount = $messageCountResult ? ($messageCountResult['total'] ?? 0) : 0;

        // Get recent activity
        $recentActivity = $this->db->fetch(
            "SELECT MAX(last_message_at) as last_activity FROM threads WHERE whatsapp_instance_id = ?",
            [$instanceId]
        );

        return [
            'instance' => $instance,
            'threads' => (int)$threadCount,
            'messages' => (int)$messageCount,
            'last_activity' => $recentActivity['last_activity'] ?? null,
            'is_connected' => $instance['status'] === 'connected'
        ];
    }

    /**
     * Configure optimal Evolution API settings for AI chat
     */
    private function configureInstanceSettings($instanceName)
    {
        try {
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $settings = new Settings($api);
            
            // Configure settings for AI integration
            $settingsData = [
                'rejectCall' => false,
                'msgCall' => 'I\'m currently unavailable for calls. Please send a message instead.',
                'groupsIgnore' => false, // Allow group interactions
                'alwaysOnline' => true,
                'readMessages' => true, // Mark messages as read
                'readStatus' => false,
                'syncFullHistory' => true // Import message history
            ];

            return $settings->setSettings($settingsData, $instanceName);

        } catch (Exception $e) {
            error_log("Failed to configure instance settings for {$instanceName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get webhook URL for this application
     */
    private function getWebhookUrl()
    {
        $baseUrl = $this->config['app']['base_url'] ?? 'http://localhost:8080';
        return rtrim($baseUrl, '/') . '/api/whatsapp/webhook';
    }

    /**
     * Check if instance is connected
     */
    public function isConnected($instanceName)
    {
        $status = $this->getConnectionStatus($instanceName);
        return $status['success'] && $status['status'] === 'open';
    }
}