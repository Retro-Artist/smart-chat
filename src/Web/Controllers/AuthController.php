<?php
// src/Web/Controllers/AuthController.php  

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Core/Security.php';
require_once __DIR__ . '/../../Api/Models/User.php';
require_once __DIR__ . '/../../Api/Models/WhatsAppInstance.php';
require_once __DIR__ . '/../../Api/WhatsApp/InstanceManager.php';

class AuthController {
    
    public function showLogin() {
        // If already logged in, redirect to dashboard
        if (Helpers::isAuthenticated()) {
            Helpers::redirect('/dashboard');
        }
        
        // Load login view
        Helpers::loadView('login', [
            'pageTitle' => 'Login - OpenAI Webchat'
        ]);
    }
    
    public function processLogin() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $error = '';
        
        // Rate limiting check
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Security::checkRateLimit("login_{$clientIP}", 5, 300)) {
            $error = 'Too many login attempts. Please try again in 5 minutes.';
        } elseif (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            try {
                $userModel = new User();
                $user = $userModel->findByUsername($username);
                
                if ($user && $userModel->verifyPassword($user, $password)) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Update last login
                    $userModel->updateLastLogin($user['id']);
                    
                    // Check WhatsApp instance status if enabled
                    if (WHATSAPP_ENABLED) {
                        $this->handleWhatsAppLogin($user['id']);
                    } else {
                        // Redirect to dashboard if WhatsApp is disabled
                        Helpers::redirect('/dashboard');
                    }
                } else {
                    $error = 'Invalid username or password';
                }
            } catch (Exception $e) {
                $error = 'Login failed. Please try again.';
                error_log("Login error: " . $e->getMessage());
            }
        }
        
        // If we get here, login failed - show form with error
        Helpers::loadView('login', [
            'pageTitle' => 'Login - OpenAI Webchat',
            'error' => $error
        ]);
    }
    
    public function showRegister() {
        // If already logged in, redirect to dashboard
        if (Helpers::isAuthenticated()) {
            Helpers::redirect('/dashboard');
        }
        
        // Load register view
        Helpers::loadView('register', [
            'pageTitle' => 'Register - OpenAI Webchat'
        ]);
    }
    
    public function processRegister() {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $error = '';
        
        // Rate limiting check
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Security::checkRateLimit("register_{$clientIP}", 3, 300)) {
            $error = 'Too many registration attempts. Please try again in 5 minutes.';
        } elseif (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (!Security::isValidPassword($password)) {
            $error = 'Password must be at least 6 characters';
        } elseif (!Security::isValidEmail($email)) {
            $error = 'Invalid email format';
        } else {
            try {
                $userModel = new User();
                $user = $userModel->create($username, $email, $password);
                
                // Auto-login after registration
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Check WhatsApp instance status if enabled
                if (WHATSAPP_ENABLED) {
                    $this->handleWhatsAppLogin($user['id']);
                } else {
                    // Redirect to dashboard if WhatsApp is disabled
                    Helpers::redirect('/dashboard');
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        // If we get here, registration failed - show form with error
        Helpers::loadView('register', [
            'pageTitle' => 'Register - OpenAI Webchat',
            'error' => $error
        ]);
    }
    
    public function logout() {
        // Clear WhatsApp authentication state before destroying session
        require_once __DIR__ . '/../../Core/Security.php';
        Security::clearWhatsAppAuthState();
        
        // Destroy session
        session_destroy();
        
        // Redirect to home
        Helpers::redirect('/');
    }
    
    private function handleWhatsAppLogin($userId) {
        try {
            require_once __DIR__ . '/../../Api/WhatsApp/EvolutionAPI.php';
            
            $instanceModel = new WhatsAppInstance();
            $instance = $instanceModel->findByUserId($userId);
            
            if (!$instance) {
                // Auto-create WhatsApp instance for new users (WhatsApp Web behavior)
                try {
                    require_once __DIR__ . '/../../Api/WhatsApp/InstanceManager.php';
                    $instanceManager = new InstanceManager();
                    
                    Logger::getInstance()->info("Auto-creating WhatsApp instance for user", [
                        'user_id' => $userId
                    ]);
                    
                    // Create instance automatically
                    $instance = $instanceManager->createInstance($userId, null);
                    
                    // Set state for newly created instance
                    $_SESSION['connection_state'] = 'connecting';
                    $_SESSION['whatsapp_first_login'] = true;
                    
                    Logger::getInstance()->info("WhatsApp instance auto-created successfully", [
                        'user_id' => $userId,
                        'instance_id' => $instance['id']
                    ]);
                    
                    // Redirect to connect page to show QR code
                    Helpers::redirect('/whatsapp/connect?state=connecting&auto_created=1');
                    
                } catch (Exception $e) {
                    Logger::getInstance()->error("Failed to auto-create WhatsApp instance: " . $e->getMessage());
                    $_SESSION['connection_state'] = 'no_instance';
                    Helpers::redirect('/whatsapp/connect?error=' . urlencode('Failed to create WhatsApp instance: ' . $e->getMessage()));
                }
            } else {
                // Check if instance actually exists in Evolution API first
                $evolutionAPI = new EvolutionAPI();
                $connectionState = 'unknown';
                
                try {
                    // Check if instance exists in Evolution API
                    if (!$evolutionAPI->instanceExists($instance['instance_name'])) {
                        Logger::getInstance()->warning("Instance exists in database but not in Evolution API, recreating", [
                            'instance_id' => $instance['id'],
                            'instance_name' => $instance['instance_name'],
                            'user_id' => $userId
                        ]);
                        
                        // Delete database record and recreate the instance
                        require_once __DIR__ . '/../../Api/WhatsApp/InstanceManager.php';
                        $instanceManager = new InstanceManager();
                        
                        // Delete from database
                        $instanceModel = new WhatsAppInstance();
                        $instanceModel->delete($instance['id']);
                        
                        // Recreate instance
                        $instance = $instanceManager->createInstance($userId, null);
                        $connectionState = 'connecting';
                        $_SESSION['whatsapp_instance_recreated'] = true;
                        
                        Logger::getInstance()->info("WhatsApp instance recreated successfully", [
                            'old_instance_id' => $instance['id'] ?? 'unknown',
                            'new_instance_id' => $instance['id'],
                            'user_id' => $userId
                        ]);
                    } else {
                        // Instance exists, get its connection state
                        $connectionState = $evolutionAPI->getConnectionState($instance['instance_name']);
                    }
                } catch (Exception $e) {
                    Logger::getInstance()->error("Failed to validate/get connection state: " . $e->getMessage());
                    $connectionState = 'unknown';
                }
                
                // Store connection state in session for QR page
                $_SESSION['connection_state'] = $connectionState;
                
                // ALWAYS redirect to QR scan page - let the QR page handle routing based on state
                // The QR page will auto-redirect to dashboard if connection state is 'open'
                switch ($connectionState) {
                    case 'open':
                        // Connected - redirect to QR page which will auto-redirect to dashboard
                        $_SESSION['whatsapp_already_connected'] = true;
                        Helpers::redirect('/whatsapp/connect?state=open');
                        break;
                        
                    case 'connecting':
                        // Connecting - show existing QR code
                        $_SESSION['whatsapp_connecting'] = true;
                        Helpers::redirect('/whatsapp/connect?state=connecting');
                        break;
                        
                    case 'disconnected':
                    case 'failed':
                        // Disconnected/Failed - needs restart and new QR
                        $_SESSION['whatsapp_reconnect_needed'] = true;
                        Helpers::redirect('/whatsapp/connect?state=' . $connectionState);
                        break;
                        
                    case 'close':
                    case 'unknown':
                    default:
                        // Needs fresh QR code
                        $_SESSION['whatsapp_qr_needed'] = true;
                        Helpers::redirect('/whatsapp/connect?state=' . $connectionState);
                        break;
                }
            }
        } catch (Exception $e) {
            error_log("WhatsApp login handling error: " . $e->getMessage());
            // Even on error, redirect to QR scan page to handle the error state
            $_SESSION['error'] = 'WhatsApp connection check failed';
            $_SESSION['connection_state'] = 'error';
            Helpers::redirect('/whatsapp/connect?state=error');
        }
    }
}