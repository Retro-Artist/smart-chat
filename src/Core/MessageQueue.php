<?php
// src/Core/MessageQueue.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Redis.php';
require_once __DIR__ . '/Logger.php';

class MessageQueue {
    private $redis;
    private $db;
    
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRY = 'retry';
    
    public function __construct() {
        $this->redis = Redis::getInstance();
        $this->db = Database::getInstance();
    }
    
    public function push($queueName, $payload, $priority = self::PRIORITY_NORMAL, $instanceId = null, $delay = 0) {
        try {
            $job = [
                'id' => uniqid('job_', true),
                'queue' => $queueName,
                'payload' => $payload,
                'priority' => $priority,
                'instance_id' => $instanceId,
                'created_at' => time(),
                'scheduled_at' => time() + $delay,
                'attempts' => 0,
                'max_attempts' => QUEUE_RETRY_ATTEMPTS
            ];
            
            if ($delay > 0) {
                $this->scheduleJob($job);
            } else {
                $this->pushToRedis($job);
                if ($instanceId) {
                    $this->saveToDatabase($job);
                }
            }
            
            Logger::getInstance()->info("Job pushed to queue: {$queueName}", ['job_id' => $job['id'], 'priority' => $priority]);
            return $job['id'];
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to push job to queue: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function pop($queues = null, $timeout = 5) {
        if ($queues === null) {
            $queues = [
                QUEUE_HIGH_PRIORITY,
                QUEUE_NORMAL_PRIORITY,
                QUEUE_LOW_PRIORITY
            ];
        }
        
        if (!is_array($queues)) {
            $queues = [$queues];
        }
        
        try {
            $result = $this->redis->blpop($queues, $timeout);
            
            if ($result) {
                $queueName = $result[0];
                $job = $result[1];
                
                if ($job['instance_id']) {
                    $this->updateDatabaseStatus($job['id'], self::STATUS_PROCESSING);
                }
                
                Logger::getInstance()->info("Job popped from queue: {$queueName}", ['job_id' => $job['id']]);
                return $job;
            }
            
            return null;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to pop job from queue: " . $e->getMessage());
            return null;
        }
    }
    
    public function complete($jobId, $result = null) {
        try {
            $this->updateDatabaseStatus($jobId, self::STATUS_COMPLETED, null, $result);
            Logger::getInstance()->info("Job completed successfully", ['job_id' => $jobId]);
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to mark job as completed: " . $e->getMessage());
        }
    }
    
    public function fail($jobId, $error, $retry = true) {
        try {
            $job = $this->getJobFromDatabase($jobId);
            
            if (!$job) {
                Logger::getInstance()->warning("Job not found in database", ['job_id' => $jobId]);
                return;
            }
            
            $attempts = $job['attempts'] + 1;
            $maxAttempts = $job['max_attempts'];
            
            if ($retry && $attempts < $maxAttempts) {
                $retryDelay = QUEUE_RETRY_DELAY * $attempts;
                $scheduledAt = time() + $retryDelay;
                
                $this->updateDatabaseStatus($jobId, self::STATUS_RETRY, $error);
                $this->scheduleRetry($job, $scheduledAt, $attempts);
                
                Logger::getInstance()->info("Job scheduled for retry", [
                    'job_id' => $jobId,
                    'attempt' => $attempts,
                    'retry_in' => $retryDelay
                ]);
                
            } else {
                $this->updateDatabaseStatus($jobId, self::STATUS_FAILED, $error);
                Logger::getInstance()->error("Job failed permanently", ['job_id' => $jobId, 'error' => $error]);
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to handle job failure: " . $e->getMessage());
        }
    }
    
    public function getQueueLength($queueName) {
        try {
            return $this->redis->llen($queueName);
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to get queue length: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getQueueStats() {
        try {
            return [
                'high_priority' => $this->redis->llen(QUEUE_HIGH_PRIORITY),
                'normal_priority' => $this->redis->llen(QUEUE_NORMAL_PRIORITY),
                'low_priority' => $this->redis->llen(QUEUE_LOW_PRIORITY),
                'total' => $this->redis->llen(QUEUE_HIGH_PRIORITY) + 
                          $this->redis->llen(QUEUE_NORMAL_PRIORITY) + 
                          $this->redis->llen(QUEUE_LOW_PRIORITY)
            ];
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to get queue stats: " . $e->getMessage());
            return ['high_priority' => 0, 'normal_priority' => 0, 'low_priority' => 0, 'total' => 0];
        }
    }
    
    public function processScheduledJobs() {
        try {
            $sql = "SELECT * FROM message_queue 
                    WHERE status IN (?, ?) 
                    AND scheduled_at <= NOW() 
                    ORDER BY priority DESC, scheduled_at ASC 
                    LIMIT 100";
            
            $jobs = $this->db->fetchAll($sql, [self::STATUS_PENDING, self::STATUS_RETRY]);
            
            foreach ($jobs as $job) {
                $jobData = [
                    'id' => $job['id'],
                    'queue' => $job['queue_name'],
                    'payload' => json_decode($job['payload'], true),
                    'priority' => $job['priority'],
                    'instance_id' => $job['instance_id'],
                    'attempts' => $job['attempts'],
                    'max_attempts' => $job['max_attempts']
                ];
                
                $this->pushToRedis($jobData);
                Logger::getInstance()->info("Scheduled job moved to queue", ['job_id' => $job['id']]);
            }
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to process scheduled jobs: " . $e->getMessage());
        }
    }
    
    public function cleanupOldJobs($olderThanDays = 7) {
        try {
            $sql = "DELETE FROM message_queue 
                    WHERE status IN (?, ?) 
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $result = $this->db->query($sql, [
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                $olderThanDays
            ]);
            
            $deletedCount = $result->rowCount();
            Logger::getInstance()->info("Cleaned up old jobs", ['deleted_count' => $deletedCount]);
            
            return $deletedCount;
            
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to cleanup old jobs: " . $e->getMessage());
            return 0;
        }
    }
    
    private function pushToRedis($job) {
        $queueName = $this->getQueueByPriority($job['priority']);
        $this->redis->lpush($queueName, $job);
    }
    
    private function scheduleJob($job) {
        $this->saveToDatabase($job);
    }
    
    private function scheduleRetry($job, $scheduledAt, $attempts) {
        $sql = "UPDATE message_queue 
                SET scheduled_at = FROM_UNIXTIME(?), attempts = ? 
                WHERE id = ?";
        
        $this->db->query($sql, [$scheduledAt, $attempts, $job['id']]);
    }
    
    private function saveToDatabase($job) {
        $sql = "INSERT INTO message_queue 
                (instance_id, queue_name, priority, payload, status, attempts, max_attempts, scheduled_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?))";
        
        $this->db->query($sql, [
            $job['instance_id'],
            $job['queue'],
            $job['priority'],
            json_encode($job['payload']),
            self::STATUS_PENDING,
            $job['attempts'],
            $job['max_attempts'],
            $job['scheduled_at'],
            $job['created_at']
        ]);
    }
    
    private function updateDatabaseStatus($jobId, $status, $error = null, $result = null) {
        $sql = "UPDATE message_queue 
                SET status = ?, processed_at = NOW(), error_message = ? 
                WHERE id = ?";
        
        $this->db->query($sql, [$status, $error, $jobId]);
    }
    
    private function getJobFromDatabase($jobId) {
        $sql = "SELECT * FROM message_queue WHERE id = ?";
        return $this->db->fetch($sql, [$jobId]);
    }
    
    private function getQueueByPriority($priority) {
        switch ($priority) {
            case self::PRIORITY_HIGH:
                return QUEUE_HIGH_PRIORITY;
            case self::PRIORITY_LOW:
                return QUEUE_LOW_PRIORITY;
            default:
                return QUEUE_NORMAL_PRIORITY;
        }
    }
    
    public function pushHighPriority($queueName, $payload, $instanceId = null) {
        return $this->push($queueName, $payload, self::PRIORITY_HIGH, $instanceId);
    }
    
    public function pushLowPriority($queueName, $payload, $instanceId = null) {
        return $this->push($queueName, $payload, self::PRIORITY_LOW, $instanceId);
    }
    
    public function getJobsByInstance($instanceId, $status = null) {
        $sql = "SELECT * FROM message_queue WHERE instance_id = ?";
        $params = [$instanceId];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
}