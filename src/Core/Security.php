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
    
    /**
     * Mask phone number for privacy
     */
    public static function maskPhoneNumber($phone) {
        if (empty($phone)) {
            return '';
        }
        
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If phone number is less than 4 digits, return as is
        if (strlen($phone) < 4) {
            return $phone;
        }
        
        // Show first 3 and last 2 digits, mask the rest
        $firstPart = substr($phone, 0, 3);
        $lastPart = substr($phone, -2);
        $maskedPart = str_repeat('*', strlen($phone) - 5);
        
        return $firstPart . $maskedPart . $lastPart;
    }
    
    /**
     * Check WhatsApp connection state for a user
     * Returns the real-time connection state from Evolution API
     */
    public static function checkWhatsAppConnection($userId) {
        try {
            // Check if WhatsApp is enabled
            if (!defined('WHATSAPP_ENABLED') || !WHATSAPP_ENABLED) {
                return 'disabled';
            }
            
            // Get user's WhatsApp instance
            require_once __DIR__ . '/../Api/Models/WhatsAppInstance.php';
            require_once __DIR__ . '/../Api/WhatsApp/EvolutionAPI.php';
            
            $instanceModel = new WhatsAppInstance();
            $instance = $instanceModel->findByUserId($userId);
            
            if (!$instance) {
                return 'no_instance';
            }
            
            // Check real-time connection state from Evolution API
            $evolutionAPI = new EvolutionAPI();
            $connectionState = $evolutionAPI->getConnectionState($instance['instance_name']);
            
            return $connectionState;
            
        } catch (Exception $e) {
            error_log("WhatsApp connection check failed: " . $e->getMessage());
            return 'unknown';
        }
    }
    
    /**
     * Require WhatsApp connection for web endpoints
     * Redirects to QR scan page if connection state is not 'open'
     */
    public static function requireWhatsAppConnection($userId = null) {
        require_once __DIR__ . '/Helpers.php';
        
        // First check basic authentication
        if (!Helpers::isAuthenticated()) {
            Helpers::redirect('/login');
            return;
        }
        
        $userId = $userId ?: Helpers::getCurrentUserId();
        
        if (!$userId) {
            Helpers::redirect('/login');
            return;
        }
        
        $connectionState = self::checkWhatsAppConnection($userId);
        
        // Allow access if WhatsApp is disabled or if connection state is 'open'
        if ($connectionState === 'disabled' || $connectionState === 'open') {
            return; // Access granted
        }
        
        // For all other states, redirect to WhatsApp connect page
        Helpers::redirect('/whatsapp/connect?state=' . urlencode($connectionState));
    }
    
    /**
     * Require WhatsApp connection for API endpoints
     * Returns JSON error if connection state is not 'open'
     */
    public static function requireWhatsAppConnectionAPI($userId = null) {
        require_once __DIR__ . '/Helpers.php';
        
        // First check basic authentication
        if (!Helpers::isAuthenticated()) {
            Helpers::jsonError('Unauthorized', 401);
            return;
        }
        
        $userId = $userId ?: Helpers::getCurrentUserId();
        
        if (!$userId) {
            Helpers::jsonError('Unauthorized', 401);
            return;
        }
        
        $connectionState = self::checkWhatsAppConnection($userId);
        
        // Only allow access if connection state is 'open'
        if ($connectionState !== 'open') {
            Helpers::jsonError('WhatsApp not connected. Current state: ' . $connectionState, 403);
            return;
        }
    }
    
    /**
     * Check if user is fully authenticated (session + WhatsApp connection)
     * This is the main authentication check for the 2-step auth system
     */
    public static function isFullyAuthenticated($userId = null) {
        require_once __DIR__ . '/Helpers.php';
        
        // First check basic session authentication
        if (!Helpers::isAuthenticated()) {
            return false;
        }
        
        // If WhatsApp is disabled, only session auth is required
        if (!defined('WHATSAPP_ENABLED') || !WHATSAPP_ENABLED) {
            return true;
        }
        
        $userId = $userId ?: Helpers::getCurrentUserId();
        
        if (!$userId) {
            return false;
        }
        
        // Check cached WhatsApp auth state first (for performance)
        if (isset($_SESSION['whatsapp_authenticated']) && 
            isset($_SESSION['last_connection_check']) &&
            (time() - $_SESSION['last_connection_check']) < 30) {
            return $_SESSION['whatsapp_authenticated'];
        }
        
        // Check real-time WhatsApp connection state
        $connectionState = self::checkWhatsAppConnection($userId);
        $isWhatsAppAuthenticated = ($connectionState === 'open');
        
        // Update session cache
        $_SESSION['whatsapp_authenticated'] = $isWhatsAppAuthenticated;
        $_SESSION['connection_state'] = $connectionState;
        $_SESSION['last_connection_check'] = time();
        
        return $isWhatsAppAuthenticated;
    }
    
    /**
     * Require full authentication (session + WhatsApp) for web endpoints
     * Redirects to appropriate authentication step if not fully authenticated
     */
    public static function requireFullAuthentication($userId = null) {
        require_once __DIR__ . '/Helpers.php';
        
        // First check basic session authentication
        if (!Helpers::isAuthenticated()) {
            Helpers::redirect('/login');
            return;
        }
        
        // If WhatsApp is disabled, only session auth is required
        if (!defined('WHATSAPP_ENABLED') || !WHATSAPP_ENABLED) {
            return; // Access granted
        }
        
        $userId = $userId ?: Helpers::getCurrentUserId();
        
        if (!$userId) {
            Helpers::redirect('/login');
            return;
        }
        
        // Check WhatsApp connection state
        if (!self::isFullyAuthenticated($userId)) {
            $connectionState = $_SESSION['connection_state'] ?? 'unknown';
            
            // Redirect to WhatsApp connect page with current state
            Helpers::redirect('/whatsapp/connect?state=' . urlencode($connectionState));
            return;
        }
        
        // Full authentication successful - access granted
    }
    
    /**
     * Require full authentication for API endpoints
     * Returns JSON error if not fully authenticated
     */
    public static function requireFullAuthenticationAPI($userId = null) {
        require_once __DIR__ . '/Helpers.php';
        
        // First check basic session authentication
        if (!Helpers::isAuthenticated()) {
            Helpers::jsonError('Not authenticated. Please log in.', 401);
            return;
        }
        
        // If WhatsApp is disabled, only session auth is required
        if (!defined('WHATSAPP_ENABLED') || !WHATSAPP_ENABLED) {
            return; // Access granted
        }
        
        $userId = $userId ?: Helpers::getCurrentUserId();
        
        if (!$userId) {
            Helpers::jsonError('Invalid user session', 401);
            return;
        }
        
        // Check full authentication
        if (!self::isFullyAuthenticated($userId)) {
            $connectionState = $_SESSION['connection_state'] ?? 'unknown';
            
            Helpers::jsonError(
                'WhatsApp authentication required. Current state: ' . $connectionState, 
                403, 
                ['requires_whatsapp_auth' => true, 'connection_state' => $connectionState]
            );
            return;
        }
        
        // Full authentication successful - access granted
    }
    
    /**
     * Update WhatsApp authentication state in session
     * Called when connection state changes
     */
    public static function updateWhatsAppAuthState($userId, $connectionState) {
        $isAuthenticated = ($connectionState === 'open');
        
        $_SESSION['whatsapp_authenticated'] = $isAuthenticated;
        $_SESSION['connection_state'] = $connectionState;
        $_SESSION['last_connection_check'] = time();
        
        return $isAuthenticated;
    }
    
    /**
     * Clear WhatsApp authentication state
     * Called on logout or disconnection
     */
    public static function clearWhatsAppAuthState() {
        unset($_SESSION['whatsapp_authenticated']);
        unset($_SESSION['connection_state']);
        unset($_SESSION['last_connection_check']);
    }
    
    /**
     * Get public routes that don't require full authentication
     */
    public static function getPublicRoutes() {
        return [
            '/',
            '/login',
            '/register',
            '/logout',
            '/whatsapp/connect',
            '/whatsapp/createInstance',
            '/whatsapp/generateQR', 
            '/whatsapp/getConnectionStatus'  // Consolidated endpoint
        ];
    }
    
    /**
     * Check if a route is public (doesn't require full authentication)
     */
    public static function isPublicRoute($route) {
        $publicRoutes = self::getPublicRoutes();
        
        // Remove query parameters for comparison
        $cleanRoute = parse_url($route, PHP_URL_PATH);
        
        return in_array($cleanRoute, $publicRoutes);
    }
}