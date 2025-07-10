<?php
// src/Web/Models/User.php

require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Core/Security.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findByUsername($username) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE username = ?", 
            [$username]
        );
    }
    
    public function findByEmail($email) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE email = ?", 
            [$email]
        );
    }
    
    public function findById($id) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = ?", 
            [$id]
        );
    }
    
    public function create($username, $email, $password) {
        // Check if username or email already exists
        if ($this->findByUsername($username)) {
            throw new Exception("Username already exists");
        }
        
        if ($this->findByEmail($email)) {
            throw new Exception("Email already exists");
        }
        
        // Validate input
        if (!Security::isValidEmail($email)) {
            throw new Exception("Invalid email format");
        }
        
        if (!Security::isValidPassword($password)) {
            throw new Exception("Password must be at least 6 characters");
        }
        
        // Hash password
        $passwordHash = Security::hashPassword($password);
        
        // Insert user
        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);
        
        // Return the created user
        return $this->findById($userId);
    }
    
    public function verifyPassword($user, $password) {
        return Security::verifyPassword($password, $user['password_hash']);
    }
    
    public function updateLastLogin($userId) {
        $this->db->update('users', 
            ['updated_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$userId]
        );
    }
    
    public function updatePassword($userId, $newPassword) {
        if (!Security::isValidPassword($newPassword)) {
            throw new Exception("Password must be at least 6 characters");
        }
        
        $passwordHash = Security::hashPassword($newPassword);
        
        $this->db->update('users', 
            ['password_hash' => $passwordHash], 
            'id = ?', 
            [$userId]
        );
    }
    
    public function updateProfile($userId, $data) {
        $allowedFields = ['username', 'email'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'email' && !Security::isValidEmail($data[$field])) {
                    throw new Exception("Invalid email format");
                }
                $updateData[$field] = $data[$field];
            }
        }
        
        if (!empty($updateData)) {
            $this->db->update('users', $updateData, 'id = ?', [$userId]);
        }
        
        return $this->findById($userId);
    }
}