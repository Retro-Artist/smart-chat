<?php

/**
 * Evolution API - Profile Settings Controller
 * 
 * Handles all profile-related operations for the Evolution API
 * Based on the official Evolution API documentation
 */

class ProfileSettings {
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
     * POST Fetch Business Profile
     * 
     * @param string $number Phone number to fetch business profile
     * @return array Response data
     */
    public function fetchBusinessProfile($number) {
        $data = [
            'number' => $number
        ];
        
        return $this->api->makeRequest("profile/fetchBusinessProfile/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Fetch Profile
     * 
     * @param string $number Phone number to fetch profile
     * @return array Response data
     */
    public function fetchProfile($number) {
        $data = [
            'number' => $number
        ];
        
        return $this->api->makeRequest("profile/fetchProfile/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Update Profile Name
     * 
     * @param string $name New profile name
     * @return array Response data
     */
    public function updateProfileName($name) {
        $data = [
            'name' => $name
        ];
        
        return $this->api->makeRequest("profile/updateProfileName/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Update Profile Status
     * 
     * @param string $status New profile status/bio
     * @return array Response data
     */
    public function updateProfileStatus($status) {
        $data = [
            'status' => $status
        ];
        
        return $this->api->makeRequest("profile/updateProfileStatus/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Update Profile Picture
     * 
     * @param string $picture Base64 encoded image or URL
     * @return array Response data
     */
    public function updateProfilePicture($picture) {
        $data = [
            'picture' => $picture
        ];
        
        return $this->api->makeRequest("profile/updateProfilePicture/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * DEL Remove Profile Picture
     * 
     * @return array Response data
     */
    public function removeProfilePicture() {
        return $this->api->makeRequest("profile/removeProfilePicture/{$this->api->getInstance()}", null, 'DELETE');
    }
    
    /**
     * GET Fetch Privacy Settings
     * 
     * @return array Response data
     */
    public function fetchPrivacySettings() {
        return $this->api->makeRequest("profile/fetchPrivacySettings/{$this->api->getInstance()}", null, 'GET');
    }
    
    /**
     * POST Update Privacy Settings
     * 
     * @param array $privacySettings Privacy settings to update
     * @return array Response data
     */
    public function updatePrivacySettings($privacySettings) {
        return $this->api->makeRequest("profile/updatePrivacySettings/{$this->api->getInstance()}", $privacySettings, 'POST');
    }
    
    // ======================
    // HELPER METHODS
    // ======================
    
    /**
     * Get current profile information
     * 
     * @return array Response data
     */
    public function getCurrentProfile() {
        return $this->fetchProfile($this->api->getInstance());
    }
    
    /**
     * Update profile picture from file path
     * 
     * @param string $filePath Path to image file
     * @return array Response data
     */
    public function updateProfilePictureFromFile($filePath) {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File not found: ' . $filePath
            ];
        }
        
        $imageData = file_get_contents($filePath);
        $base64Image = base64_encode($imageData);
        
        return $this->updateProfilePicture($base64Image);
    }
    
    /**
     * Update profile picture from URL
     * 
     * @param string $url URL to image
     * @return array Response data
     */
    public function updateProfilePictureFromUrl($url) {
        $imageData = file_get_contents($url);
        
        if ($imageData === false) {
            return [
                'success' => false,
                'error' => 'Failed to download image from URL'
            ];
        }
        
        $base64Image = base64_encode($imageData);
        
        return $this->updateProfilePicture($base64Image);
    }
    
    /**
     * Set complete profile information
     * 
     * @param string $name Profile name
     * @param string $status Profile status/bio
     * @param string $picture Base64 image, file path, or URL (optional)
     * @return array Combined response data
     */
    public function setCompleteProfile($name, $status, $picture = null) {
        $results = [];
        
        // Update name
        $nameResult = $this->updateProfileName($name);
        $results['name'] = $nameResult;
        
        // Update status
        $statusResult = $this->updateProfileStatus($status);
        $results['status'] = $statusResult;
        
        // Update picture if provided
        if ($picture) {
            if (filter_var($picture, FILTER_VALIDATE_URL)) {
                // It's a URL
                $pictureResult = $this->updateProfilePictureFromUrl($picture);
            } elseif (file_exists($picture)) {
                // It's a file path
                $pictureResult = $this->updateProfilePictureFromFile($picture);
            } else {
                // Assume it's base64
                $pictureResult = $this->updateProfilePicture($picture);
            }
            $results['picture'] = $pictureResult;
        }
        
        // Return overall success status
        $allSuccess = true;
        foreach ($results as $result) {
            if (!$result['success']) {
                $allSuccess = false;
                break;
            }
        }
        
        return [
            'success' => $allSuccess,
            'results' => $results
        ];
    }
    
    /**
     * Update privacy settings with common options
     * 
     * @param string $readReceipts Who can see read receipts ('all', 'contacts', 'none')
     * @param string $profilePhoto Who can see profile photo ('all', 'contacts', 'none')
     * @param string $status Who can see status ('all', 'contacts', 'none')
     * @param string $online Who can see online status ('all', 'contacts', 'none')
     * @param string $lastSeen Who can see last seen ('all', 'contacts', 'none')
     * @param string $groupAdd Who can add to groups ('all', 'contacts', 'none')
     * @return array Response data
     */
    public function setPrivacyOptions($readReceipts = null, $profilePhoto = null, $status = null, $online = null, $lastSeen = null, $groupAdd = null) {
        $settings = [];
        
        if ($readReceipts !== null) $settings['readreceipts'] = $readReceipts;
        if ($profilePhoto !== null) $settings['profile'] = $profilePhoto;
        if ($status !== null) $settings['status'] = $status;
        if ($online !== null) $settings['online'] = $online;
        if ($lastSeen !== null) $settings['last'] = $lastSeen;
        if ($groupAdd !== null) $settings['groupadd'] = $groupAdd;
        
        return $this->updatePrivacySettings($settings);
    }
    
    /**
     * Set privacy to maximum (most restrictive)
     * 
     * @return array Response data
     */
    public function setMaxPrivacy() {
        return $this->setPrivacyOptions('contacts', 'contacts', 'contacts', 'contacts', 'contacts', 'contacts');
    }
    
    /**
     * Set privacy to minimum (most open)
     * 
     * @return array Response data
     */
    public function setMinPrivacy() {
        return $this->setPrivacyOptions('all', 'all', 'all', 'all', 'all', 'all');
    }
    
    /**
     * Set privacy to contacts only
     * 
     * @return array Response data
     */
    public function setContactsOnlyPrivacy() {
        return $this->setPrivacyOptions('contacts', 'contacts', 'contacts', 'contacts', 'contacts', 'contacts');
    }
    
    /**
     * Get formatted privacy settings
     * 
     * @return array Formatted privacy settings
     */
    public function getFormattedPrivacySettings() {
        $result = $this->fetchPrivacySettings();
        
        if ($result['success'] && isset($result['data'])) {
            return [
                'success' => true,
                'privacy' => [
                    'readReceipts' => $result['data']['readreceipts'] ?? 'unknown',
                    'profilePhoto' => $result['data']['profile'] ?? 'unknown',
                    'status' => $result['data']['status'] ?? 'unknown',
                    'online' => $result['data']['online'] ?? 'unknown',
                    'lastSeen' => $result['data']['last'] ?? 'unknown',
                    'groupAdd' => $result['data']['groupadd'] ?? 'unknown'
                ],
                'raw' => $result['data']
            ];
        }
        
        return $result;
    }
    
    /**
     * Check if profile picture exists for a number
     * 
     * @param string $number Phone number to check
     * @return bool True if profile picture exists
     */
    public function hasProfilePicture($number) {
        $result = $this->fetchProfile($number);
        
        if ($result['success'] && isset($result['data']['picture'])) {
            return !empty($result['data']['picture']);
        }
        
        return false;
    }
    
    /**
     * Get profile picture URL for a number
     * 
     * @param string $number Phone number
     * @return string|null Profile picture URL or null if not found
     */
    public function getProfilePictureUrl($number) {
        $result = $this->fetchProfile($number);
        
        if ($result['success'] && isset($result['data']['picture'])) {
            return $result['data']['picture'];
        }
        
        return null;
    }
    
    /**
     * Get profile status/bio for a number
     * 
     * @param string $number Phone number
     * @return string|null Profile status or null if not found
     */
    public function getProfileStatus($number) {
        $result = $this->fetchProfile($number);
        
        if ($result['success'] && isset($result['data']['status'])) {
            return $result['data']['status'];
        }
        
        return null;
    }
    
    /**
     * Get profile name for a number
     * 
     * @param string $number Phone number
     * @return string|null Profile name or null if not found
     */
    public function getProfileName($number) {
        $result = $this->fetchProfile($number);
        
        if ($result['success'] && isset($result['data']['name'])) {
            return $result['data']['name'];
        }
        
        return null;
    }
    
    /**
     * Check if a number has business profile
     * 
     * @param string $number Phone number to check
     * @return bool True if has business profile
     */
    public function hasBusinessProfile($number) {
        $result = $this->fetchBusinessProfile($number);
        
        return $result['success'] && isset($result['data']) && !empty($result['data']);
    }
    
    /**
     * Get business profile information
     * 
     * @param string $number Phone number
     * @return array|null Business profile data or null if not found
     */
    public function getBusinessProfileInfo($number) {
        $result = $this->fetchBusinessProfile($number);
        
        if ($result['success'] && isset($result['data'])) {
            return $result['data'];
        }
        
        return null;
    }
}