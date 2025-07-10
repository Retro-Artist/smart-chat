<?php

/**
 * Evolution API - Instances Controller
 * 
 * Handles all instance-related operations for the Evolution API
 * Based on the official Evolution API documentation
 */

class Instances {
    private $api;
    
    /**
     * Constructor
     * 
     * @param EvolutionAPI $api The main EvolutionAPI instance
     */
    public function __construct(EvolutionAPI $api) {
        $this->api = $api;
    }
    
    /**
     * POST Create Instance
     * 
     * @param string $instanceName Name of the instance
     * @param array $options Additional instance configuration options
     * @return array Response data
     */
    public function createInstance($instanceName, $options = []) {
        $data = array_merge([
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS'
        ], $options);
        
        return $this->api->makeRequest("instance/create", $data, 'POST');
    }
    
    /**
     * GET Fetch Instances
     * 
     * @param string $instanceName Optional instance name to filter
     * @return array Response data
     */
    public function fetchInstances($instanceName = null) {
        $endpoint = "instance/fetchInstances";
        if ($instanceName) {
            $endpoint .= "?instanceName=" . urlencode($instanceName);
        }
        
        return $this->api->makeRequest($endpoint, null, 'GET');
    }
    
    /**
     * GET Instance Connect
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @param string $number Optional phone number for pairing
     * @return array Response data with QR code or pairing code
     */
    public function instanceConnect($instance = null, $number = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        $endpoint = "instance/connect/{$instanceName}";
        
        if ($number) {
            $endpoint .= "?number=" . urlencode($number);
        }
        
        return $this->api->makeRequest($endpoint, null, 'GET');
    }
    
    /**
     * PUT Restart Instance
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function restartInstance($instance = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        return $this->api->makeRequest("instance/restart/{$instanceName}", null, 'PUT');
    }
    
    /**
     * GET Connection State
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data with connection state
     */
    public function connectionState($instance = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        return $this->api->makeRequest("instance/connectionState/{$instanceName}", null, 'GET');
    }
    
    /**
     * DEL Logout Instance
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function logoutInstance($instance = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        return $this->api->makeRequest("instance/logout/{$instanceName}", null, 'DELETE');
    }
    
    /**
     * DEL Delete Instance
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function deleteInstance($instance = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        return $this->api->makeRequest("instance/delete/{$instanceName}", null, 'DELETE');
    }
    
    /**
     * POST Set Presence
     * 
     * @param string $presence Presence status (available, unavailable, composing, recording, paused)
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function setPresence($presence, $instance = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        $data = [
            'presence' => $presence
        ];
        
        return $this->api->makeRequest("instance/setPresence/{$instanceName}", $data, 'POST');
    }
    
    // ======================
    // HELPER METHODS
    // ======================
    
    /**
     * Check if the current instance is connected
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return bool True if connected, false otherwise
     */
    public function isInstanceConnected($instance = null) {
        $connectionState = $this->connectionState($instance);
        
        if ($connectionState['success'] && isset($connectionState['data']['instance']['state'])) {
            return $connectionState['data']['instance']['state'] === 'open';
        }
        
        return false;
    }
    
    /**
     * Get instance information
     * 
     * @param string $instanceName Instance name to get info for
     * @return array Response data with instance information
     */
    public function getInstanceInfo($instanceName = null) {
        $instances = $this->fetchInstances($instanceName);
        
        if ($instances['success'] && isset($instances['data'])) {
            if ($instanceName) {
                // Return specific instance if found
                foreach ($instances['data'] as $instanceData) {
                    // Handle different possible data structures
                    if (isset($instanceData['instance'])) {
                        // Standard structure: data -> instance -> details
                        $instance = $instanceData['instance'];
                        $fullData = $instanceData;
                    } else {
                        // Direct structure: data -> details
                        $instance = $instanceData;
                        $fullData = $instanceData;
                    }
                    
                    // Check instance name in different possible fields
                    $name = $instance['instanceName'] ?? $instance['name'] ?? null;
                    
                    if ($name === $instanceName) {
                        return ['success' => true, 'data' => $fullData];
                    }
                }
                return ['success' => false, 'error' => 'Instance not found'];
            }
            return $instances;
        }
        
        return $instances;
    }
    
/**
     * Create Basic Instance (simplified version)
     * 
     * @param string $instanceName Name of the instance
     * @return array Response data
     */
    public function createBasicInstance($instanceName) {
        $data = [
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
            'rejectCall' => true,
            'alwaysOnline' => true,
            'readMessages' => false,
            'readStatus' => false,
            'syncFullHistory' => true
        ];
        
        return $this->api->makeRequest("instance/create", $data, 'POST');
    }
    
    /**
     * Create instance with webhook configuration
     * 
     * @param string $instanceName Name of the instance
     * @param string $webhookUrl Webhook URL
     * @param array $webhookEvents Array of webhook events
     * @param array $additionalOptions Additional options
     * @return array Response data
     */
    public function createInstanceWithWebhook($instanceName, $webhookUrl, $webhookEvents = ['APPLICATION_STARTUP'], $additionalOptions = []) {
        $options = array_merge([
            'webhookUrl' => $webhookUrl,
            'webhookByEvents' => true,
            'webhookBase64' => true,
            'webhookEvents' => $webhookEvents
        ], $additionalOptions);
        
        return $this->createInstance($instanceName, $options);
    }
    
    /**
     * Wait for instance to be connected
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @param int $maxAttempts Maximum number of attempts (default: 30)
     * @param int $delay Delay between attempts in seconds (default: 2)
     * @return array Final connection state
     */
    public function waitForConnection($instance = null, $maxAttempts = 30, $delay = 2) {
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $connectionState = $this->connectionState($instance);
            
            if ($connectionState['success'] && isset($connectionState['data']['instance']['state'])) {
                $state = $connectionState['data']['instance']['state'];
                if ($state === 'open') {
                    return [
                        'success' => true,
                        'connected' => true,
                        'attempts' => $attempts + 1,
                        'data' => $connectionState['data']
                    ];
                }
            }
            
            $attempts++;
            if ($attempts < $maxAttempts) {
                sleep($delay);
            }
        }
        
        return [
            'success' => false,
            'connected' => false,
            'attempts' => $attempts,
            'error' => 'Connection timeout after ' . $maxAttempts . ' attempts'
        ];
    }
    
    /**
     * Get QR code for instance connection
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data with QR code
     */
    public function getQRCode($instance = null) {
        return $this->instanceConnect($instance);
    }
    
    /**
     * Get pairing code for instance connection
     * 
     * @param string $number Phone number (with country code)
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data with pairing code
     */
    public function getPairingCode($number, $instance = null) {
        return $this->instanceConnect($instance, $number);
    }
}