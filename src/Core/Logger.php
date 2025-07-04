<?php

/**
 * Logger Class for Evolution API Project
 * 
 * A comprehensive logging system that supports multiple log levels,
 * file rotation, and structured logging for debugging and monitoring.
 */

class Logger {
    
    // Log levels (following PSR-3 standard)
    const EMERGENCY = 'EMERGENCY';
    const ALERT     = 'ALERT';
    const CRITICAL  = 'CRITICAL';
    const ERROR     = 'ERROR';
    const WARNING   = 'WARNING';
    const NOTICE    = 'NOTICE';
    const INFO      = 'INFO';
    const DEBUG     = 'DEBUG';
    
    private $logDirectory;
    private $logLevel;
    private $maxFileSize;
    private $maxFiles;
    private $dateFormat;
    private $logFormat;
    
    /**
     * Constructor
     * 
     * @param string $logDirectory Directory to store log files
     * @param string $logLevel Minimum log level to record
     * @param int $maxFileSize Maximum size per log file in bytes (default: 10MB)
     * @param int $maxFiles Maximum number of log files to keep (default: 5)
     */
    public function __construct($logDirectory = 'logs/', $logLevel = self::INFO, $maxFileSize = 10485760, $maxFiles = 5) {
        $this->logDirectory = rtrim($logDirectory, '/') . '/';
        $this->logLevel = $logLevel;
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;
        $this->dateFormat = 'Y-m-d H:i:s';
        $this->logFormat = '[{timestamp}] {level}: {message} {context}';
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
    }
    
    /**
     * Log an emergency message
     */
    public function emergency($message, array $context = []) {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log an alert message
     */
    public function alert($message, array $context = []) {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log a critical message
     */
    public function critical($message, array $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log an error message
     */
    public function error($message, array $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public function warning($message, array $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log a notice message
     */
    public function notice($message, array $context = []) {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log an info message
     */
    public function info($message, array $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log a debug message
     */
    public function debug($message, array $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log Evolution API specific events
     */
    public function apiCall($endpoint, $method, $data = [], $response = []) {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'request_data' => $data,
            'response' => $response
        ];
        
        $message = "API Call: {$method} {$endpoint}";
        
        if (isset($response['success']) && $response['success']) {
            $this->info($message, $context);
        } else {
            $this->error($message, $context);
        }
    }
    
    /**
     * Log media processing events
     */
    public function mediaProcessing($action, $filename, $details = []) {
        $context = array_merge([
            'action' => $action,
            'filename' => $filename
        ], $details);
        
        $this->info("Media Processing: {$action} - {$filename}", $context);
    }
    
    /**
     * Log audio processing events
     */
    public function audioProcessing($inputFile, $result = []) {
        $context = [
            'input_file' => $inputFile,
            'processing_result' => $result
        ];
        
        $message = "Audio Processing: " . basename($inputFile);
        
        if (isset($result['success']) && $result['success']) {
            $this->info($message . " - SUCCESS", $context);
        } else {
            $this->error($message . " - FAILED", $context);
        }
    }
    
    /**
     * Main logging method
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function log($level, $message, array $context = []) {
        // Check if this level should be logged
        if (!$this->shouldLog($level)) {
            return;
        }
        
        // Format the log entry
        $timestamp = date($this->dateFormat);
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        
        $logEntry = str_replace(
            ['{timestamp}', '{level}', '{message}', '{context}'],
            [$timestamp, $level, $message, $contextString],
            $this->logFormat
        ) . PHP_EOL;
        
        // Determine log file name
        $logFile = $this->getLogFileName($level);
        
        // Check if file rotation is needed
        $this->rotateLogIfNeeded($logFile);
        
        // Write to file
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also write to general log
        if ($level !== self::DEBUG) {
            $generalLogFile = $this->logDirectory . 'evolution-api.log';
            $this->rotateLogIfNeeded($generalLogFile);
            file_put_contents($generalLogFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        // For critical errors, also log to error log
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            error_log($message . $contextString);
        }
    }
    
    /**
     * Check if a log level should be recorded
     */
    private function shouldLog($level) {
        $levels = [
            self::EMERGENCY => 0,
            self::ALERT => 1,
            self::CRITICAL => 2,
            self::ERROR => 3,
            self::WARNING => 4,
            self::NOTICE => 5,
            self::INFO => 6,
            self::DEBUG => 7
        ];
        
        return $levels[$level] <= $levels[$this->logLevel];
    }
    
    /**
     * Get log file name based on level and date
     */
    private function getLogFileName($level) {
        $date = date('Y-m-d');
        $levelLower = strtolower($level);
        return $this->logDirectory . "evolution-api-{$levelLower}-{$date}.log";
    }
    
    /**
     * Rotate log file if it exceeds maximum size
     */
    private function rotateLogIfNeeded($logFile) {
        if (!file_exists($logFile)) {
            return;
        }
        
        if (filesize($logFile) >= $this->maxFileSize) {
            $this->rotateLogFile($logFile);
        }
    }
    
    /**
     * Perform log file rotation
     */
    private function rotateLogFile($logFile) {
        $fileInfo = pathinfo($logFile);
        $baseName = $fileInfo['dirname'] . '/' . $fileInfo['filename'];
        $extension = $fileInfo['extension'] ?? 'log';
        
        // Shift existing rotated files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = "{$baseName}.{$i}.{$extension}";
            $newFile = "{$baseName}." . ($i + 1) . ".{$extension}";
            
            if (file_exists($oldFile)) {
                if ($i == $this->maxFiles - 1) {
                    unlink($oldFile); // Remove oldest file
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current file to .1
        if (file_exists($logFile)) {
            rename($logFile, "{$baseName}.1.{$extension}");
        }
    }
    
    /**
     * Get log statistics
     */
    public function getLogStats() {
        $stats = [
            'log_directory' => $this->logDirectory,
            'log_level' => $this->logLevel,
            'max_file_size' => $this->formatBytes($this->maxFileSize),
            'max_files' => $this->maxFiles,
            'log_files' => [],
            'total_size' => 0,
            'total_files' => 0
        ];
        
        if (!is_dir($this->logDirectory)) {
            return $stats;
        }
        
        $files = glob($this->logDirectory . '*.log*');
        
        foreach ($files as $file) {
            $fileSize = filesize($file);
            $stats['log_files'][] = [
                'name' => basename($file),
                'size' => $this->formatBytes($fileSize),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
            $stats['total_size'] += $fileSize;
            $stats['total_files']++;
        }
        
        $stats['total_size'] = $this->formatBytes($stats['total_size']);
        
        return $stats;
    }
    
    /**
     * Clean up old log files
     */
    public function cleanup($daysOld = 7) {
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $files = glob($this->logDirectory . '*.log*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        $this->info("Log cleanup completed", [
            'files_removed' => $cleaned,
            'days_old' => $daysOld
        ]);
        
        return $cleaned;
    }
    
    /**
     * Get recent log entries
     */
    public function getRecentLogs($level = null, $limit = 100) {
        $logs = [];
        $pattern = $level ? "*{$level}*.log" : "*.log";
        $files = glob($this->logDirectory . $pattern);
        
        // Sort files by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $count = 0;
        foreach ($files as $file) {
            if ($count >= $limit) break;
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Newest first
            
            foreach ($lines as $line) {
                if ($count >= $limit) break;
                $logs[] = $this->parsLogEntry($line);
                $count++;
            }
        }
        
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Parse a log entry into components
     */
    private function parsLogEntry($logLine) {
        // Parse: [2023-12-07 14:30:25] INFO: Message {"context":"data"}
        if (preg_match('/\[([^\]]+)\]\s+(\w+):\s+([^{]+)(.*)/', $logLine, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => trim($matches[3]),
                'context' => !empty($matches[4]) ? json_decode($matches[4], true) : null,
                'raw' => $logLine
            ];
        }
        
        return [
            'timestamp' => null,
            'level' => 'UNKNOWN',
            'message' => $logLine,
            'context' => null,
            'raw' => $logLine
        ];
    }
    
    /**
     * Format bytes into human readable format
     */
    private function formatBytes($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
    
    /**
     * Set log level
     */
    public function setLogLevel($level) {
        $this->logLevel = $level;
    }
    
    /**
     * Get current log level
     */
    public function getLogLevel() {
        return $this->logLevel;
    }
    
    /**
     * Set custom log format
     */
    public function setLogFormat($format) {
        $this->logFormat = $format;
    }
    
    /**
     * Create a structured log entry for Evolution API responses
     */
    public function logApiResponse($method, $endpoint, $requestData, $response) {
        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'request_size' => strlen(json_encode($requestData)),
            'response_code' => $response['http_code'] ?? null,
            'success' => $response['success'] ?? false,
            'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
        ];
        
        if ($response['success']) {
            $this->info("API Success: {$method} {$endpoint}", $context);
        } else {
            $context['error'] = $response['error'] ?? 'Unknown error';
            $this->error("API Error: {$method} {$endpoint}", $context);
        }
    }
}

?>