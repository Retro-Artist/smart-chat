<?php
// src/Api/Models/WhatsAppSyncLog.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Logger.php';

class WhatsAppSyncLog {
    private $db;
    
    const TYPE_CONTACTS = 'contacts';
    const TYPE_MESSAGES = 'messages';
    const TYPE_GROUPS = 'groups';
    const TYPE_FULL = 'full';
    
    const STATUS_STARTED = 'started';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PARTIAL = 'partial';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function start($instanceId, $syncType, $metadata = null) {
        $sql = "INSERT INTO whatsapp_sync_log 
                (instance_id, sync_type, status, started_at, metadata) 
                VALUES (?, ?, ?, NOW(), ?)";
        
        $params = [
            $instanceId,
            $syncType,
            self::STATUS_STARTED,
            $metadata ? json_encode($metadata) : null
        ];
        
        try {
            $this->db->query($sql, $params);
            $id = $this->db->getConnection()->lastInsertId();
            
            Logger::getInstance()->info("Sync operation started", [
                'sync_id' => $id,
                'instance_id' => $instanceId,
                'sync_type' => $syncType
            ]);
            
            return $id;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to start sync operation: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function complete($id, $recordsProcessed = 0, $recordsCreated = 0, $recordsUpdated = 0, $metadata = null) {
        $sql = "UPDATE whatsapp_sync_log 
                SET status = ?, 
                    records_processed = ?, 
                    records_created = ?, 
                    records_updated = ?, 
                    completed_at = NOW(),
                    metadata = COALESCE(?, metadata)
                WHERE id = ?";
        
        $params = [
            self::STATUS_COMPLETED,
            $recordsProcessed,
            $recordsCreated,
            $recordsUpdated,
            $metadata ? json_encode($metadata) : null,
            $id
        ];
        
        try {
            $this->db->query($sql, $params);
            
            Logger::getInstance()->info("Sync operation completed", [
                'sync_id' => $id,
                'records_processed' => $recordsProcessed,
                'records_created' => $recordsCreated,
                'records_updated' => $recordsUpdated
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to complete sync operation: " . $e->getMessage());
            return false;
        }
    }
    
    public function fail($id, $errorMessage, $recordsProcessed = 0, $recordsCreated = 0, $recordsUpdated = 0) {
        $sql = "UPDATE whatsapp_sync_log 
                SET status = ?, 
                    error_message = ?, 
                    records_processed = ?, 
                    records_created = ?, 
                    records_updated = ?, 
                    completed_at = NOW()
                WHERE id = ?";
        
        $params = [
            self::STATUS_FAILED,
            $errorMessage,
            $recordsProcessed,
            $recordsCreated,
            $recordsUpdated,
            $id
        ];
        
        try {
            $this->db->query($sql, $params);
            
            Logger::getInstance()->error("Sync operation failed", [
                'sync_id' => $id,
                'error' => $errorMessage,
                'records_processed' => $recordsProcessed
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update sync operation failure: " . $e->getMessage());
            return false;
        }
    }
    
    public function partial($id, $errorMessage, $recordsProcessed = 0, $recordsCreated = 0, $recordsUpdated = 0) {
        $sql = "UPDATE whatsapp_sync_log 
                SET status = ?, 
                    error_message = ?, 
                    records_processed = ?, 
                    records_created = ?, 
                    records_updated = ?, 
                    completed_at = NOW()
                WHERE id = ?";
        
        $params = [
            self::STATUS_PARTIAL,
            $errorMessage,
            $recordsProcessed,
            $recordsCreated,
            $recordsUpdated,
            $id
        ];
        
        try {
            $this->db->query($sql, $params);
            
            Logger::getInstance()->warning("Sync operation partially completed", [
                'sync_id' => $id,
                'error' => $errorMessage,
                'records_processed' => $recordsProcessed
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update sync operation partial completion: " . $e->getMessage());
            return false;
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM whatsapp_sync_log WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByInstance($instanceId, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM whatsapp_sync_log 
                WHERE instance_id = ? 
                ORDER BY started_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $limit, $offset]);
    }
    
    public function findByInstanceAndType($instanceId, $syncType, $limit = 20) {
        $sql = "SELECT * FROM whatsapp_sync_log 
                WHERE instance_id = ? AND sync_type = ?
                ORDER BY started_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, $syncType, $limit]);
    }
    
    public function findByStatus($status, $limit = 100) {
        $sql = "SELECT * FROM whatsapp_sync_log 
                WHERE status = ? 
                ORDER BY started_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$status, $limit]);
    }
    
    public function findRunningOperations($instanceId = null) {
        $sql = "SELECT * FROM whatsapp_sync_log 
                WHERE status = ? 
                AND started_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $params = [self::STATUS_STARTED];
        
        if ($instanceId) {
            $sql .= " AND instance_id = ?";
            $params[] = $instanceId;
        }
        
        $sql .= " ORDER BY started_at ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getLastSyncByType($instanceId, $syncType) {
        $sql = "SELECT * FROM whatsapp_sync_log 
                WHERE instance_id = ? AND sync_type = ?
                ORDER BY started_at DESC 
                LIMIT 1";
        
        return $this->db->fetch($sql, [$instanceId, $syncType]);
    }
    
    public function getLastSuccessfulSync($instanceId, $syncType = null) {
        $sql = "SELECT * FROM whatsapp_sync_log 
                WHERE instance_id = ? 
                AND status = ?";
        
        $params = [$instanceId, self::STATUS_COMPLETED];
        
        if ($syncType) {
            $sql .= " AND sync_type = ?";
            $params[] = $syncType;
        }
        
        $sql .= " ORDER BY completed_at DESC LIMIT 1";
        
        return $this->db->fetch($sql, $params);
    }
    
    public function getSyncStats($instanceId) {
        $sql = "SELECT 
                    sync_type,
                    status,
                    COUNT(*) as count,
                    SUM(records_processed) as total_processed,
                    SUM(records_created) as total_created,
                    SUM(records_updated) as total_updated,
                    AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration
                FROM whatsapp_sync_log 
                WHERE instance_id = ?
                GROUP BY sync_type, status
                ORDER BY sync_type, status";
        
        return $this->db->fetchAll($sql, [$instanceId]);
    }
    
    public function getOverallStats($instanceId) {
        $sql = "SELECT 
                    COUNT(*) as total_syncs,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_syncs,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_syncs,
                    SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_syncs,
                    SUM(CASE WHEN status = 'started' THEN 1 ELSE 0 END) as running_syncs,
                    SUM(records_processed) as total_records_processed,
                    SUM(records_created) as total_records_created,
                    SUM(records_updated) as total_records_updated,
                    MAX(started_at) as last_sync_started,
                    MAX(completed_at) as last_sync_completed
                FROM whatsapp_sync_log 
                WHERE instance_id = ?";
        
        return $this->db->fetch($sql, [$instanceId]);
    }
    
    public function updateProgress($id, $recordsProcessed, $recordsCreated = null, $recordsUpdated = null, $metadata = null) {
        $sql = "UPDATE whatsapp_sync_log 
                SET records_processed = ?";
        
        $params = [$recordsProcessed];
        
        if ($recordsCreated !== null) {
            $sql .= ", records_created = ?";
            $params[] = $recordsCreated;
        }
        
        if ($recordsUpdated !== null) {
            $sql .= ", records_updated = ?";
            $params[] = $recordsUpdated;
        }
        
        if ($metadata !== null) {
            $sql .= ", metadata = ?";
            $params[] = json_encode($metadata);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        try {
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to update sync progress: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentFailures($instanceId, $hours = 24, $limit = 10) {
        $sql = "SELECT * FROM whatsapp_sync_log 
                WHERE instance_id = ? 
                AND status = ?
                AND started_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY started_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$instanceId, self::STATUS_FAILED, $hours, $limit]);
    }
    
    public function getAverageSyncDuration($instanceId, $syncType = null, $days = 7) {
        $sql = "SELECT 
                    sync_type,
                    AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration_seconds,
                    COUNT(*) as sync_count
                FROM whatsapp_sync_log 
                WHERE instance_id = ? 
                AND status = ?
                AND completed_at IS NOT NULL
                AND started_at > DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $params = [$instanceId, self::STATUS_COMPLETED, $days];
        
        if ($syncType) {
            $sql .= " AND sync_type = ?";
            $params[] = $syncType;
        }
        
        $sql .= " GROUP BY sync_type";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function needsSync($instanceId, $syncType, $intervalHours = 24) {
        $lastSync = $this->getLastSuccessfulSync($instanceId, $syncType);
        
        if (!$lastSync) {
            return true; // Never synced
        }
        
        $lastSyncTime = strtotime($lastSync['completed_at']);
        $now = time();
        $intervalSeconds = $intervalHours * 3600;
        
        return ($now - $lastSyncTime) > $intervalSeconds;
    }
    
    public function isCurrentlyRunning($instanceId, $syncType = null) {
        $sql = "SELECT COUNT(*) as count FROM whatsapp_sync_log 
                WHERE instance_id = ? 
                AND status = ?
                AND started_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)";
        
        $params = [$instanceId, self::STATUS_STARTED];
        
        if ($syncType) {
            $sql .= " AND sync_type = ?";
            $params[] = $syncType;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    public function cleanupOldLogs($instanceId = null, $olderThanDays = 30) {
        $sql = "DELETE FROM whatsapp_sync_log 
                WHERE started_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $params = [$olderThanDays];
        
        if ($instanceId) {
            $sql .= " AND instance_id = ?";
            $params[] = $instanceId;
        }
        
        try {
            $result = $this->db->query($sql, $params);
            $deletedCount = $result->rowCount();
            
            Logger::getInstance()->info("Cleaned up old sync logs", [
                'instance_id' => $instanceId,
                'deleted_count' => $deletedCount
            ]);
            
            return $deletedCount;
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to cleanup old sync logs: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getMetadata($id) {
        $syncLog = $this->findById($id);
        
        if (!$syncLog || !$syncLog['metadata']) {
            return null;
        }
        
        return json_decode($syncLog['metadata'], true);
    }
    
    public function getSyncHistory($instanceId, $days = 30) {
        $sql = "SELECT 
                    DATE(started_at) as sync_date,
                    sync_type,
                    COUNT(*) as sync_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_count,
                    SUM(records_processed) as total_processed
                FROM whatsapp_sync_log 
                WHERE instance_id = ?
                AND started_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(started_at), sync_type
                ORDER BY sync_date DESC, sync_type";
        
        return $this->db->fetchAll($sql, [$instanceId, $days]);
    }
}