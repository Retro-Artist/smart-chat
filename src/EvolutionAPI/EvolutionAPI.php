<?php

/**
 * Evolution API PHP Library
 * 
 * A comprehensive PHP library for interacting with the Evolution API
 * Based on the official Evolution API documentation at https://doc.evolution-api.com/v2/api-reference/
 */

class EvolutionAPI {
    private $server_url;
    private $api_key;
    private $instance;
    
    /**
     * Constructor
     * 
     * @param string $server_url The Evolution API server URL
     * @param string $api_key Your API key
     * @param string $instance The instance name
     */
    public function __construct($server_url, $api_key, $instance) {
        $this->server_url = rtrim($server_url, '/');
        $this->api_key = $api_key;
        $this->instance = $instance;
    }
    
    /**
     * Make a cURL request to the Evolution API
     * 
     * @param string $endpoint The API endpoint
     * @param array $data The data to send
     * @param string $method The HTTP method (POST, GET, etc.)
     * @return array Response data with success status
     */
    private function makeRequest($endpoint, $data = null, $method = 'POST') {
        $url = $this->server_url . '/' . ltrim($endpoint, '/');
        
        $curl = curl_init();
        
        $curl_options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "apikey: " . $this->api_key
            ],
        ];
        
        if ($method === 'POST') {
            $curl_options[CURLOPT_CUSTOMREQUEST] = "POST";
            if ($data) {
                $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'GET') {
            $curl_options[CURLOPT_CUSTOMREQUEST] = "GET";
        } elseif ($method === 'DELETE') {
            $curl_options[CURLOPT_CUSTOMREQUEST] = "DELETE";
        } elseif ($method === 'PUT') {
            $curl_options[CURLOPT_CUSTOMREQUEST] = "PUT";
            if ($data) {
                $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }
        
        curl_setopt_array($curl, $curl_options);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            return [
                'success' => false,
                'error' => "cURL Error: " . $err,
                'http_code' => null,
                'data' => null,
                'raw_response' => null
            ];
        }
        
        $decodedResponse = json_decode($response, true);
        $isSuccess = $httpCode >= 200 && $httpCode < 300;
        
        return [
            'success' => $isSuccess,
            'http_code' => $httpCode,
            'data' => $decodedResponse,
            'raw_response' => $response,
            'error' => !$isSuccess ? "HTTP Error {$httpCode}: " . ($decodedResponse['message'] ?? $decodedResponse['error'] ?? 'Unknown error') : null
        ];
    }
    
    // ======================
    // INSTANCE CONTROLLER METHODS
    // ======================
    
    /**
     * Create a new instance
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
        
        return $this->makeRequest("instance/create", $data);
    }
    
    /**
     * Fetch all instances or specific instance
     * 
     * @param string $instanceName Optional instance name to filter
     * @return array Response data
     */
    public function fetchInstances($instanceName = null) {
        $endpoint = "instance/fetchInstances";
        if ($instanceName) {
            $endpoint .= "?instanceName=" . urlencode($instanceName);
        }
        
        return $this->makeRequest($endpoint, null, 'GET');
    }
    
    /**
     * Connect an instance (get QR code or pairing code)
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @param string $number Optional phone number for pairing
     * @return array Response data with QR code or pairing code
     */
    public function instanceConnect($instance = null, $number = null) {
        $instanceName = $instance ?: $this->instance;
        $endpoint = "instance/connect/{$instanceName}";
        
        if ($number) {
            $endpoint .= "?number=" . urlencode($number);
        }
        
        return $this->makeRequest($endpoint, null, 'GET');
    }
    
    /**
     * Restart an instance
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function restartInstance($instance = null) {
        $instanceName = $instance ?: $this->instance;
        return $this->makeRequest("instance/restart/{$instanceName}", null, 'PUT');
    }
    
    /**
     * Get connection state of an instance
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data with connection state
     */
    public function getConnectionState($instance = null) {
        $instanceName = $instance ?: $this->instance;
        return $this->makeRequest("instance/connectionState/{$instanceName}", null, 'GET');
    }
    
    /**
     * Logout an instance
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function logoutInstance($instance = null) {
        $instanceName = $instance ?: $this->instance;
        return $this->makeRequest("instance/logout/{$instanceName}", null, 'DELETE');
    }
    
    /**
     * Delete an instance
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function deleteInstance($instance = null) {
        $instanceName = $instance ?: $this->instance;
        return $this->makeRequest("instance/delete/{$instanceName}", null, 'DELETE');
    }
    
    /**
     * Set presence status for an instance
     * 
     * @param string $presence Presence status (available, unavailable, composing, recording, paused)
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return array Response data
     */
    public function setInstancePresence($presence, $instance = null) {
        $instanceName = $instance ?: $this->instance;
        $data = [
            'presence' => $presence
        ];
        
        return $this->makeRequest("instance/setPresence/{$instanceName}", $data);
    }
    
    // ======================
    // INSTANCE HELPER METHODS
    // ======================
    
    /**
     * Check if the current instance is connected
     * 
     * @param string $instance Instance name (optional, uses class instance if not provided)
     * @return bool True if connected, false otherwise
     */
    public function isInstanceConnected($instance = null) {
        $connectionState = $this->getConnectionState($instance);
        
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
                foreach ($instances['data'] as $instance) {
                    if ($instance['instance']['instanceName'] === $instanceName) {
                        return ['success' => true, 'data' => $instance];
                    }
                }
                return ['success' => false, 'error' => 'Instance not found'];
            }
            return $instances;
        }
        
        return $instances;
    }
    
    /**
     * Create instance with basic configuration
     * 
     * @param string $instanceName Name of the instance
     * @param string $token Optional token for the instance
     * @param string $number Optional phone number
     * @param bool $qrcode Whether to enable QR code (default: true)
     * @return array Response data
     */
    public function createBasicInstance($instanceName, $token = null, $number = null, $qrcode = true) {
        $options = [
            'qrcode' => $qrcode,
            'integration' => 'WHATSAPP-BAILEYS'
        ];
        
        if ($token) {
            $options['token'] = $token;
        }
        
        if ($number) {
            $options['number'] = $number;
        }
        
        return $this->createInstance($instanceName, $options);
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
            $connectionState = $this->getConnectionState($instance);
            
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
    
    // ======================
    // SEND MESSAGE METHODS
    // ======================
    
    /**
     * Send a text message
     * 
     * @param string $number The recipient's phone number
     * @param string $text The message text
     * @param int $delay Delay in seconds before sending
     * @param bool $linkPreview Show link preview
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendText($number, $text, $delay = 0, $linkPreview = false, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'text' => $text,
            'delay' => $delay,
            'linkPreview' => $linkPreview,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendText/{$this->instance}", $data);
    }
    
    /**
     * Send media (image, video, audio, document)
     * 
     * @param string $number The recipient's phone number
     * @param string $mediatype Type of media (image, video, audio, document)
     * @param string $mimetype MIME type of the media
     * @param string $media Base64 encoded media or URL
     * @param string $caption Caption for the media
     * @param string $fileName File name
     * @param int $delay Delay in seconds before sending
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendMedia($number, $mediatype, $mimetype, $media, $caption = '', $fileName = '', $delay = 0, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'mediatype' => $mediatype,
            'mimetype' => $mimetype,
            'media' => $media,
            'caption' => $caption,
            'fileName' => $fileName,
            'delay' => $delay,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendMedia/{$this->instance}", $data);
    }
    
    /**
     * Send a reaction to a message
     * 
     * @param array $key Message key data
     * @param string $reaction Reaction emoji
     * @return array Response data
     */
    public function sendReaction($key, $reaction) {
        $data = [
            'key' => $key,
            'reaction' => $reaction
        ];
        
        return $this->makeRequest("message/sendReaction/{$this->instance}", $data);
    }
    
    /**
     * Send a location message
     * 
     * @param string $number The recipient's phone number
     * @param string $name Location name
     * @param string $address Location address
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param int $delay Delay in seconds before sending
     * @param bool $linkPreview Show link preview
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendLocation($number, $name, $address, $latitude, $longitude, $delay = 0, $linkPreview = false, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'name' => $name,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'delay' => $delay,
            'linkPreview' => $linkPreview,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendLocation/{$this->instance}", $data);
    }
    
    /**
     * Send a contact message
     * 
     * @param string $number The recipient's phone number
     * @param array $contact Array of contact data
     * @return array Response data
     */
    public function sendContact($number, $contact) {
        $data = [
            'number' => $number,
            'contact' => $contact
        ];
        
        return $this->makeRequest("message/sendContact/{$this->instance}", $data);
    }
    
    /**
     * Send a button message
     * 
     * @param string $number The recipient's phone number
     * @param string $title Button title
     * @param string $description Button description
     * @param string $footer Footer text
     * @param array $buttons Array of button data
     * @param int $delay Delay in seconds before sending
     * @param bool $linkPreview Show link preview
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendButtons($number, $title, $description, $footer, $buttons, $delay = 0, $linkPreview = false, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'footer' => $footer,
            'buttons' => $buttons,
            'delay' => $delay,
            'linkPreview' => $linkPreview,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendButtons/{$this->instance}", $data);
    }
    
    /**
     * Send a list message
     * 
     * @param string $number The recipient's phone number
     * @param string $title List title
     * @param string $description List description
     * @param string $buttonText Button text
     * @param string $footerText Footer text
     * @param array $sections Array of section data
     * @param int $delay Delay in seconds before sending
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendList($number, $title, $description, $buttonText, $footerText, $sections, $delay = 0, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'buttonText' => $buttonText,
            'footerText' => $footerText,
            'sections' => $sections,
            'delay' => $delay,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendList/{$this->instance}", $data);
    }
    
    /**
     * Send a status message
     * 
     * @param string $type Type of status (text, image, video, etc.)
     * @param string $content Status content
     * @param string $caption Caption for media status
     * @param string $backgroundColor Background color
     * @param int $font Font style
     * @param bool $allContacts Send to all contacts
     * @param array $statusJidList Specific contacts to send to
     * @return array Response data
     */
    public function sendStatus($type, $content, $caption = '', $backgroundColor = '', $font = 0, $allContacts = true, $statusJidList = []) {
        $data = [
            'type' => $type,
            'content' => $content,
            'caption' => $caption,
            'backgroundColor' => $backgroundColor,
            'font' => $font,
            'allContacts' => $allContacts
        ];
        
        // Only include statusJidList if it's not empty
        if (!empty($statusJidList)) {
            $data['statusJidList'] = $statusJidList;
        }
        
        return $this->makeRequest("message/sendStatus/{$this->instance}", $data);
    }
    
    /**
     * Send a WhatsApp audio message
     * 
     * @param string $number The recipient's phone number
     * @param string $audio Base64 encoded audio or URL
     * @param int $delay Delay in seconds before sending
     * @param bool $encoding Encode audio into WhatsApp default format
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendWhatsAppAudio($number, $audio, $delay = 0, $encoding = true, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'audio' => $audio,
            'delay' => $delay,
            'encoding' => $encoding,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendWhatsAppAudio/{$this->instance}", $data);
    }
    
    /**
     * Send a sticker message
     * 
     * @param string $number The recipient's phone number
     * @param string $sticker Base64 encoded sticker or URL
     * @param int $delay Delay in seconds before sending
     * @param bool $linkPreview Show link preview
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendSticker($number, $sticker, $delay = 0, $linkPreview = false, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'sticker' => $sticker,
            'delay' => $delay,
            'linkPreview' => $linkPreview,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendSticker/{$this->instance}", $data);
    }
    
    /**
     * Send a poll message
     * 
     * @param string $number The recipient's phone number
     * @param string $name Poll name/question
     * @param array $values Array of poll options
     * @param int $selectableCount Number of options that can be selected
     * @param int $delay Delay in seconds before sending
     * @param bool $mentionsEveryOne Mention everyone in group
     * @param array $mentioned Array of phone numbers to mention
     * @param array $quoted Quote message data
     * @return array Response data
     */
    public function sendPoll($number, $name, $values, $selectableCount = 1, $delay = 0, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'name' => $name,
            'values' => $values,
            'selectableCount' => $selectableCount,
            'delay' => $delay,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        // Only include mentioned if it's not empty
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->makeRequest("message/sendPoll/{$this->instance}", $data);
    }
    
    // ======================
    // CHAT CONTROLLER METHODS
    // ======================
    
    /**
     * Check if numbers are WhatsApp users
     * 
     * @param array $numbers Array of phone numbers to check
     * @return array Response data
     */
    public function checkIsWhatsApp($numbers) {
        $data = [
            'numbers' => $numbers
        ];
        
        return $this->makeRequest("chat/whatsappNumbers/{$this->instance}", $data);
    }
    
    /**
     * Mark messages as read
     * 
     * @param array $readMessages Array of message keys to mark as read
     * @return array Response data
     */
    public function markMessageAsRead($readMessages) {
        $data = [
            'readMessages' => $readMessages
        ];
        
        return $this->makeRequest("chat/markMessageAsRead/{$this->instance}", $data);
    }
    
    /**
     * Mark messages as unread
     * 
     * @param array $unreadMessages Array of message keys to mark as unread
     * @return array Response data
     */
    public function markMessageAsUnread($unreadMessages) {
        $data = [
            'unreadMessages' => $unreadMessages
        ];
        
        return $this->makeRequest("chat/markMessageAsUnread/{$this->instance}", $data);
    }
    
    /**
     * Archive a chat
     * 
     * @param string $chat Chat identifier
     * @param array $lastMessage Last message data
     * @param bool $archive Archive status (true/false)
     * @return array Response data
     */
    public function archiveChat($chat, $lastMessage, $archive = true) {
        $data = [
            'chat' => $chat,
            'lastMessage' => $lastMessage,
            'archive' => $archive
        ];
        
        return $this->makeRequest("chat/archiveChat/{$this->instance}", $data);
    }
    
    /**
     * Delete message for everyone
     * 
     * @param string $id Message ID
     * @param string $remoteJid Remote JID
     * @param bool $fromMe Is from me
     * @param string $participant Participant ID (optional)
     * @return array Response data
     */
    public function deleteMessageForEveryone($id, $remoteJid, $fromMe, $participant = null) {
        $data = [
            'id' => $id,
            'remoteJid' => $remoteJid,
            'fromMe' => $fromMe
        ];
        
        if ($participant) {
            $data['participant'] = $participant;
        }
        
        return $this->makeRequest("chat/deleteMessageForEveryone/{$this->instance}", $data, 'DELETE');
    }
    
    /**
     * Update a message
     * 
     * @param string $number Phone number
     * @param string $text New message text
     * @param array $key Message key data
     * @return array Response data
     */
    public function updateMessage($number, $text, $key) {
        $data = [
            'number' => $number,
            'text' => $text,
            'key' => $key
        ];
        
        return $this->makeRequest("chat/updateMessage/{$this->instance}", $data);
    }
    
    /**
     * Send presence status (typing, recording, etc.)
     * 
     * @param string $number The recipient's phone number
     * @param array $options Presence options
     * @return array Response data
     */
    public function sendPresence($number, $options = []) {
        $data = [
            'number' => $number,
            'options' => array_merge([
                'delay' => 0,
                'presence' => 'composing',
                'number' => $number
            ], $options)
        ];
        
        return $this->makeRequest("chat/sendPresence/{$this->instance}", $data);
    }
    
    /**
     * Update block status of a contact
     * 
     * @param string $number Phone number to block/unblock
     * @param string $status Block status (block/unblock)
     * @return array Response data
     */
    public function updateBlockStatus($number, $status) {
        $data = [
            'number' => $number,
            'status' => $status
        ];
        
        return $this->makeRequest("chat/updateBlockStatus/{$this->instance}", $data);
    }
    
    /**
     * Fetch profile picture URL
     * 
     * @param string $number Phone number
     * @return array Response data
     */
    public function fetchProfilePictureUrl($number) {
        $data = [
            'number' => $number
        ];
        
        return $this->makeRequest("chat/fetchProfilePictureUrl/{$this->instance}", $data);
    }
    
    /**
     * Get base64 from media message
     * 
     * @param array $message Message data
     * @param bool $convertToMp4 Convert to MP4 format
     * @return array Response data
     */
    public function getBase64FromMediaMessage($message, $convertToMp4 = false) {
        $data = [
            'message' => $message,
            'convertToMp4' => $convertToMp4
        ];
        
        return $this->makeRequest("chat/getBase64FromMediaMessage/{$this->instance}", $data);
    }
    
    /**
     * Find contacts
     * 
     * @param array $where Search criteria
     * @param int $limit Limit results
     * @return array Response data
     */
    public function findContacts($where = [], $limit = 50) {
        $data = [
            'where' => $where,
            'limit' => $limit
        ];
        
        return $this->makeRequest("chat/findContacts/{$this->instance}", $data);
    }
    
    /**
     * Find messages
     * 
     * @param array $where Search criteria
     * @param int $limit Limit results
     * @return array Response data
     */
    public function findMessages($where = [], $limit = 50) {
        $data = [
            'where' => $where,
            'limit' => $limit
        ];
        
        return $this->makeRequest("chat/findMessages/{$this->instance}", $data);
    }
    
    /**
     * Find status messages
     * 
     * @param array $where Search criteria
     * @param int $limit Limit results
     * @return array Response data
     */
    public function findStatusMessage($where = [], $limit = 50) {
        $data = [
            'where' => $where,
            'limit' => $limit
        ];
        
        return $this->makeRequest("chat/findStatusMessage/{$this->instance}", $data);
    }
    
    /**
     * Find chats
     * 
     * @param array $where Search criteria
     * @param int $limit Limit results
     * @return array Response data
     */
    public function findChats($where = [], $limit = 50) {
        $data = [
            'where' => $where,
            'limit' => $limit
        ];
        
        return $this->makeRequest("chat/findChats/{$this->instance}", $data);
    }
    
    // ======================
    // UTILITY METHODS
    // ======================
    
    /**
     * Get API information
     * 
     * @return array Response data
     */
    public function getInformation() {
        return $this->makeRequest("", null, 'GET');
    }
    
    /**
     * Helper method to create a simple text message
     * 
     * @param string $number The recipient's phone number
     * @param string $text The message text
     * @param int $delay Delay in seconds before sending
     * @return array Response data
     */
    public function sendSimpleMessage($number, $text, $delay = 0) {
        return $this->sendText($number, $text, $delay);
    }
    
    /**
     * Helper method to create a message key for reactions
     * 
     * @param string $remoteJid Remote JID
     * @param bool $fromMe Is from me
     * @param string $id Message ID
     * @param string $participant Participant ID (optional)
     * @return array Message key data
     */
    public function createMessageKey($remoteJid, $fromMe, $id, $participant = null) {
        $key = [
            'remoteJid' => $remoteJid,
            'fromMe' => $fromMe,
            'id' => $id
        ];
        
        if ($participant) {
            $key['participant'] = $participant;
        }
        
        return $key;
    }
    
    /**
     * Helper method to create contact data
     * 
     * @param string $fullName Full name
     * @param string $wuid WhatsApp User ID
     * @param string $phoneNumber Phone number
     * @param string $organization Organization
     * @param string $email Email
     * @param string $url URL
     * @return array Contact data
     */
    public function createContact($fullName, $wuid, $phoneNumber, $organization = '', $email = '', $url = '') {
        return [
            'fullName' => $fullName,
            'wuid' => $wuid,
            'phoneNumber' => $phoneNumber,
            'organization' => $organization,
            'email' => $email,
            'url' => $url
        ];
    }
    
    /**
     * Helper method to create button data
     * 
     * @param string $title Button title
     * @param string $displayText Display text
     * @param string $id Button ID
     * @return array Button data
     */
    public function createButton($title, $displayText, $id) {
        return [
            'title' => $title,
            'displayText' => $displayText,
            'id' => $id
        ];
    }
    
    /**
     * Helper method to create list section data
     * 
     * @param string $title Section title
     * @param array $rows Array of row data
     * @return array Section data
     */
    public function createListSection($title, $rows) {
        return [
            'title' => $title,
            'rows' => $rows
        ];
    }
    
    /**
     * Helper method to create list row data
     * 
     * @param string $title Row title
     * @param string $description Row description
     * @param string $rowId Row ID
     * @return array Row data
     */
    public function createListRow($title, $description, $rowId) {
        return [
            'title' => $title,
            'description' => $description,
            'rowId' => $rowId
        ];
    }
    
    /**
     * Helper method to create poll options
     * 
     * @param array $options Array of option strings
     * @return array Poll options array
     */
    public function createPollOptions($options) {
        return array_map(function($option) {
            return ['optionName' => $option];
        }, $options);
    }
    
    /**
     * Helper method to create read message data
     * 
     * @param string $remoteJid Remote JID
     * @param bool $fromMe Is from me
     * @param string $id Message ID
     * @return array Read message data
     */
    public function createReadMessageData($remoteJid, $fromMe, $id) {
        return [
            'remoteJid' => $remoteJid,
            'fromMe' => $fromMe,
            'id' => $id
        ];
    }
    
    /**
     * Helper method to create last message data for archiving
     * 
     * @param string $remoteJid Remote JID
     * @param bool $fromMe Is from me
     * @param string $id Message ID
     * @return array Last message data
     */
    public function createLastMessageData($remoteJid, $fromMe, $id) {
        return [
            'key' => [
                'remoteJid' => $remoteJid,
                'fromMe' => $fromMe,
                'id' => $id
            ]
        ];
    }
    
    /**
     * Helper method to create search criteria
     * 
     * @param array $criteria Search criteria
     * @return array Formatted search criteria
     */
    public function createSearchCriteria($criteria) {
        return $criteria;
    }
}