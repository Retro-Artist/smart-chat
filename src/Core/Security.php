<?php
// src/Core/Security.php

class Security {
    
    /**
     * Hash a password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify a password against a hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate a secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Sanitize input for database
     */
    public static function sanitizeInput($input) {
        if (is_string($input)) {
            return trim($input);
        }
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return $input;
    }
    
    /**
     * Validate email format
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Check if password meets minimum requirements
     */
    public static function isValidPassword($password) {
        return strlen($password) >= 6;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting check (simple implementation)
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        $cacheKey = "rate_limit_{$key}";
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = ['count' => 0, 'reset_time' => time() + $timeWindow];
        }
        
        $data = $_SESSION[$cacheKey];
        
        // Reset if time window expired
        if (time() > $data['reset_time']) {
            $_SESSION[$cacheKey] = ['count' => 1, 'reset_time' => time() + $timeWindow];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$cacheKey]['count']++;
        return true;
    }
}