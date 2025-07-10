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
     * Sanitize input for database and prevent XSS
     */
    public static function sanitizeInput($input) {
        if (is_string($input)) {
            $input = trim($input);
            $input = stripslashes($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            return $input;
        }
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return $input;
    }
    
    /**
     * Validate input against rules
     */
    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $fieldErrors = self::validateField($field, $value, $ruleSet);
            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            }
        }
        
        return $errors;
    }
    
    private static function validateField($field, $value, $rules) {
        $errors = [];
        $ruleArray = explode('|', $rules);
        
        foreach ($ruleArray as $rule) {
            $params = explode(':', $rule);
            $ruleName = $params[0];
            $ruleParam = $params[1] ?? null;
            
            switch ($ruleName) {
                case 'required':
                    if (empty($value)) {
                        $errors[] = "$field is required";
                    }
                    break;
                    
                case 'integer':
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[] = "$field must be an integer";
                    }
                    break;
                    
                case 'min':
                    if (strlen($value) < $ruleParam) {
                        $errors[] = "$field must be at least $ruleParam characters";
                    }
                    break;
                    
                case 'max':
                    if (strlen($value) > $ruleParam) {
                        $errors[] = "$field must not exceed $ruleParam characters";
                    }
                    break;
                    
                case 'alpha_numeric':
                    if (!ctype_alnum($value)) {
                        $errors[] = "$field must contain only letters and numbers";
                    }
                    break;
                    
                case 'phone':
                    if (!preg_match('/^\+?[1-9]\d{1,14}$/', $value)) {
                        $errors[] = "$field must be a valid phone number";
                    }
                    break;
                    
                case 'safe_text':
                    if (preg_match('/<script|javascript:|on\w+=/i', $value)) {
                        $errors[] = "$field contains unsafe content";
                    }
                    break;
            }
        }
        
        return $errors;
    }
    
    /**
     * Mask phone number for display
     */
    public static function maskPhoneNumber($phone) {
        if (empty($phone)) return '';
        
        $phone = preg_replace('/[^\d+]/', '', $phone);
        $length = strlen($phone);
        
        if ($length <= 4) return $phone;
        
        return substr($phone, 0, 2) . str_repeat('*', $length - 4) . substr($phone, -2);
    }
    
    /**
     * Validate email format
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Check if password meets security requirements
     */
    public static function isValidPassword($password) {
        if (strlen($password) < 12) {
            return false;
        }
        
        // Check for uppercase, lowercase, number, and special character
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get password strength requirements message
     */
    public static function getPasswordRequirements() {
        return "Password must be at least 12 characters with uppercase, lowercase, number, and special character";
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