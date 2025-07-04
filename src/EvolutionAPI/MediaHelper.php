<?php

/**
 * Media Handling Examples for Evolution API
 * 
 * This file demonstrates different ways to handle media files
 * for sending via the Evolution API
 */

class MediaHelper {
    private $api;
    private $mediaDirectory;
    private $audioProcessor;
    
    public function __construct($api, $mediaDirectory = 'media/') {
        $this->api = $api;
        $this->mediaDirectory = rtrim($mediaDirectory, '/') . '/';
        
        // Initialize AudioProcessor
        $this->audioProcessor = new AudioProcessor('temp/');
        
        // Create media directory if it doesn't exist
        if (!is_dir($this->mediaDirectory)) {
            mkdir($this->mediaDirectory, 0755, true);
        }
    }
    
    /**
     * Send media by filename from media directory
     */
    public function sendFromMediaDir($number, $filename, $caption = '') {
        $filePath = $this->mediaDirectory . $filename;
        return $this->sendFromFile($number, $filePath, $caption);
    }
    
    /**
     * Send media from any file path
     */
    public function sendFromFile($number, $filePath, $caption = '') {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }
        
        $fileInfo = pathinfo($filePath);
        $fileName = $fileInfo['basename'];
        $extension = strtolower($fileInfo['extension']);
        
        // Get MIME type and media type
        $mimeType = $this->getMimeType($extension);
        $mediaType = $this->getMediaType($extension);
        
        // Read and encode file
        $fileData = file_get_contents($filePath);
        $base64Data = base64_encode($fileData);
        
        return $this->api->sendMedia(
            $number,
            $mediaType,
            $mimeType,
            $base64Data,
            $caption,
            $fileName
        );
    }
    
    /**
     * Download and send media from URL
     */
    public function sendFromUrl($number, $url, $caption = '', $saveLocal = false) {
        // Download the file
        $fileData = file_get_contents($url);
        
        if ($fileData === false) {
            return ['success' => false, 'error' => 'Failed to download from URL'];
        }
        
        // Get filename and extension
        $fileName = basename(parse_url($url, PHP_URL_PATH));
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Save locally if requested
        if ($saveLocal) {
            $localPath = $this->mediaDirectory . $fileName;
            file_put_contents($localPath, $fileData);
        }
        
        // Get MIME type and media type
        $mimeType = $this->getMimeType($extension);
        $mediaType = $this->getMediaType($extension);
        
        // Convert to base64
        $base64Data = base64_encode($fileData);
        
        return $this->api->sendMedia(
            $number,
            $mediaType,
            $mimeType,
            $base64Data,
            $caption,
            $fileName
        );
    }
    
    /**
     * Send WhatsApp audio (PTT - Push to Talk) from file
     * This creates voice messages that appear as audio notes in WhatsApp
     */
    public function sendWhatsAppAudioFromFile($number, $filePath, $caption = '', $encoding = true) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }
        
        // Process audio file with AudioProcessor
        $audioResult = $this->audioProcessor->processAudioForWhatsApp($filePath);
        
        if (!$audioResult['success']) {
            return ['success' => false, 'error' => $audioResult['error']];
        }
        
        // Send processed audio to WhatsApp
        return $this->api->sendWhatsAppAudio($number, $audioResult['base64'], 0, $encoding);
    }
    
    /**
     * Send WhatsApp audio from media directory
     */
    public function sendWhatsAppAudioFromMediaDir($number, $filename, $caption = '', $encoding = true) {
        $filePath = $this->mediaDirectory . $filename;
        return $this->sendWhatsAppAudioFromFile($number, $filePath, $caption, $encoding);
    }
    
    /**
     * Send regular audio file as document (not as voice message)
     * Use this for music files or long audio that shouldn't be PTT
     */
    public function sendAudioAsDocument($number, $filePath, $caption = '') {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }
        
        $fileInfo = pathinfo($filePath);
        $fileName = $fileInfo['basename'];
        $extension = strtolower($fileInfo['extension']);
        
        // Get MIME type
        $mimeType = $this->getMimeType($extension);
        
        // Read and encode file
        $fileData = file_get_contents($filePath);
        $base64Data = base64_encode($fileData);
        
        return $this->api->sendMedia(
            $number,
            'document', // Send as document, not audio
            $mimeType,
            $base64Data,
            $caption,
            $fileName
        );
    }
    public function createTextImage($number, $text, $caption = '') {
        // Create a simple image with text using GD
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'GD extension not loaded'];
        }
        
        $width = 400;
        $height = 200;
        
        $image = imagecreate($width, $height);
        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        
        // Add text to image
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);
        
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $fontSize, $x, $y, $text, $textColor);
        
        // Capture image as base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        $base64Data = base64_encode($imageData);
        
        return $this->api->sendMedia(
            $number,
            'image',
            'image/png',
            $base64Data,
            $caption,
            'text-image.png'
        );
    }
    
    private function getMimeType($extension) {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'mp4' => 'video/mp4',
            'avi' => 'video/avi',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    private function getMediaType($extension) {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return 'image';
        } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv'])) {
            return 'video';
        } elseif (in_array($extension, ['mp3', 'wav', 'ogg', 'aac'])) {
            return 'audio';
        } else {
            return 'document';
        }
    }
}