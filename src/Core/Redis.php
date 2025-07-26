<?php
// src/Core/Redis.php - Redis wrapper with file-based fallback

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Database.php';

class RedisManager {
    private static $instance = null;
    private $redis;
    private $connected = false;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            if (!extension_loaded('redis')) {
                Logger::getInstance()->warning('Redis PHP extension not installed, using file-based fallback');
                $this->connected = false;
                return;
            }
            
            $this->redis = new \Redis();
            $connected = $this->redis->connect(REDIS_HOST, REDIS_PORT, 5);
            
            if (!$connected) {
                throw new Exception('Could not connect to Redis server');
            }
            
            if (!empty(REDIS_PASSWORD)) {
                $auth = $this->redis->auth(REDIS_PASSWORD);
                if (!$auth) {
                    throw new Exception('Redis authentication failed');
                }
            }
            
            $this->redis->select(REDIS_DATABASE);
            $this->connected = true;
            
            Logger::getInstance()->info('Redis connection established successfully');
        } catch (Exception $e) {
            Logger::getInstance()->warning('Redis connection failed, using file-based fallback: ' . $e->getMessage());
            $this->connected = false;
        }
    }
    
    public function isConnected() {
        if (!$this->connected || !$this->redis) {
            return false;
        }
        
        try {
            $pong = $this->redis->ping();
            // Redis extension can return different values: 'PONG', '+PONG', or 1 (true)
            return $pong === 'PONG' || $pong === '+PONG' || $pong === 1 || $pong === true;
        } catch (Exception $e) {
            $this->connected = false;
            return false;
        }
    }
    
    public function reconnect() {
        $this->connected = false;
        $this->connect();
    }
    
    // Database-based fallback methods
    private function dbSet($key, $value, $expiry = null) {
        try {
            $expiresAt = $expiry ? date('Y-m-d H:i:s', time() + $expiry) : null;
            
            $this->db->query(
                "INSERT INTO redis_fallback (cache_key, cache_value, expires_at) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                 cache_value = VALUES(cache_value), 
                 expires_at = VALUES(expires_at), 
                 updated_at = CURRENT_TIMESTAMP",
                [$key, serialize($value), $expiresAt]
            );
            
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error('Database fallback set failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function dbGet($key) {
        try {
            $result = $this->db->query(
                "SELECT cache_value, expires_at FROM redis_fallback 
                 WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > NOW())",
                [$key]
            );
            
            if (empty($result) || !is_array($result) || !isset($result[0])) {
                return null;
            }
            
            return unserialize($result[0]['cache_value']);
        } catch (Exception $e) {
            Logger::getInstance()->error('Database fallback get failed: ' . $e->getMessage());
            return null;
        }
    }
    
    private function dbDelete($key) {
        try {
            $this->db->query(
                "DELETE FROM redis_fallback WHERE cache_key = ?",
                [$key]
            );
            return true;
        } catch (Exception $e) {
            Logger::getInstance()->error('Database fallback delete failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function dbExists($key) {
        try {
            $result = $this->db->query(
                "SELECT 1 FROM redis_fallback 
                 WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > NOW())",
                [$key]
            );
            
            return !empty($result);
        } catch (Exception $e) {
            Logger::getInstance()->error('Database fallback exists check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function cleanupExpired() {
        try {
            $this->db->query(
                "DELETE FROM redis_fallback WHERE expires_at IS NOT NULL AND expires_at <= NOW()"
            );
        } catch (Exception $e) {
            Logger::getInstance()->error('Database fallback cleanup failed: ' . $e->getMessage());
        }
    }
    
    // Public interface methods
    public function set($key, $value, $expiry = null) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            if ($expiry) {
                return $this->redis->setex($prefixedKey, $expiry, $this->serialize($value));
            } else {
                return $this->redis->set($prefixedKey, $this->serialize($value));
            }
        }
        
        return $this->dbSet($key, $value, $expiry);
    }
    
    public function get($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $value = $this->redis->get($prefixedKey);
            return $value !== false ? $this->unserialize($value) : null;
        }
        
        return $this->dbGet($key);
    }
    
    public function delete($key) {
        $success1 = true;
        $success2 = true;
        
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $success1 = $this->redis->del($prefixedKey);
        }
        
        $success2 = $this->dbDelete($key);
        
        return $success1 && $success2;
    }
    
    public function exists($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->exists($prefixedKey);
        }
        
        return $this->dbExists($key);
    }
    
    public function expire($key, $seconds) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->expire($prefixedKey, $seconds);
        }
        
        // For database fallback, we need to update the existing entry
        $value = $this->dbGet($key);
        if ($value !== null) {
            return $this->dbSet($key, $value, $seconds);
        }
        
        return false;
    }
    
    // Queue operations with fallback
    public function lpush($key, $value) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->lpush($prefixedKey, $this->serialize($value));
        }
        
        // Database-based queue fallback
        $queue = $this->dbGet($key . '_queue') ?: [];
        array_unshift($queue, $value);
        return $this->dbSet($key . '_queue', $queue);
    }
    
    public function rpop($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $value = $this->redis->rpop($prefixedKey);
            return $value !== false ? $this->unserialize($value) : null;
        }
        
        // Database-based queue fallback
        $queue = $this->dbGet($key . '_queue') ?: [];
        if (empty($queue)) {
            return null;
        }
        
        $value = array_pop($queue);
        $this->dbSet($key . '_queue', $queue);
        
        return $value;
    }
    
    public function blpop($keys, $timeout = 0) {
        if ($this->isConnected()) {
            $prefixedKeys = array_map(function($key) {
                return REDIS_PREFIX . $key;
            }, $keys);
            
            $result = $this->redis->blpop($prefixedKeys, $timeout);
            
            if ($result && count($result) === 2) {
                $key = str_replace(REDIS_PREFIX, '', $result[0]);
                $value = $this->unserialize($result[1]);
                return [$key, $value];
            }
            
            return null;
        }
        
        // Database-based blocking pop (simplified, non-blocking)
        foreach ($keys as $key) {
            $queue = $this->dbGet($key . '_queue') ?: [];
            if (!empty($queue)) {
                $value = array_shift($queue);
                $this->dbSet($key . '_queue', $queue);
                return [$key, $value];
            }
        }
        
        return null;
    }
    
    public function llen($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->llen($prefixedKey);
        }
        
        $queue = $this->dbGet($key . '_queue') ?: [];
        return count($queue);
    }
    
    public function incr($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->incr($prefixedKey);
        }
        
        $value = $this->dbGet($key) ?: 0;
        $newValue = intval($value) + 1;
        $this->dbSet($key, $newValue);
        return $newValue;
    }
    
    public function decr($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->decr($prefixedKey);
        }
        
        $value = $this->dbGet($key) ?: 0;
        $newValue = intval($value) - 1;
        $this->dbSet($key, $newValue);
        return $newValue;
    }
    
    // Hash operations (simplified for fallback)
    public function hset($key, $field, $value) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->hset($prefixedKey, $field, $this->serialize($value));
        }
        
        $hash = $this->dbGet($key . '_hash') ?: [];
        $hash[$field] = $value;
        return $this->dbSet($key . '_hash', $hash);
    }
    
    public function hget($key, $field) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $value = $this->redis->hget($prefixedKey, $field);
            return $value !== false ? $this->unserialize($value) : null;
        }
        
        $hash = $this->dbGet($key . '_hash') ?: [];
        return $hash[$field] ?? null;
    }
    
    public function hgetall($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $values = $this->redis->hgetall($prefixedKey);
            
            $result = [];
            foreach ($values as $field => $value) {
                $result[$field] = $this->unserialize($value);
            }
            
            return $result;
        }
        
        return $this->dbGet($key . '_hash') ?: [];
    }
    
    public function hdel($key, $field) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->hdel($prefixedKey, $field);
        }
        
        $hash = $this->dbGet($key . '_hash') ?: [];
        if (isset($hash[$field])) {
            unset($hash[$field]);
            return $this->dbSet($key . '_hash', $hash);
        }
        
        return true;
    }
    
    public function flushAll() {
        if ($this->isConnected()) {
            $success1 = $this->redis->flushAll();
        } else {
            $success1 = true;
        }
        
        // Clear database fallback
        try {
            $this->db->query("DELETE FROM redis_fallback");
        } catch (Exception $e) {
            Logger::getInstance()->error('Database fallback flush failed: ' . $e->getMessage());
        }
        
        return $success1;
    }
    
    private function serialize($value) {
        return is_string($value) ? $value : json_encode($value);
    }
    
    private function unserialize($value) {
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }
    
    public function __destruct() {
        if ($this->connected && $this->redis) {
            try {
                $this->redis->close();
            } catch (Exception $e) {
                // Ignore errors during cleanup
            }
        }
        
        // Clean up expired entries periodically
        if (rand(1, 100) <= 5) { // 5% chance
            $this->cleanupExpired();
        }
    }
}