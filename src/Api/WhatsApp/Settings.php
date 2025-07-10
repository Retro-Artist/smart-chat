<?php

/**
 * Evolution API - Settings Controller
 * 
 * Handles all settings-related operations for the Evolution API
 * Based on the official Evolution API documentation
 */

class Settings {
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
     * POST Set Settings
     * 
     * @param array $settings Settings to update
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function setSettings($settings, $instance = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        
        // Convert camelCase to snake_case for API compatibility
        $apiSettings = [];
        foreach ($settings as $key => $value) {
            $apiKey = $this->camelToSnake($key);
            $apiSettings[$apiKey] = $value;
        }
        
        return $this->api->makeRequest("settings/set/{$instanceName}", $apiSettings, 'POST');
    }
    
    /**
     * GET Find Settings
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function findSettings($instance = null) {
        $instanceName = $instance ?: $this->api->getInstance();
        return $this->api->makeRequest("settings/find/{$instanceName}", null, 'GET');
    }
    
    // ======================
    // HELPER METHODS
    // ======================
    
    /**
     * Update specific settings with a more user-friendly interface
     * 
     * @param bool $rejectCall Reject incoming calls
     * @param string $msgCall Message for rejected calls
     * @param bool $groupsIgnore Ignore group messages
     * @param bool $alwaysOnline Always show as online
     * @param bool $readMessages Automatically read messages
     * @param bool $readStatus Read message status
     * @param bool $syncFullHistory Sync full message history
     * @param string $instance Instance name (optional)
     * @return array Response data
     */
    public function updateSettings($rejectCall = null, $msgCall = null, $groupsIgnore = null, $alwaysOnline = null, $readMessages = null, $readStatus = null, $syncFullHistory = null, $instance = null) {
        $settings = [];
        
        if ($rejectCall !== null) $settings['rejectCall'] = $rejectCall;
        if ($msgCall !== null) $settings['msgCall'] = $msgCall;
        if ($groupsIgnore !== null) $settings['groupsIgnore'] = $groupsIgnore;
        if ($alwaysOnline !== null) $settings['alwaysOnline'] = $alwaysOnline;
        if ($readMessages !== null) $settings['readMessages'] = $readMessages;
        if ($readStatus !== null) $settings['readStatus'] = $readStatus;
        if ($syncFullHistory !== null) $settings['syncFullHistory'] = $syncFullHistory;
        
        return $this->setSettings($settings, $instance);
    }
    
    /**
     * Enable/disable call rejection
     * 
     * @param bool $reject Whether to reject calls
     * @param string $message Message to send when rejecting calls
     * @param string $instance Instance name (optional)
     * @return array Response data
     */
    public function setCallSettings($reject, $message = '', $instance = null) {
        return $this->updateSettings($reject, $message, null, null, null, null, null, $instance);
    }
    
    /**
     * Enable/disable group message handling
     * 
     * @param bool $ignore Whether to ignore group messages
     * @param string $instance Instance name (optional)
     * @return array Response data
     */
    public function setGroupSettings($ignore, $instance = null) {
        return $this->updateSettings(null, null, $ignore, null, null, null, null, $instance);
    }
    
    /**
     * Set online status settings
     * 
     * @param bool $alwaysOnline Whether to always show as online
     * @param string $instance Instance name (optional)
     * @return array Response data
     */
    public function setOnlineSettings($alwaysOnline, $instance = null) {
        return $this->updateSettings(null, null, null, $alwaysOnline, null, null, null, $instance);
    }
    
    /**
     * Set message reading settings
     * 
     * @param bool $readMessages Whether to automatically read messages
     * @param bool $readStatus Whether to read message status
     * @param string $instance Instance name (optional)
     * @return array Response data
     */
    public function setReadSettings($readMessages, $readStatus, $instance = null) {
        return $this->updateSettings(null, null, null, null, $readMessages, $readStatus, null, $instance);
    }
    
    /**
     * Set history sync settings
     * 
     * @param bool $syncFullHistory Whether to sync full message history
     * @param string $instance Instance name (optional)
     * @return array Response data
     */
    public function setHistorySettings($syncFullHistory, $instance = null) {
        return $this->updateSettings(null, null, null, null, null, null, $syncFullHistory, $instance);
    }
    
    /**
     * Get current settings in a formatted way
     * 
     * @param string $instance Instance name (optional)
     * @return array Formatted settings data
     */
    public function getCurrentSettings($instance = null) {
        $result = $this->findSettings($instance);
        
        if ($result['success'] && isset($result['data'])) {
            return [
                'success' => true,
                'settings' => [
                    'rejectCall' => $result['data']['reject_call'] ?? false,
                    'msgCall' => $result['data']['msg_call'] ?? '',
                    'groupsIgnore' => $result['data']['groups_ignore'] ?? false,
                    'alwaysOnline' => $result['data']['always_online'] ?? false,
                    'readMessages' => $result['data']['read_messages'] ?? false,
                    'readStatus' => $result['data']['read_status'] ?? false,
                    'syncFullHistory' => $result['data']['sync_full_history'] ?? false
                ],
                'raw' => $result['data']
            ];
        }
        
        return $result;
    }
    
    /**
     * Reset settings to default values
     * 
     * @param string $instance Instance name (optional)
     * @return array Response data
     */
    public function resetToDefaults($instance = null) {
        $defaultSettings = [
            'rejectCall' => false,
            'msgCall' => '',
            'groupsIgnore' => false,
            'alwaysOnline' => false,
            'readMessages' => false,
            'readStatus' => false,
            'syncFullHistory' => false
        ];
        
        return $this->setSettings($defaultSettings, $instance);
    }
    
    /**
     * Convert camelCase to snake_case
     * 
     * @param string $input
     * @return string
     */
    private function camelToSnake($input) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }
}