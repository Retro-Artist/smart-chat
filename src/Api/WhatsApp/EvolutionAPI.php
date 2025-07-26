<?php
// src/Api/WhatsApp/EvolutionAPI.php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../Core/Logger.php';

class EvolutionAPI {
    private $apiUrl;
    private $apiKey;
    private $timeout;
    private $lastRequestTime = 0;
    private $minRequestInterval = 100; // 100ms between requests for rate limiting
    
    public function __construct() {
        $this->apiUrl = rtrim(EVOLUTION_API_URL, '/');
        $this->apiKey = EVOLUTION_API_KEY;
        $this->timeout = WEBHOOK_TIMEOUT;
    }
    
    public function createInstance($instanceName, $phoneNumber = null, $webhookUrl = null) {
        $data = [
            'instanceName' => $instanceName,
            'integration' => 'WHATSAPP-BAILEYS'
        ];
        
        if ($phoneNumber) {
            $data['number'] = $phoneNumber;
        }
        
        // Configure webhook according to the correct Evolution API format
        if ($webhookUrl) {
            $data['webhook'] = [
                'url' => $webhookUrl,
                'byEvents' => null,
                'base64' => null,
                'events' => explode(',', str_replace(' ', '', WEBHOOK_ENABLED_EVENTS))
            ];
        }
        
        return $this->makeRequest('POST', "/instance/create", $data);
    }
    
    public function getInstance($instanceName) {
        return $this->makeRequest('GET', "/instance/fetchInstances/{$instanceName}");
    }
    
    public function getInstanceStatus($instanceName) {
        return $this->makeRequest('GET', "/instance/connectionState/{$instanceName}");
    }
    
    public function connectInstance($instanceName) {
        return $this->makeRequest('GET', "/instance/connect/{$instanceName}");
    }
    
    public function restartInstance($instanceName) {
        return $this->makeRequest('PUT', "/instance/restart/{$instanceName}");
    }
    
    public function logoutInstance($instanceName) {
        return $this->makeRequest('DELETE', "/instance/logout/{$instanceName}");
    }
    
    public function deleteInstance($instanceName) {
        return $this->makeRequest('DELETE', "/instance/delete/{$instanceName}");
    }
    
    public function setPresence($instanceName, $presence = 'available') {
        $data = ['presence' => $presence];
        return $this->makeRequest('POST', "/instance/setPresence/{$instanceName}", $data);
    }
    
    public function setWebhook($instanceName, $webhookUrl, $events = null) {
        $data = [
            'enabled' => true,
            'url' => $webhookUrl,
            'webhookByEvents' => true,
            'webhookBase64' => true,
            'events' => $events ?: explode(',', WEBHOOK_ENABLED_EVENTS)
        ];
        
        return $this->makeRequest('POST', "/webhook/set/{$instanceName}", $data);
    }
    
    public function getWebhook($instanceName) {
        return $this->makeRequest('GET', "/webhook/find/{$instanceName}");
    }
    
    public function setSettings($instanceName, $settings = null) {
        $defaultSettings = [
            'rejectCall' => true,
            'msgCall' => 'Please, wait a moment',
            'groupsIgnore' => true,
            'alwaysOnline' => true,
            'readMessages' => true,
            'readStatus' => true,
            'syncFullHistory' => true
        ];
        
        $data = $settings ?: $defaultSettings;
        return $this->makeRequest('POST', "/settings/set/{$instanceName}", $data);
    }
    
    public function getSettings($instanceName) {
        return $this->makeRequest('GET', "/settings/find/{$instanceName}");
    }
    
    public function sendTextMessage($instanceName, $phoneNumber, $text) {
        $data = [
            'number' => $phoneNumber,
            'text' => $text
        ];
        
        return $this->makeRequest('POST', "/message/sendText/{$instanceName}", $data);
    }
    
    public function sendMediaMessage($instanceName, $phoneNumber, $mediaType, $media, $caption = null) {
        $data = [
            'number' => $phoneNumber,
            'mediatype' => $mediaType,
            'media' => $media
        ];
        
        if ($caption) {
            $data['caption'] = $caption;
        }
        
        return $this->makeRequest('POST', "/message/sendMedia/{$instanceName}", $data);
    }
    
    public function sendAudio($instanceName, $phoneNumber, $audio) {
        $data = [
            'number' => $phoneNumber,
            'audio' => $audio
        ];
        
        return $this->makeRequest('POST', "/message/sendWhatsAppAudio/{$instanceName}", $data);
    }
    
    public function findMessages($instanceName, $phoneNumber = null, $limit = 50) {
        $params = ['limit' => $limit];
        
        if ($phoneNumber) {
            $params['number'] = $phoneNumber;
        }
        
        $queryString = http_build_query($params);
        return $this->makeRequest('GET', "/chat/findMessages/{$instanceName}?{$queryString}");
    }
    
    public function findContacts($instanceName) {
        return $this->makeRequest('GET', "/chat/findContacts/{$instanceName}");
    }
    
    public function findChats($instanceName) {
        return $this->makeRequest('GET', "/chat/findChats/{$instanceName}");
    }
    
    public function checkIsWhatsapp($instanceName, $phoneNumbers) {
        if (!is_array($phoneNumbers)) {
            $phoneNumbers = [$phoneNumbers];
        }
        
        $data = ['numbers' => $phoneNumbers];
        return $this->makeRequest('POST', "/chat/whatsappNumbers/{$instanceName}", $data);
    }
    
    public function getProfilePicture($instanceName, $phoneNumber) {
        $data = ['number' => $phoneNumber];
        return $this->makeRequest('POST', "/chat/fetchProfilePictureUrl/{$instanceName}", $data);
    }
    
    public function updateProfileName($instanceName, $name) {
        $data = ['name' => $name];
        return $this->makeRequest('POST', "/profile/updateProfileName/{$instanceName}", $data);
    }
    
    public function updateProfileStatus($instanceName, $status) {
        $data = ['status' => $status];
        return $this->makeRequest('POST', "/profile/updateProfileStatus/{$instanceName}", $data);
    }
    
    public function updateProfilePicture($instanceName, $picture) {
        $data = ['picture' => $picture];
        return $this->makeRequest('POST', "/profile/updateProfilePicture/{$instanceName}", $data);
    }
    
    public function createGroup($instanceName, $subject, $participants, $description = null) {
        $data = [
            'subject' => $subject,
            'participants' => $participants
        ];
        
        if ($description) {
            $data['description'] = $description;
        }
        
        return $this->makeRequest('POST', "/group/create/{$instanceName}", $data);
    }
    
    public function getGroupInfo($instanceName, $groupId) {
        $data = ['groupJid' => $groupId];
        return $this->makeRequest('POST', "/group/findGroupInfos/{$instanceName}", $data);
    }
    
    public function addParticipant($instanceName, $groupId, $participants) {
        $data = [
            'groupJid' => $groupId,
            'participants' => is_array($participants) ? $participants : [$participants]
        ];
        
        return $this->makeRequest('POST', "/group/updateParticipant/{$instanceName}", $data);
    }
    
    public function removeParticipant($instanceName, $groupId, $participants) {
        $data = [
            'groupJid' => $groupId,
            'participants' => is_array($participants) ? $participants : [$participants],
            'action' => 'remove'
        ];
        
        return $this->makeRequest('POST', "/group/updateParticipant/{$instanceName}", $data);
    }
    
    public function promoteParticipant($instanceName, $groupId, $participants) {
        $data = [
            'groupJid' => $groupId,
            'participants' => is_array($participants) ? $participants : [$participants],
            'action' => 'promote'
        ];
        
        return $this->makeRequest('POST', "/group/updateParticipant/{$instanceName}", $data);
    }
    
    public function demoteParticipant($instanceName, $groupId, $participants) {
        $data = [
            'groupJid' => $groupId,
            'participants' => is_array($participants) ? $participants : [$participants],
            'action' => 'demote'
        ];
        
        return $this->makeRequest('POST', "/group/updateParticipant/{$instanceName}", $data);
    }
    
    public function updateGroupSubject($instanceName, $groupId, $subject) {
        $data = [
            'groupJid' => $groupId,
            'subject' => $subject
        ];
        
        return $this->makeRequest('POST', "/group/updateGroupSubject/{$instanceName}", $data);
    }
    
    public function updateGroupDescription($instanceName, $groupId, $description) {
        $data = [
            'groupJid' => $groupId,
            'description' => $description
        ];
        
        return $this->makeRequest('POST', "/group/updateGroupDescription/{$instanceName}", $data);
    }
    
    public function updateGroupPicture($instanceName, $groupId, $picture) {
        $data = [
            'groupJid' => $groupId,
            'picture' => $picture
        ];
        
        return $this->makeRequest('POST', "/group/updateGroupPicture/{$instanceName}", $data);
    }
    
    public function getGroupInviteCode($instanceName, $groupId) {
        $data = ['groupJid' => $groupId];
        return $this->makeRequest('POST', "/group/inviteCode/{$instanceName}", $data);
    }
    
    public function revokeGroupInviteCode($instanceName, $groupId) {
        $data = ['groupJid' => $groupId];
        return $this->makeRequest('POST', "/group/revokeInviteCode/{$instanceName}", $data);
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $this->rateLimitDelay();
        
        $url = $this->apiUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->apiKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Logger::getInstance()->error("Evolution API cURL error: {$error}", [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data
            ]);
            throw new Exception("API request failed: {$error}");
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            Logger::getInstance()->error("Evolution API HTTP error: {$httpCode}", [
                'method' => $method,
                'endpoint' => $endpoint,
                'response' => $response
            ]);
            throw new Exception("API request failed with HTTP {$httpCode}: {$response}");
        }
        
        Logger::getInstance()->info("Evolution API request successful", [
            'method' => $method,
            'endpoint' => $endpoint,
            'http_code' => $httpCode
        ]);
        
        return $result;
    }
    
    private function rateLimitDelay() {
        $currentTime = microtime(true) * 1000; // Convert to milliseconds
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;
        
        if ($timeSinceLastRequest < $this->minRequestInterval) {
            $sleepTime = ($this->minRequestInterval - $timeSinceLastRequest) * 1000; // Convert to microseconds
            usleep($sleepTime);
        }
        
        $this->lastRequestTime = microtime(true) * 1000;
    }
    
    public function setRateLimit($minIntervalMs) {
        $this->minRequestInterval = $minIntervalMs;
    }
    
    public function isHealthy() {
        try {
            $response = $this->makeRequest('GET', '/');
            return isset($response['status']) || isset($response['message']);
        } catch (Exception $e) {
            Logger::getInstance()->error('Evolution API health check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getConnectionState($instanceName) {
        try {
            $response = $this->makeRequest('GET', "/instance/connectionState/{$instanceName}");
            
            // Extract connection state from response
            if (isset($response['instance']['state'])) {
                return $response['instance']['state'];
            }
            
            // Fallback to status if state not available
            if (isset($response['instance']['status'])) {
                return $response['instance']['status'];
            }
            
            return 'unknown';
        } catch (Exception $e) {
            Logger::getInstance()->error("Failed to get connection state for {$instanceName}: " . $e->getMessage());
            return 'unknown';
        }
    }
    
}