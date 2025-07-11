<?php
// src/Api/Models/Instance.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Api/WhatsApp/EvolutionAPI.php';
require_once __DIR__ . '/../../Api/WhatsApp/Instances.php';

class Instance
{
    private $db;
    private $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../../../config/config.php';
    }

/**
 * Get QR code for instance connection - Updated for Evolution API v2.3.0
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
            // Evolution API can return data in different formats
            $data = $qrData['data'] ?? [];
            
            // Debug logging
            error_log('Evolution API QR Response: ' . json_encode($data));
            
            // Check for different response formats from Evolution API
            // Format 1: Direct response with base64 field
            if (isset($data['base64'])) {
                $qrCodeDataUri = $data['base64'];
                // Ensure it has the data URI prefix
                if (strpos($qrCodeDataUri, 'data:image/') !== 0) {
                    $qrCodeDataUri = 'data:image/png;base64,' . $qrCodeDataUri;
                }
                
                return [
                    'success' => true,
                    'qr_code' => $qrCodeDataUri,
                    'pairing_code' => $data['pairingCode'] ?? null,
                    'count' => $data['count'] ?? 1,
                    'raw_qr_code' => $data['code'] ?? null
                ];
            }
            
            // Format 2: Response with qr field (some versions)
            if (isset($data['qr'])) {
                $qrCodeDataUri = $data['qr'];
                // Ensure it has the data URI prefix
                if (strpos($qrCodeDataUri, 'data:image/') !== 0) {
                    $qrCodeDataUri = 'data:image/png;base64,' . $qrCodeDataUri;
                }
                
                return [
                    'success' => true,
                    'qr_code' => $qrCodeDataUri,
                    'pairing_code' => $data['pairingCode'] ?? null,
                    'count' => $data['count'] ?? 1,
                    'raw_qr_code' => $data['code'] ?? null
                ];
            }
            
            // Format 3: Response with qrcode field
            if (isset($data['qrcode'])) {
                $qrCodeDataUri = $data['qrcode'];
                // Ensure it has the data URI prefix
                if (strpos($qrCodeDataUri, 'data:image/') !== 0) {
                    $qrCodeDataUri = 'data:image/png;base64,' . $qrCodeDataUri;
                }
                
                return [
                    'success' => true,
                    'qr_code' => $qrCodeDataUri,
                    'pairing_code' => $data['pairingCode'] ?? null,
                    'count' => $data['count'] ?? 1,
                    'raw_qr_code' => $data['code'] ?? null
                ];
            }
            
            // Check if count is 0 (no QR code available)
            if (isset($data['count']) && $data['count'] == 0) {
                return [
                    'success' => false,
                    'error' => 'QR code not ready yet. Instance may already be connected.',
                    'data' => $data
                ];
            }
            
            // If we reach here, no QR code was found in expected fields
            return [
                'success' => false,
                'error' => 'QR code not found in API response',
                'debug_data' => $data,
                'debug_fields' => array_keys($data)
            ];
        }

        return [
            'success' => false,
            'error' => $qrData['error'] ?? 'Failed to get QR code'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * Generate QR code image from raw code data
     */
    private function generateQRCodeImage($rawQrCode)
    {
        // This method should not be needed since Evolution API provides the QR code
        // If we reach here, it means the API response format has changed
        error_log('Warning: Evolution API did not provide base64 QR code, raw code: ' . substr($rawQrCode, 0, 50) . '...');
        return null;
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
            } else if (!$statusData['success']) {
                // Instance not found in Evolution API or other error
                $errorMessage = $statusData['error'] ?? 'Unknown error';
                
                // Check if it's a "not found" error
                if (stripos($errorMessage, 'not found') !== false || 
                    stripos($errorMessage, '404') !== false ||
                    (isset($statusData['http_code']) && $statusData['http_code'] == 404)) {
                    
                    // Mark instance as deleted/missing
                    $this->updateByName($instanceName, [
                        'status' => 'deleted',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    return [
                        'success' => false,
                        'status' => 'deleted',
                        'error' => 'Instance not found in Evolution API. It may have been deleted externally.',
                        'deleted' => true
                    ];
                }
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
            "SELECT * FROM whatsapp_instances WHERE user_id = ? AND status IN ('connected', 'connecting', 'deleted') ORDER BY created_at DESC LIMIT 1",
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
