<?php
// src/Api/Models/Run.php  

require_once __DIR__ . '/../../Core/Database.php';

class Run {
    private static function getDB() {
        return Database::getInstance();
    }
    
    public static function findById($runId) {
        $db = self::getDB();
        return $db->fetch("SELECT * FROM runs WHERE id = ?", [$runId]);
    }
    
    public static function getThreadRuns($threadId) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT r.*, a.name as agent_name 
            FROM runs r 
            LEFT JOIN agents a ON r.agent_id = a.id 
            WHERE r.thread_id = ? 
            ORDER BY r.created_at DESC
        ", [$threadId]);
    }
    
    public static function getAgentRuns($agentId, $limit = 20) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT r.*, t.title as thread_title 
            FROM runs r 
            LEFT JOIN threads t ON r.thread_id = t.id 
            WHERE r.agent_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT ?
        ", [$agentId, $limit]);
    }
    
    public static function getUserRuns($userId, $limit = 50) {
        $db = self::getDB();
        return $db->fetchAll("
            SELECT r.*, a.name as agent_name, t.title as thread_title 
            FROM runs r 
            LEFT JOIN agents a ON r.agent_id = a.id 
            LEFT JOIN threads t ON r.thread_id = t.id 
            WHERE t.user_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT ?
        ", [$userId, $limit]);
    }
    
    public static function getRunStats($userId) {
        $db = self::getDB();
        return $db->fetch("
            SELECT 
                COUNT(*) as total_runs,
                SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_runs,
                SUM(CASE WHEN r.status = 'failed' THEN 1 ELSE 0 END) as failed_runs,
                SUM(CASE WHEN r.status = 'in_progress' THEN 1 ELSE 0 END) as running_runs
            FROM runs r 
            LEFT JOIN threads t ON r.thread_id = t.id 
            WHERE t.user_id = ?
        ", [$userId]);
    }
    
    public static function create($threadId, $agentId, $status = 'queued') {
        $db = self::getDB();
        $runId = $db->insert('runs', [
            'thread_id' => $threadId,
            'agent_id' => $agentId,
            'status' => $status,
            'started_at' => $status === 'in_progress' ? date('Y-m-d H:i:s') : null
        ]);
        
        return self::findById($runId);
    }
    
    public static function updateStatus($runId, $status, $metadata = null) {
        $db = self::getDB();
        
        $updateData = ['status' => $status];
        
        if ($status === 'in_progress' && !self::hasStarted($runId)) {
            $updateData['started_at'] = date('Y-m-d H:i:s');
        }
        
        if (in_array($status, ['completed', 'failed'])) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        if ($metadata !== null) {
            $updateData['metadata'] = json_encode($metadata);
        }
        
        $db->update('runs', $updateData, 'id = ?', [$runId]);
        
        return self::findById($runId);
    }
    
    public static function cancel($runId) {
        return self::updateStatus($runId, 'cancelled');
    }
    
    public static function delete($runId) {
        $db = self::getDB();
        return $db->delete('runs', 'id = ?', [$runId]);
    }
    
    private static function hasStarted($runId) {
        $run = self::findById($runId);
        return $run && !empty($run['started_at']);
    }
}
