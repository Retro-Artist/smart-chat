<?php
/**
 * Application Logger
 * Simple logging system with different levels
 */

class Logger {
    private static $instance = null;
    private $enabled = true;
    
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    
    private function __construct() {
        $config = require __DIR__ . '/../../config/app.php';
        $this->enabled = $config['app']['debug'] ?? false;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($level, $message, $context = []) {
        if (!$this->enabled) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }
        
        $logMessage = "[{$level}] {$timestamp} - {$message}{$contextStr}";
        
        // Log to PHP error log
        error_log($logMessage);
        
        // Also log to file if in development
        if ($this->enabled) {
            $this->logToFile($logMessage);
        }
    }
    
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    private function logToFile($message) {
        $logDir = __DIR__ . '/../../logs';
        
        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
        
        // Write to log file
        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    public function logRequest() {
        if (!$this->enabled) {
            return;
        }
        
        $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        $uri = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        $this->info("Request: {$method} {$uri}", [
            'ip' => $ip,
            'user_agent' => substr($userAgent, 0, 100)
        ]);
    }
    
    public function logError($exception) {
        $this->error("Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    public function logPerformance($operation, $duration) {
        $this->info("Performance: {$operation} completed in {$duration}ms");
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    private function __wakeup() {}
}