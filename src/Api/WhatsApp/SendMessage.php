<?php

/**
 * Evolution API - Send Message Controller
 * 
 * Handles all message sending operations for the Evolution API
 * Based on the official Evolution API documentation
 */

class SendMessage {
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
     * POST Send Plain Text
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
    public function sendPlainText($number, $text, $delay = 0, $linkPreview = false, $mentionsEveryOne = false, $mentioned = [], $quoted = null) {
        $data = [
            'number' => $number,
            'text' => $text,
            'delay' => $delay,
            'linkPreview' => $linkPreview,
            'mentionsEveryOne' => $mentionsEveryOne
        ];
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendText/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Status
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
        
        if (!empty($statusJidList)) {
            $data['statusJidList'] = $statusJidList;
        }
        
        return $this->api->makeRequest("message/sendStatus/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Media
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
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendMedia/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send WhatsApp Audio
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
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendWhatsAppAudio/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Sticker
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
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendSticker/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Location
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
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendLocation/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Contact
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
        
        return $this->api->makeRequest("message/sendContact/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Reaction
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
        
        return $this->api->makeRequest("message/sendReaction/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Poll
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
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendPoll/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send List
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
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendList/{$this->api->getInstance()}", $data, 'POST');
    }
    
    /**
     * POST Send Buttons
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
        
        if (!empty($mentioned)) {
            $data['mentioned'] = $mentioned;
        }
        
        if ($quoted) {
            $data['quoted'] = $quoted;
        }
        
        return $this->api->makeRequest("message/sendButtons/{$this->api->getInstance()}", $data, 'POST');
    }
    
    // ======================
    // HELPER METHODS
    // ======================
    
    /**
     * Helper method to create a simple text message
     * 
     * @param string $number The recipient's phone number
     * @param string $text The message text
     * @param int $delay Delay in seconds before sending
     * @return array Response data
     */
    public function sendSimpleMessage($number, $text, $delay = 0) {
        return $this->sendPlainText($number, $text, $delay);
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
}