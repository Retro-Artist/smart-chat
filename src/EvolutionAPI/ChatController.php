<?php

/**
 * Evolution API - Chat Controller
 * 
 * Handles all chat-related operations for the Evolution API
 * Based on the official Evolution API documentation
 */

class ChatController {
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
     * POST Check is WhatsApp
     * 
     * @param array $numbers Array of phone numbers to check
     * @return array Response data
     */
    public function checkIsWhatsApp($numbers) {
        $data = [
            'numbers' => $numbers
        ];
        
        return $this->api->makeRequest("chat/whatsappNumbers/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Mark Message As Read
     * 
     * @param array $readMessages Array of message keys to mark as read
     * @return array Response data
     */
    public function markMessageAsRead($readMessages) {
        $data = [
            'readMessages' => $readMessages
        ];
        
        return $this->api->makeRequest("chat/markMessageAsRead/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Mark Message As Unread
     * 
     * @param array $unreadMessages Array of message keys to mark as unread
     * @return array Response data
     */
    public function markMessageAsUnread($unreadMessages) {
        $data = [
            'unreadMessages' => $unreadMessages
        ];
        
        return $this->api->makeRequest("chat/markMessageAsUnread/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Archive Chat
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
        
        return $this->api->makeRequest("chat/archiveChat/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * DEL Delete Message for Everyone
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
        
        return $this->api->makeRequest("chat/deleteMessageForEveryone/{$this->api->getInstance()}", $data, 'DELETE');
    }
    
    /**
     * POST Update Message
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
        
        return $this->api->makeRequest("chat/updateMessage/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Presence
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
        
        return $this->api->makeRequest("chat/sendPresence/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Update Block Status
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
        
        return $this->api->makeRequest("chat/updateBlockStatus/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Fetch Profile Picture URL
     * 
     * @param string $number Phone number
     * @return array Response data
     */
    public function fetchProfilePictureUrl($number) {
        $data = [
            'number' => $number
        ];
        
        return $this->api->makeRequest("chat/fetchProfilePictureUrl/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Get Base64
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
        
        return $this->api->makeRequest("chat/getBase64FromMediaMessage/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Find Contacts
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
        
        return $this->api->makeRequest("chat/findContacts/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Find Messages
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
        
        return $this->api->makeRequest("chat/findMessages/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Find Status Message
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
        
        return $this->api->makeRequest("chat/findStatusMessage/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Find Chats
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
        
        return $this->api->makeRequest("chat/findChats/{$this->api->getInstance()}", $data, 'POST');
    }
    
    // ======================
    // HELPER METHODS
    // ======================
    
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
    
    /**
     * Check if a number is WhatsApp user (single number)
     * 
     * @param string $number Phone number to check
     * @return array Response data
     */
    public function checkSingleWhatsApp($number) {
        return $this->checkIsWhatsApp([$number]);
    }
    
    /**
     * Block a contact
     * 
     * @param string $number Phone number to block
     * @return array Response data
     */
    public function blockContact($number) {
        return $this->updateBlockStatus($number, 'block');
    }
    
    /**
     * Unblock a contact
     * 
     * @param string $number Phone number to unblock
     * @return array Response data
     */
    public function unblockContact($number) {
        return $this->updateBlockStatus($number, 'unblock');
    }
    
    /**
     * Set typing presence
     * 
     * @param string $number Phone number
     * @param int $delay Delay in seconds
     * @return array Response data
     */
    public function setTyping($number, $delay = 0) {
        return $this->sendPresence($number, [
            'presence' => 'composing',
            'delay' => $delay
        ]);
    }
    
    /**
     * Set recording presence
     * 
     * @param string $number Phone number
     * @param int $delay Delay in seconds
     * @return array Response data
     */
    public function setRecording($number, $delay = 0) {
        return $this->sendPresence($number, [
            'presence' => 'recording',
            'delay' => $delay
        ]);
    }
    
    /**
     * Set paused presence
     * 
     * @param string $number Phone number
     * @param int $delay Delay in seconds
     * @return array Response data
     */
    public function setPaused($number, $delay = 0) {
        return $this->sendPresence($number, [
            'presence' => 'paused',
            'delay' => $delay
        ]);
    }
    
    /**
     * Archive a chat by number
     * 
     * @param string $number Phone number
     * @param bool $archive Archive status
     * @return array Response data
     */
    public function archiveChatByNumber($number, $archive = true) {
        // Create a basic last message structure
        $lastMessage = $this->createLastMessageData($number . '@s.whatsapp.net', false, '');
        
        return $this->archiveChat($number . '@s.whatsapp.net', $lastMessage, $archive);
    }
    
    /**
     * Unarchive a chat by number
     * 
     * @param string $number Phone number
     * @return array Response data
     */
    public function unarchiveChatByNumber($number) {
        return $this->archiveChatByNumber($number, false);
    }
    
    /**
     * Mark single message as read
     * 
     * @param string $remoteJid Remote JID
     * @param bool $fromMe Is from me
     * @param string $id Message ID
     * @return array Response data
     */
    public function markSingleMessageAsRead($remoteJid, $fromMe, $id) {
        $readMessage = $this->createReadMessageData($remoteJid, $fromMe, $id);
        return $this->markMessageAsRead([$readMessage]);
    }
    
    /**
     * Mark single message as unread
     * 
     * @param string $remoteJid Remote JID
     * @param bool $fromMe Is from me
     * @param string $id Message ID
     * @return array Response data
     */
    public function markSingleMessageAsUnread($remoteJid, $fromMe, $id) {
        $unreadMessage = $this->createReadMessageData($remoteJid, $fromMe, $id);
        return $this->markMessageAsUnread([$unreadMessage]);
    }
}