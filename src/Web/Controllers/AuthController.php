<?php
// src/Web/Controllers/AuthController.php  

require_once __DIR__ . '/../../Core/Helpers.php';
require_once __DIR__ . '/../../Core/Security.php';
require_once __DIR__ . '/../Models/User.php';

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
                    
                    // Redirect to dashboard instead of chat
                    Helpers::redirect('/dashboard');
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
                
                // Redirect to dashboard instead of chat
                Helpers::redirect('/dashboard');
                
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
        // Destroy session
        session_destroy();
        
        // Redirect to home
        Helpers::redirect('/');
    }
}