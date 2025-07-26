<?php
// src/Core/Helpers.php - UPDATED VIEW PATH

class Helpers {
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Check if user is fully authenticated (session + WhatsApp if enabled)
     * This is the main authentication method for the 2-step auth system
     */
    public static function isFullyAuthenticated() {
        require_once __DIR__ . '/Security.php';
        return Security::isFullyAuthenticated();
    }
    
    /**
     * Redirect to a URL
     */
    public static function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Return JSON response
     */
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Return JSON error response
     */
    public static function jsonError($message, $statusCode = 400, $additionalData = []) {
        $response = array_merge(['error' => $message], $additionalData);
        self::jsonResponse($response, $statusCode);
    }
    
    /**
     * Require authentication for API endpoints
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            self::jsonError('Unauthorized', 401);
        }
    }
    
    /**
     * Require authentication for web endpoints
     * Updated default redirect to dashboard
     */
    public static function requireWebAuth($redirectTo = '/dashboard') {
        if (!self::isAuthenticated()) {
            // If trying to access a protected page, redirect to login
            // But preserve the intended destination
            if ($redirectTo === '/dashboard') {
                self::redirect('/login');
            } else {
                self::redirect($redirectTo);
            }
        }
    }
    
    /**
     * Sanitize HTML output
     */
    public static function escape($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get JSON input from request body
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        $decoded = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::jsonError('Invalid JSON input');
        }
        
        return $decoded;
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($data, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                self::jsonError("Field '{$field}' is required");
            }
        }
    }
    
    /**
     * Load a view with variables
     */
    public static function loadView($viewName, $variables = []) {
        // Extract variables for use in view
        extract($variables);
        
        // Capture the view content
        ob_start();
        include __DIR__ . "/../Web/Views/{$viewName}.php";
        $content = ob_get_clean();
        
        // Return the content directly - layout wrapping is handled in the view file itself
        echo $content;
    }
}