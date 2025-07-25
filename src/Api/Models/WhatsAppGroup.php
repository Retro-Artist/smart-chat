<?php
// src/Api/Models/WhatsAppGroup.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';

class WhatsAppGroup {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($instanceId, $groupId, $name, $description = null, $ownerPhone = null, $participants = [], $isAdmin = false) {
        $sql = "INSERT INTO whatsapp_groups 
                (instance_id, group_id, name, description, owner_phone, participants, is_admin, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                description = VALUES(description),
                owner_phone = VALUES(owner_phone),
                participants = VALUES(participants),
                is_admin = VALUES(is_admin),
                updated_at = NOW()";
        
        $params = [
            $instanceId,
            $groupId,
            $name,
            $description,
            $ownerPhone,
            json_encode($participants),
            $isAdmin ? 1 : 0
        ];
        
        try {
            $this->db->query($sql, $params);
            return $this->findByInstanceAndGroupId($instanceId, $groupId);
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to create group: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM whatsapp_groups WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByInstanceAndGroupId($instanceId, $groupId) {
        $sql = "SELECT * FROM whatsapp_groups WHERE instance_id = ? AND group_id = ?";
        return $this->db->fetch($sql, [$instanceId, $groupId]);
    }
    
    public function findByInstance($instanceId) {
        $sql = "SELECT * FROM whatsapp_groups WHERE instance_id = ? ORDER BY name ASC";
        return $this->db->fetchAll($sql, [$instanceId]);
    }
    
    public function findAdminGroups($instanceId) {
        $sql = "SELECT * FROM whatsapp_groups WHERE instance_id = ? AND is_admin = 1 ORDER BY name ASC";
        return $this->db->fetchAll($sql, [$instanceId]);
    }
    
    public function searchGroups($instanceId, $searchTerm) {
        $sql = "SELECT * FROM whatsapp_groups 
                WHERE instance_id = ? 
                AND (name LIKE ? OR description LIKE ?)
                ORDER BY name ASC 
                LIMIT 50";
        
        return $this->db->fetchAll($sql, [$instanceId, "%{$searchTerm}%", "%{$searchTerm}%"]);
    }
    
    public function updateName($id, $name) {
        $sql = "UPDATE whatsapp_groups SET name = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$name, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update group name: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateDescription($id, $description) {
        $sql = "UPDATE whatsapp_groups SET description = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$description, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update group description: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateProfilePicture($id, $profilePicture) {
        $sql = "UPDATE whatsapp_groups SET profile_picture = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$profilePicture, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update group profile picture: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateOwner($id, $ownerPhone) {
        $sql = "UPDATE whatsapp_groups SET owner_phone = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$ownerPhone, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update group owner: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateParticipants($id, $participants) {
        $sql = "UPDATE whatsapp_groups SET participants = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [json_encode($participants), $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update group participants: " . $e->getMessage());
            return false;
        }
    }
    
    public function addParticipant($id, $phoneNumber) {
        $group = $this->findById($id);
        if (!$group) {
            return false;
        }
        
        $participants = json_decode($group['participants'], true) ?: [];
        
        if (!in_array($phoneNumber, $participants)) {
            $participants[] = $phoneNumber;
            return $this->updateParticipants($id, $participants);
        }
        
        return true;
    }
    
    public function removeParticipant($id, $phoneNumber) {
        $group = $this->findById($id);
        if (!$group) {
            return false;
        }
        
        $participants = json_decode($group['participants'], true) ?: [];
        $participants = array_filter($participants, function($p) use ($phoneNumber) {
            return $p !== $phoneNumber;
        });
        
        return $this->updateParticipants($id, array_values($participants));
    }
    
    public function updateAdminStatus($id, $isAdmin) {
        $sql = "UPDATE whatsapp_groups SET is_admin = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->query($sql, [$isAdmin ? 1 : 0, $id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update group admin status: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        $sql = "DELETE FROM whatsapp_groups WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to delete group: " . $e->getMessage());
            return false;
        }
    }
    
    public function bulkCreateOrUpdate($instanceId, $groups) {
        $sql = "INSERT INTO whatsapp_groups 
                (instance_id, group_id, name, description, profile_picture, owner_phone, participants, is_admin, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                description = VALUES(description),
                profile_picture = VALUES(profile_picture),
                owner_phone = VALUES(owner_phone),
                participants = VALUES(participants),
                is_admin = VALUES(is_admin),
                updated_at = NOW()";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $count = 0;
            
            foreach ($groups as $group) {
                $params = [
                    $instanceId,
                    $group['group_id'],
                    $group['name'],
                    $group['description'] ?? null,
                    $group['profile_picture'] ?? null,
                    $group['owner_phone'] ?? null,
                    json_encode($group['participants'] ?? []),
                    isset($group['is_admin']) ? ($group['is_admin'] ? 1 : 0) : 0
                ];
                
                $stmt->execute($params);
                $count++;
            }
            
            Logger::getInstance()->info("Bulk group operation completed", [
                'instance_id' => $instanceId,
                'groups_processed' => $count
            ]);
            
            return $count;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to bulk create/update groups: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getGroupStats($instanceId) {
        $sql = "SELECT 
                    COUNT(*) as total_groups,
                    SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_groups,
                    AVG(JSON_LENGTH(participants)) as avg_participants
                FROM whatsapp_groups 
                WHERE instance_id = ?";
        
        return $this->db->fetch($sql, [$instanceId]);
    }
    
    public function getParticipants($id) {
        $group = $this->findById($id);
        
        if (!$group || !$group['participants']) {
            return [];
        }
        
        return json_decode($group['participants'], true) ?: [];
    }
    
    public function isParticipant($id, $phoneNumber) {
        $participants = $this->getParticipants($id);
        return in_array($phoneNumber, $participants);
    }
    
    public function getParticipantCount($id) {
        return count($this->getParticipants($id));
    }
    
    public function findGroupsByParticipant($instanceId, $phoneNumber) {
        $sql = "SELECT * FROM whatsapp_groups 
                WHERE instance_id = ? 
                AND JSON_CONTAINS(participants, ?)
                ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, [$instanceId, json_encode($phoneNumber)]);
    }
    
    public function getRecentGroups($instanceId, $limit = 10) {
        $sql = "SELECT g.* 
                FROM whatsapp_groups g
                LEFT JOIN whatsapp_messages m ON m.group_id = g.id
                WHERE g.instance_id = ?
                GROUP BY g.id
                ORDER BY COALESCE(MAX(m.timestamp), g.created_at) DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $limit]);
    }
    
    public function getGroupsWithUnreadMessages($instanceId) {
        $sql = "SELECT g.*, COUNT(m.id) as unread_count
                FROM whatsapp_groups g
                LEFT JOIN whatsapp_messages m ON (
                    m.group_id = g.id 
                    AND m.is_from_me = 0 
                    AND m.status != 'read'
                )
                WHERE g.instance_id = ?
                GROUP BY g.id
                HAVING unread_count > 0
                ORDER BY unread_count DESC";
        
        return $this->db->fetchAll($sql, [$instanceId]);
    }
    
    public function markGroupMessagesAsRead($instanceId, $groupId) {
        $sql = "UPDATE whatsapp_messages 
                SET status = 'read' 
                WHERE instance_id = ? 
                AND group_id = ? 
                AND is_from_me = 0 
                AND status != 'read'";
        
        try {
            $this->db->query($sql, [$instanceId, $groupId]);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to mark group messages as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function getGroupMessageStats($id) {
        $sql = "SELECT 
                    COUNT(*) as total_messages,
                    COUNT(DISTINCT from_phone) as active_participants,
                    MIN(timestamp) as first_message,
                    MAX(timestamp) as last_message,
                    SUM(CASE WHEN is_from_me = 1 THEN 1 ELSE 0 END) as my_messages,
                    SUM(CASE WHEN message_type != 'text' THEN 1 ELSE 0 END) as media_messages
                FROM whatsapp_messages 
                WHERE group_id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    public function cleanupEmptyGroups($instanceId) {
        $sql = "DELETE FROM whatsapp_groups 
                WHERE instance_id = ? 
                AND (participants IS NULL OR participants = '[]' OR participants = '')";
        
        try {
            $result = $this->db->query($sql, [$instanceId]);
            $deletedCount = $result->rowCount();
            
            Logger::getInstance()->info("Cleaned up empty groups", [
                'instance_id' => $instanceId,
                'deleted_count' => $deletedCount
            ]);
            
            return $deletedCount;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to cleanup empty groups: " . $e->getMessage());
            return 0;
        }
    }
}