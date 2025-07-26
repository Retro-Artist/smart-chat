<?php
// src/Api/Models/WhatsAppInstance.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';

class WhatsAppInstance {
    private $db;
    
    const STATUS_CREATING = 'creating';
    const STATUS_CONNECTING = 'connecting';
    const STATUS_CONNECTED = 'connected';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_FAILED = 'failed';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($userId, $instanceName, $instanceId = null, $phoneNumber = null, $settings = null) {
        $sql = "INSERT INTO whatsapp_instances 
                (user_id, instance_name, instance_id, phone_number, status, settings, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $params = [
            $userId,
            $instanceName,
            $instanceId,
            $phoneNumber,
            self::STATUS_CREATING,
            $settings ? json_encode($settings) : null
        ];
        
        try {
            $this->db->query($sql, $params);
            $id = $this->db->getConnection()->lastInsertId();
            
            Logger::getInstance()->getInstance()->info("WhatsApp instance created", [
                'instance_id' => $id,
                'user_id' => $userId,
                'instance_name' => $instanceName
            ]);
            
            return $this->findById($id);
        } catch (Exception $e) {
            Logger::getInstance()->getInstance()->error("Failed to create WhatsApp instance: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM whatsapp_instances WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT * FROM whatsapp_instances WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->db->fetch($sql, [$userId]);
    }
    
    public function findByInstanceName($instanceName) {
        $sql = "SELECT * FROM whatsapp_instances WHERE instance_name = ?";
        return $this->db->fetch($sql, [$instanceName]);
    }
    
    public function findByPhoneNumber($phoneNumber) {
        $sql = "SELECT * FROM whatsapp_instances WHERE phone_number = ?";
        return $this->db->fetch($sql, [$phoneNumber]);
    }
    
    public function findByStatus($status) {
        $sql = "SELECT * FROM whatsapp_instances WHERE status = ? ORDER BY updated_at DESC";
        return $this->db->fetchAll($sql, [$status]);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE whatsapp_instances SET status = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$status, $id]);
            
            Logger::getInstance()->info("WhatsApp instance status updated", [
                'instance_id' => $id,
                'status' => $status
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update instance status: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateQRCode($id, $qrCode) {
        $sql = "UPDATE whatsapp_instances SET qr_code = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$qrCode, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update QR code: " . $e->getMessage());
            return false;
        }
    }
    
    public function updatePhoneNumber($id, $phoneNumber) {
        $sql = "UPDATE whatsapp_instances SET phone_number = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$phoneNumber, $id]);
            
            Logger::getInstance()->info("WhatsApp instance phone number updated", [
                'instance_id' => $id,
                'phone_number' => $phoneNumber
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update phone number: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateProfilePicture($id, $profilePicture) {
        $sql = "UPDATE whatsapp_instances SET profile_picture = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$profilePicture, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update profile picture: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateWebhookUrl($id, $webhookUrl) {
        $sql = "UPDATE whatsapp_instances SET webhook_url = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$webhookUrl, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update webhook URL: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateLastSync($id) {
        $sql = "UPDATE whatsapp_instances SET last_sync_at = NOW(), updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update last sync: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateSettings($id, $settings) {
        $sql = "UPDATE whatsapp_instances SET settings = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [json_encode($settings), $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update settings: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        $allowedFields = [
            'instance_id', 'phone_number', 'status', 'qr_code', 
            'profile_picture', 'webhook_url', 'settings'
        ];
        
        $updates = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "{$field} = ?";
                $params[] = $field === 'settings' && is_array($value) ? json_encode($value) : $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE whatsapp_instances SET " . implode(', ', $updates) . " WHERE id = ?";
        
        try {
            $this->db->query($sql, $params);
            
            Logger::getInstance()->info("WhatsApp instance updated", [
                'instance_id' => $id,
                'fields' => array_keys($data)
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update instance: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        $sql = "DELETE FROM whatsapp_instances WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            
            Logger::getInstance()->info("WhatsApp instance deleted", ['instance_id' => $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to delete instance: " . $e->getMessage());
            return false;
        }
    }
    
    public function getConnectedInstances() {
        $sql = "SELECT * FROM whatsapp_instances WHERE status = ? ORDER BY updated_at DESC";
        return $this->db->fetchAll($sql, [self::STATUS_CONNECTED]);
    }
    
    public function getInstancesNeedingSync($hoursAgo = 1) {
        $sql = "SELECT * FROM whatsapp_instances 
                WHERE status = ? 
                AND (last_sync_at IS NULL OR last_sync_at < DATE_SUB(NOW(), INTERVAL ? HOUR))
                ORDER BY last_sync_at ASC";
        
        return $this->db->fetchAll($sql, [self::STATUS_CONNECTED, $hoursAgo]);
    }
    
    public function getInstanceStats() {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM whatsapp_instances 
                GROUP BY status";
        
        $results = $this->db->fetchAll($sql);
        
        $stats = [
            'total' => 0,
            'creating' => 0,
            'connecting' => 0,
            'connected' => 0,
            'disconnected' => 0,
            'failed' => 0
        ];
        
        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
            $stats['total'] += (int)$result['count'];
        }
        
        return $stats;
    }
    
    public function isConnected($id) {
        $instance = $this->findById($id);
        return $instance && $instance['status'] === self::STATUS_CONNECTED;
    }
    
    public function generateInstanceName($userId) {
        // Get username from users table
        $userSql = "SELECT username FROM users WHERE id = ?";
        $user = $this->db->fetch($userSql, [$userId]);
        
        if (!$user || !$user['username']) {
            // Fallback to user ID if username not found
            $baseName = "user_{$userId}";
        } else {
            // Use username to create instance name (sanitized for URL safety)
            $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $user['username']);
            $baseName = strtolower($username);
        }
        
        $counter = 1;
        
        do {
            $instanceName = $baseName . ($counter > 1 ? "_{$counter}" : "");
            $existing = $this->findByInstanceName($instanceName);
            $counter++;
        } while ($existing && $counter <= 10);
        
        if ($counter > 10) {
            $instanceName = $baseName . "_" . uniqid();
        }
        
        return $instanceName;
    }
    
    public function getSettings($id) {
        $instance = $this->findById($id);
        
        if (!$instance || !$instance['settings']) {
            return null;
        }
        
        return json_decode($instance['settings'], true);
    }
    
    public function hasValidQRCode($id) {
        $instance = $this->findById($id);
        
        if (!$instance || !$instance['qr_code'] || $instance['status'] === self::STATUS_CONNECTED) {
            return false;
        }
        
        $updatedAt = strtotime($instance['updated_at']);
        $qrExpiryTime = CACHE_TTL_QR_CODE; // QR codes typically expire after 60 seconds
        
        return (time() - $updatedAt) < $qrExpiryTime;
    }
}