<?php
// src/Api/Models/WhatsAppContact.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';

class WhatsAppContact {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($instanceId, $phoneNumber, $name = null, $isWhatsappUser = false, $metadata = null) {
        $sql = "INSERT INTO whatsapp_contacts 
                (instance_id, phone_number, name, is_whatsapp_user, metadata, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                is_whatsapp_user = VALUES(is_whatsapp_user),
                metadata = VALUES(metadata),
                updated_at = NOW()";
        
        $params = [
            $instanceId,
            $phoneNumber,
            $name,
            $isWhatsappUser ? 1 : 0,
            $metadata ? json_encode($metadata) : null
        ];
        
        try {
            $this->db->query($sql, $params);
            return $this->findByInstanceAndPhone($instanceId, $phoneNumber);
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to create contact: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM whatsapp_contacts WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByInstanceAndPhone($instanceId, $phoneNumber) {
        $sql = "SELECT * FROM whatsapp_contacts WHERE instance_id = ? AND phone_number = ?";
        return $this->db->fetch($sql, [$instanceId, $phoneNumber]);
    }
    
    public function findByInstance($instanceId, $whatsappUsersOnly = true) {
        $sql = "SELECT * FROM whatsapp_contacts WHERE instance_id = ?";
        $params = [$instanceId];
        
        if ($whatsappUsersOnly) {
            $sql .= " AND is_whatsapp_user = 1";
        }
        
        $sql .= " ORDER BY name ASC, phone_number ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findByPhoneNumber($phoneNumber) {
        $sql = "SELECT * FROM whatsapp_contacts WHERE phone_number = ?";
        return $this->db->fetchAll($sql, [$phoneNumber]);
    }
    
    public function searchContacts($instanceId, $searchTerm, $whatsappUsersOnly = true) {
        $sql = "SELECT * FROM whatsapp_contacts 
                WHERE instance_id = ? 
                AND (name LIKE ? OR phone_number LIKE ?)";
        
        $params = [$instanceId, "%{$searchTerm}%", "%{$searchTerm}%"];
        
        if ($whatsappUsersOnly) {
            $sql .= " AND is_whatsapp_user = 1";
        }
        
        $sql .= " ORDER BY name ASC, phone_number ASC LIMIT 50";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function updateName($id, $name) {
        $sql = "UPDATE whatsapp_contacts SET name = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$name, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update contact name: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateProfilePicture($id, $profilePicture) {
        $sql = "UPDATE whatsapp_contacts SET profile_picture = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$profilePicture, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update contact profile picture: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateWhatsAppStatus($id, $isWhatsappUser) {
        $sql = "UPDATE whatsapp_contacts SET is_whatsapp_user = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$isWhatsappUser ? 1 : 0, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update contact WhatsApp status: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateBusinessStatus($id, $isBusiness) {
        $sql = "UPDATE whatsapp_contacts SET is_business = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$isBusiness ? 1 : 0, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update contact business status: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE whatsapp_contacts SET status = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$status, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update contact status: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateLastSeen($id, $lastSeen) {
        $sql = "UPDATE whatsapp_contacts SET last_seen = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$lastSeen, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update contact last seen: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateMetadata($id, $metadata) {
        $sql = "UPDATE whatsapp_contacts SET metadata = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [json_encode($metadata), $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update contact metadata: " . $e->getMessage());
            return false;
        }
    }
    
    public function blockContact($id) {
        $sql = "UPDATE whatsapp_contacts SET is_blocked = 1, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to block contact: " . $e->getMessage());
            return false;
        }
    }
    
    public function unblockContact($id) {
        $sql = "UPDATE whatsapp_contacts SET is_blocked = 0, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to unblock contact: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        $sql = "DELETE FROM whatsapp_contacts WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to delete contact: " . $e->getMessage());
            return false;
        }
    }
    
    public function bulkCreateOrUpdate($instanceId, $contacts) {
        $sql = "INSERT INTO whatsapp_contacts 
                (instance_id, phone_number, name, is_whatsapp_user, is_business, profile_picture, metadata, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                is_whatsapp_user = VALUES(is_whatsapp_user),
                is_business = VALUES(is_business),
                profile_picture = VALUES(profile_picture),
                metadata = VALUES(metadata),
                updated_at = NOW()";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $count = 0;
            
            foreach ($contacts as $contact) {
                $params = [
                    $instanceId,
                    $contact['phone_number'],
                    $contact['name'] ?? null,
                    isset($contact['is_whatsapp_user']) ? ($contact['is_whatsapp_user'] ? 1 : 0) : 0,
                    isset($contact['is_business']) ? ($contact['is_business'] ? 1 : 0) : 0,
                    $contact['profile_picture'] ?? null,
                    isset($contact['metadata']) ? json_encode($contact['metadata']) : null
                ];
                
                $stmt->execute($params);
                $count++;
            }
            
            Logger::getInstance()->info("Bulk contact operation completed", [
                'instance_id' => $instanceId,
                'contacts_processed' => $count
            ]);
            
            return $count;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to bulk create/update contacts: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getContactStats($instanceId) {
        $sql = "SELECT 
                    COUNT(*) as total_contacts,
                    SUM(CASE WHEN is_whatsapp_user = 1 THEN 1 ELSE 0 END) as whatsapp_users,
                    SUM(CASE WHEN is_business = 1 THEN 1 ELSE 0 END) as business_accounts,
                    SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END) as blocked_contacts
                FROM whatsapp_contacts 
                WHERE instance_id = ?";
        
        return $this->db->fetch($sql, [$instanceId]);
    }
    
    public function getRecentContacts($instanceId, $limit = 20) {
        $sql = "SELECT c.* 
                FROM whatsapp_contacts c
                INNER JOIN whatsapp_messages m ON (
                    (m.from_phone = c.phone_number OR m.to_phone = c.phone_number)
                    AND m.instance_id = c.instance_id
                )
                WHERE c.instance_id = ? AND c.is_whatsapp_user = 1
                GROUP BY c.id
                ORDER BY MAX(m.timestamp) DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $limit]);
    }
    
    public function verifyWhatsAppNumbers($instanceId, $phoneNumbers) {
        if (!is_array($phoneNumbers)) {
            $phoneNumbers = [$phoneNumbers];
        }
        
        $placeholders = str_repeat('?,', count($phoneNumbers) - 1) . '?';
        $sql = "UPDATE whatsapp_contacts 
                SET is_whatsapp_user = 1, updated_at = NOW() 
                WHERE instance_id = ? AND phone_number IN ({$placeholders})";
        
        try {
            $params = array_merge([$instanceId], $phoneNumbers);
            $this->db->query($sql, $params);
            
            Logger::getInstance()->info("WhatsApp numbers verified", [
                'instance_id' => $instanceId,
                'count' => count($phoneNumbers)
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to verify WhatsApp numbers: " . $e->getMessage());
            return false;
        }
    }
    
    public function getMetadata($id) {
        $contact = $this->findById($id);
        
        if (!$contact || !$contact['metadata']) {
            return null;
        }
        
        return json_decode($contact['metadata'], true);
    }
    
    public function getContactsNeedingVerification($instanceId, $limit = 100) {
        $sql = "SELECT phone_number 
                FROM whatsapp_contacts 
                WHERE instance_id = ? 
                AND is_whatsapp_user = 0 
                AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $results = $this->db->fetchAll($sql, [$instanceId, $limit]);
        return array_column($results, 'phone_number');
    }
}