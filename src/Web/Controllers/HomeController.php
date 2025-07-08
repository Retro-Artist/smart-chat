<?php
// src/Web/Controllers/HomeController.php  

require_once __DIR__ . '/../../Core/Helpers.php';

class HomeController {
    
    public function index() {
        // If user is logged in, redirect to dashboard
        if (Helpers::isAuthenticated()) {
            Helpers::redirect('/dashboard');
        }
        
        // Load home/landing page for non-authenticated users
        Helpers::loadView('home', [
            'pageTitle' => 'OpenAI Webchat - AI-Powered Conversations'
        ]);
    }
}