<?php
// src/Web/Models/WhatsAppInstance.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Api/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../../Api/EvolutionAPI/Instances.php';

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
     * Get QR code for instance connection - OPTIMIZED VERSION
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

            if ($qrData['success'] && isset($qrData['data'])) {
                // Evolution API v2.3.0+ returns both formats
                $rawQrCode = $qrData['data']['code'] ?? null;
                $base64QrCode = $qrData['data']['base64'] ?? null;
                $pairingCode = $qrData['data']['pairingCode'] ?? null;
                
                $qrCodeDataUri = null;
                
                // Priority: Use the ready-made base64 if available, otherwise convert raw code
                if ($base64QrCode) {
                    $qrCodeDataUri = $base64QrCode;
                } elseif ($rawQrCode) {
                    // Fallback: Convert raw QR code to data URI
                    $qrCodeDataUri = $this->convertQrCodeToDataUri($rawQrCode);
                }
                
                if ($qrCodeDataUri) {
                    // Update QR code in database (store the raw code for reference)
                    $this->updateByName($instanceName, [
                        'qr_code' => $rawQrCode,
                        'status' => 'connecting'
                    ]);
                }

                return [
                    'success' => true,
                    'qr_code' => $qrCodeDataUri, // Return ready-to-display data URI
                    'raw_qr_code' => $rawQrCode,
                    'pairing_code' => $pairingCode,
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
     * Convert raw QR code to base64 data URI for image display
     */
    private function convertQrCodeToDataUri($rawQrCode)
    {
        try {
            // Check if it's already a base64 encoded image
            if (strpos($rawQrCode, 'data:image/') === 0) {
                return $rawQrCode;
            }

            // If it's just base64 data, create proper data URI
            if (base64_decode($rawQrCode, true) !== false) {
                return 'data:image/png;base64,' . $rawQrCode;
            }

            // For other formats, try to generate QR code using a simple approach
            // This is a fallback - in most cases the API should return base64
            return 'data:image/png;base64,' . base64_encode($rawQrCode);

        } catch (Exception $e) {
            // If conversion fails, return null
            return null;
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

            if ($statusData['success'] && isset($statusData['data']['instance'])) {
                $instanceData = $statusData['data']['instance'];
                $state = $instanceData['state'] ?? 'disconnected';
                
                // Map Evolution API states to our status
                $statusMap = [
                    'open' => 'connected',
                    'close' => 'disconnected', 
                    'connecting' => 'connecting',
                    'qr' => 'connecting'
                ];
                
                $status = $statusMap[$state] ?? 'disconnected';
                
                // Prepare update data
                $updateData = ['status' => $status];
                
                // If connected, extract profile information and clear QR code
                if ($state === 'open') {
                    $updateData['phone_number'] = $instanceData['wuid'] ?? null;
                    $updateData['profile_name'] = $instanceData['profileName'] ?? null;
                    $updateData['profile_picture'] = $instanceData['profilePictureUrl'] ?? null;
                    $updateData['last_seen'] = date('Y-m-d H:i:s');
                    $updateData['qr_code'] = null; // Clear QR code when connected
                }

                // Update instance in database
                $this->updateByName($instanceName, $updateData);

                return [
                    'success' => true,
                    'status' => $status,
                    'state' => $state,
                    'data' => $statusData['data'],
                    'stats' => [
                        'phone_number' => $updateData['phone_number'] ?? null,
                        'profile_name' => $updateData['profile_name'] ?? null
                    ]
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
     * Create a new WhatsApp instance for user - FIXED TO MATCH WORKING PATTERN
     */
    public function create($userId, $instanceName = null)
    {
        try {
            // Generate unique instance name if not provided
            if (!$instanceName) {
                $instanceName = 'smart-chat-' . $userId . '-' . time();
            }

            // Check if instance name already exists
            if ($this->findByName($instanceName)) {
                throw new Exception("Instance name already exists");
            }

            // Create Evolution API instance using the SAME method as working script
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $instances = new Instances($api);
            
            // Use createBasicInstance method (same as working instance-management.php)
            $instanceData = $instances->createBasicInstance($instanceName);

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
     * Update instance by name
     */
    public function updateByName($instanceName, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update(
            'whatsapp_instances',
            $data,
            'instance_name = ?',
            [$instanceName]
        );
    }

    /**
     * Get user's active instance
     */
    public function getUserActiveInstance($userId)
    {
        return $this->db->fetch(
            "SELECT * FROM whatsapp_instances WHERE user_id = ? AND status IN ('connected', 'connecting') ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
    }

    /**
     * Get all user instances
     */
    public function getUserInstances($userId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM whatsapp_instances WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Configure optimal instance settings
     */
    private function configureInstanceSettings($instanceName)
    {
        try {
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            // Configure optimal settings for AI chat
            $settings = [
                'rejectCall' => true,
                'alwaysOnline' => true,
                'readMessages' => false, // Let AI handle read receipts
                'readStatus' => false,
                'syncFullHistory' => true
            ];

            foreach ($settings as $setting => $value) {
                $api->makeRequest("instance/settings/{$instanceName}", [
                    $setting => $value
                ], 'PUT');
            }

        } catch (Exception $e) {
            // Log error but don't fail instance creation
            error_log("Failed to configure instance settings: " . $e->getMessage());
        }
    }

    /**
     * Delete instance
     */
    public function delete($instanceName, $userId = null)
    {
        try {
            // Verify ownership if userId provided
            if ($userId) {
                $instance = $this->findByName($instanceName);
                if (!$instance || $instance['user_id'] != $userId) {
                    throw new Exception('Instance not found or access denied');
                }
            }

            // Delete from Evolution API
            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $result = $api->makeRequest("instance/delete/{$instanceName}", null, 'DELETE');

            // Delete from database regardless of API result
            $this->db->delete('whatsapp_instances', 'instance_name = ?', [$instanceName]);

            return [
                'success' => true,
                'api_result' => $result
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
    public function restart($instanceName, $userId = null)
    {
        try {
            // Verify ownership if userId provided
            if ($userId) {
                $instance = $this->findByName($instanceName);
                if (!$instance || $instance['user_id'] != $userId) {
                    throw new Exception('Instance not found or access denied');
                }
            }

            $api = new EvolutionAPI(
                $this->config['evolutionAPI']['api_url'],
                $this->config['evolutionAPI']['api_key'],
                $instanceName
            );

            $instances = new Instances($api);
            $result = $instances->restartInstance($instanceName);

            if ($result['success']) {
                // Update status in database
                $this->updateByName($instanceName, [
                    'status' => 'connecting',
                    'qr_code' => null
                ]);
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}