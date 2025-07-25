<?php
// src/Core/Redis.php - Redis wrapper with file-based fallback

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/Logger.php';

class Redis {
    private static $instance = null;
    private $redis;
    private $connected = false;
    private $fallbackDir;
    
    private function __construct() {
        $this->fallbackDir = __DIR__ . '/../../temp/redis_fallback';
        if (!is_dir($this->fallbackDir)) {
            mkdir($this->fallbackDir, 0755, true);
        }
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
            return $this->redis->ping() === '+PONG';
        } catch (Exception $e) {
            $this->connected = false;
            return false;
        }
    }
    
    public function reconnect() {
        $this->connected = false;
        $this->connect();
    }
    
    // File-based fallback methods
    private function getFilePath($key) {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-\:]/', '_', $key);
        return $this->fallbackDir . '/' . $safeKey . '.cache';
    }
    
    private function fileSet($key, $value, $expiry = null) {
        $filePath = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expires' => $expiry ? time() + $expiry : null,
            'created' => time()
        ];
        return file_put_contents($filePath, serialize($data)) !== false;
    }
    
    private function fileGet($key) {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($filePath));
        if (!$data) {
            return null;
        }
        
        // Check expiry
        if ($data['expires'] && time() > $data['expires']) {
            unlink($filePath);
            return null;
        }
        
        return $data['value'];
    }
    
    private function fileDelete($key) {
        $filePath = $this->getFilePath($key);
        return file_exists($filePath) ? unlink($filePath) : true;
    }
    
    private function fileExists($key) {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Check if expired
        $data = unserialize(file_get_contents($filePath));
        if ($data && $data['expires'] && time() > $data['expires']) {
            unlink($filePath);
            return false;
        }
        
        return true;
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
        
        return $this->fileSet($key, $value, $expiry);
    }
    
    public function get($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $value = $this->redis->get($prefixedKey);
            return $value !== false ? $this->unserialize($value) : null;
        }
        
        return $this->fileGet($key);
    }
    
    public function delete($key) {
        $success1 = true;
        $success2 = true;
        
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $success1 = $this->redis->del($prefixedKey);
        }
        
        $success2 = $this->fileDelete($key);
        
        return $success1 && $success2;
    }
    
    public function exists($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->exists($prefixedKey);
        }
        
        return $this->fileExists($key);
    }
    
    public function expire($key, $seconds) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->expire($prefixedKey, $seconds);
        }
        
        // For file fallback, we need to update the existing entry
        $value = $this->fileGet($key);
        if ($value !== null) {
            return $this->fileSet($key, $value, $seconds);
        }
        
        return false;
    }
    
    // Queue operations with fallback
    public function lpush($key, $value) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->lpush($prefixedKey, $this->serialize($value));
        }
        
        // File-based queue fallback
        $queueFile = $this->getFilePath($key . '_queue');
        $queue = [];
        
        if (file_exists($queueFile)) {
            $queue = unserialize(file_get_contents($queueFile)) ?: [];
        }
        
        array_unshift($queue, $value);
        return file_put_contents($queueFile, serialize($queue)) !== false;
    }
    
    public function rpop($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $value = $this->redis->rpop($prefixedKey);
            return $value !== false ? $this->unserialize($value) : null;
        }
        
        // File-based queue fallback
        $queueFile = $this->getFilePath($key . '_queue');
        if (!file_exists($queueFile)) {
            return null;
        }
        
        $queue = unserialize(file_get_contents($queueFile)) ?: [];
        if (empty($queue)) {
            return null;
        }
        
        $value = array_pop($queue);
        file_put_contents($queueFile, serialize($queue));
        
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
        
        // File-based blocking pop (simplified, non-blocking)
        foreach ($keys as $key) {
            $queueFile = $this->getFilePath($key . '_queue');
            if (file_exists($queueFile)) {
                $queue = unserialize(file_get_contents($queueFile)) ?: [];
                if (!empty($queue)) {
                    $value = array_shift($queue);
                    file_put_contents($queueFile, serialize($queue));
                    return [$key, $value];
                }
            }
        }
        
        return null;
    }
    
    public function llen($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->llen($prefixedKey);
        }
        
        $queueFile = $this->getFilePath($key . '_queue');
        if (!file_exists($queueFile)) {
            return 0;
        }
        
        $queue = unserialize(file_get_contents($queueFile)) ?: [];
        return count($queue);
    }
    
    public function incr($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->incr($prefixedKey);
        }
        
        $value = $this->fileGet($key) ?: 0;
        $newValue = intval($value) + 1;
        $this->fileSet($key, $newValue);
        return $newValue;
    }
    
    public function decr($key) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->decr($prefixedKey);
        }
        
        $value = $this->fileGet($key) ?: 0;
        $newValue = intval($value) - 1;
        $this->fileSet($key, $newValue);
        return $newValue;
    }
    
    // Hash operations (simplified for fallback)
    public function hset($key, $field, $value) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->hset($prefixedKey, $field, $this->serialize($value));
        }
        
        $hash = $this->fileGet($key . '_hash') ?: [];
        $hash[$field] = $value;
        return $this->fileSet($key . '_hash', $hash);
    }
    
    public function hget($key, $field) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            $value = $this->redis->hget($prefixedKey, $field);
            return $value !== false ? $this->unserialize($value) : null;
        }
        
        $hash = $this->fileGet($key . '_hash') ?: [];
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
        
        return $this->fileGet($key . '_hash') ?: [];
    }
    
    public function hdel($key, $field) {
        if ($this->isConnected()) {
            $prefixedKey = REDIS_PREFIX . $key;
            return $this->redis->hdel($prefixedKey, $field);
        }
        
        $hash = $this->fileGet($key . '_hash') ?: [];
        if (isset($hash[$field])) {
            unset($hash[$field]);
            return $this->fileSet($key . '_hash', $hash);
        }
        
        return true;
    }
    
    public function flushAll() {
        if ($this->isConnected()) {
            $success1 = $this->redis->flushAll();
        } else {
            $success1 = true;
        }
        
        // Clear fallback files
        $files = glob($this->fallbackDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
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
    }
}