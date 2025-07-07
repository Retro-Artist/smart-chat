<?php

/**
 * Evolution API - Unified Media Handler
 * 
 * Consolidated media processing class that handles all types of media:
 * - Images (JPG, PNG, WebP, GIF, etc.)
 * - Videos (MP4, AVI, MOV, etc.)
 * - Audio (MP3, WAV, OGG, etc.) with WhatsApp voice message optimization
 * - Documents (PDF, DOC, TXT, etc.)
 */

class MediaHandler {
    private $api;
    private $mediaDirectory;
    private $tempDirectory;
    private $maxFileSize = 16 * 1024 * 1024; // 16MB in bytes
    private $maxAudioDuration = 300; // 5 minutes in seconds
    
    /**
     * Constructor
     * 
     * @param EvolutionAPI $api The main EvolutionAPI instance
     * @param string $mediaDirectory Media files directory
     * @param string $tempDirectory Temporary processing directory
     */
    public function __construct(EvolutionAPI $api, $mediaDirectory = 'media/', $tempDirectory = 'temp/') {
        $this->api = $api;
        $this->mediaDirectory = rtrim($mediaDirectory, '/') . '/';
        $this->tempDirectory = rtrim($tempDirectory, '/') . '/';
        
        // Create directories if they don't exist
        if (!is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }
    }
    
    // ======================
    // MEDIA SENDING METHODS
    // ======================
    
    /**
     * Send media from file path
     * 
     * @param string $number The recipient's phone number
     * @param string $filePath Path to the media file
     * @param string $caption Caption for the media
     * @param int $delay Delay in seconds before sending
     * @return array Response data
     */
    public function sendFromFile($number, $filePath, $caption = '', $delay = 0) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }
        
        // Validate file
        $validation = $this->validateFile($filePath);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
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
        
        // Use SendMessage class to send the media
        $sendMessage = new SendMessage($this->api);
        return $sendMessage->sendMedia(
            $number,
            $mediaType,
            $mimeType,
            $base64Data,
            $caption,
            $fileName,
            $delay
        );
    }
    
    /**
     * Send media from media directory
     * 
     * @param string $number The recipient's phone number
     * @param string $relativePath Relative path from media directory
     * @param string $caption Caption for the media
     * @param int $delay Delay in seconds before sending
     * @return array Response data
     */
    public function sendFromMediaDir($number, $relativePath, $caption = '', $delay = 0) {
        $fullPath = $this->mediaDirectory . ltrim($relativePath, '/');
        return $this->sendFromFile($number, $fullPath, $caption, $delay);
    }
    
    /**
     * Send media from URL
     * 
     * @param string $number The recipient's phone number
     * @param string $url URL to the media file
     * @param string $caption Caption for the media
     * @param int $delay Delay in seconds before sending
     * @return array Response data
     */
    public function sendFromUrl($number, $url, $caption = '', $delay = 0) {
        // Download the file
        $fileData = file_get_contents($url);
        
        if ($fileData === false) {
            return ['success' => false, 'error' => 'Failed to download from URL'];
        }
        
        // Get filename and extension
        $fileName = basename(parse_url($url, PHP_URL_PATH));
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file size
        if (strlen($fileData) > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File too large: ' . $this->formatBytes(strlen($fileData)) . ' (max: ' . $this->formatBytes($this->maxFileSize) . ')'
            ];
        }
        
        // Get MIME type and media type
        $mimeType = $this->getMimeType($extension);
        $mediaType = $this->getMediaType($extension);
        
        // Convert to base64
        $base64Data = base64_encode($fileData);
        
        // Use SendMessage class to send the media
        $sendMessage = new SendMessage($this->api);
        return $sendMessage->sendMedia(
            $number,
            $mediaType,
            $mimeType,
            $base64Data,
            $caption,
            $fileName,
            $delay
        );
    }
    
    // ======================
    // AUDIO SPECIFIC METHODS
    // ======================
    
    /**
     * Send WhatsApp audio (voice message) from file
     * 
     * @param string $number The recipient's phone number
     * @param string $filePath Path to the audio file
     * @param int $delay Delay in seconds before sending
     * @param bool $encoding Encode audio into WhatsApp default format
     * @return array Response data
     */
    public function sendWhatsAppAudioFromFile($number, $filePath, $delay = 0, $encoding = true) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }
        
        // Process audio for WhatsApp if encoding is enabled
        if ($encoding) {
            $processResult = $this->processAudioForWhatsApp($filePath);
            if (!$processResult['success']) {
                return $processResult;
            }
            $base64Data = $processResult['base64'];
        } else {
            // Just read and encode the file
            $fileData = file_get_contents($filePath);
            $base64Data = base64_encode($fileData);
        }
        
        // Use SendMessage class to send WhatsApp audio
        $sendMessage = new SendMessage($this->api);
        return $sendMessage->sendWhatsAppAudio($number, $base64Data, $delay, $encoding);
    }
    
    /**
     * Send WhatsApp audio from media directory
     * 
     * @param string $number The recipient's phone number
     * @param string $relativePath Relative path from media directory
     * @param int $delay Delay in seconds before sending
     * @param bool $encoding Encode audio into WhatsApp default format
     * @return array Response data
     */
    public function sendWhatsAppAudioFromMediaDir($number, $relativePath, $delay = 0, $encoding = true) {
        $fullPath = $this->mediaDirectory . ltrim($relativePath, '/');
        return $this->sendWhatsAppAudioFromFile($number, $fullPath, $delay, $encoding);
    }
    
    /**
     * Send audio as document (not as voice message)
     * 
     * @param string $number The recipient's phone number
     * @param string $filePath Path to the audio file
     * @param string $caption Caption for the audio
     * @param int $delay Delay in seconds before sending
     * @return array Response data
     */
    public function sendAudioAsDocument($number, $filePath, $caption = '', $delay = 0) {
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
        
        // Use SendMessage class to send as document
        $sendMessage = new SendMessage($this->api);
        return $sendMessage->sendMedia(
            $number,
            'document', // Send as document, not audio
            $mimeType,
            $base64Data,
            $caption,
            $fileName,
            $delay
        );
    }
    
    // ======================
    // IMAGE GENERATION METHODS
    // ======================
    
    /**
     * Create and send a text image
     * 
     * @param string $number The recipient's phone number
     * @param string $text Text to put on the image
     * @param string $caption Caption for the image
     * @param int $width Image width (default: 400)
     * @param int $height Image height (default: 200)
     * @param array $backgroundColor RGB array for background color (default: white)
     * @param array $textColor RGB array for text color (default: black)
     * @return array Response data
     */
    public function createTextImage($number, $text, $caption = '', $width = 400, $height = 200, $backgroundColor = [255, 255, 255], $textColor = [0, 0, 0]) {
        // Check if GD extension is loaded
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'GD extension not loaded'];
        }
        
        $image = imagecreate($width, $height);
        $bgColor = imagecolorallocate($image, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
        $txtColor = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
        
        // Add text to image
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);
        
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $fontSize, $x, $y, $text, $txtColor);
        
        // Capture image as base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        $base64Data = base64_encode($imageData);
        
        // Use SendMessage class to send the image
        $sendMessage = new SendMessage($this->api);
        return $sendMessage->sendMedia(
            $number,
            'image',
            'image/png',
            $base64Data,
            $caption,
            'text-image.png'
        );
    }
    
    // ======================
    // AUDIO PROCESSING METHODS
    // ======================
    
    /**
     * Process audio file for WhatsApp voice messages
     * 
     * @param string $inputFile Path to input audio file
     * @return array Result with success, duration, base64, size, and error info
     */
    public function processAudioForWhatsApp($inputFile) {
        try {
            // Step 1: Validate file exists
            if (!file_exists($inputFile)) {
                return $this->errorResponse('File not found: ' . $inputFile);
            }
            
            // Step 2: Check FFmpeg availability
            if (!$this->isFFmpegAvailable()) {
                return $this->errorResponse('FFmpeg is not installed or not available in PATH');
            }
            
            // Step 3: Validate file size
            $fileSize = filesize($inputFile);
            if ($fileSize > $this->maxFileSize) {
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);
                return $this->errorResponse("File too large: {$fileSizeMB}MB (max: 16MB)");
            }
            
            // Step 4: Get audio duration
            $duration = $this->getAudioDuration($inputFile);
            if ($duration === false) {
                return $this->errorResponse('Could not determine audio duration');
            }
            
            // Step 5: Validate duration
            if ($duration > $this->maxAudioDuration) {
                $durationMin = round($duration / 60, 2);
                return $this->errorResponse("Audio too long: {$durationMin} minutes (max: 5 minutes)");
            }
            
            // Step 6: Convert to Opus format
            $opusFile = $this->convertToOpus($inputFile);
            if ($opusFile === false) {
                return $this->errorResponse('Failed to convert audio to Opus format');
            }
            
            // Step 7: Convert to base64
            $base64Data = base64_encode(file_get_contents($opusFile));
            $opusSize = filesize($opusFile);
            
            // Step 8: Cleanup temp file
            unlink($opusFile);
            
            // Step 9: Return success response
            return [
                'success' => true,
                'duration' => round($duration, 2),
                'base64' => $base64Data,
                'original_size' => $fileSize,
                'opus_size' => $opusSize,
                'format' => 'opus',
                'compression_ratio' => round((1 - $opusSize / $fileSize) * 100, 1),
                'error' => null
            ];
            
        } catch (Exception $e) {
            return $this->errorResponse('Processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get audio file information and metadata
     * 
     * @param string $inputFile Path to input file
     * @return array Audio file information
     */
    public function getAudioInfo($inputFile) {
        if (!file_exists($inputFile)) {
            return $this->errorResponse('File not found: ' . $inputFile);
        }
        
        // Get basic file info
        $fileSize = filesize($inputFile);
        $fileInfo = pathinfo($inputFile);
        
        // Get duration
        $duration = $this->getAudioDuration($inputFile);
        
        // Get detailed info using FFprobe
        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg($inputFile)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        $ffprobeData = null;
        if ($returnCode === 0) {
            $ffprobeData = json_decode(implode('', $output), true);
        }
        
        return [
            'success' => true,
            'filename' => $fileInfo['basename'],
            'extension' => $fileInfo['extension'] ?? '',
            'size' => $fileSize,
            'size_formatted' => $this->formatBytes($fileSize),
            'duration' => $duration,
            'duration_formatted' => $this->formatDuration($duration),
            'is_valid_size' => $fileSize <= $this->maxFileSize,
            'is_valid_duration' => $duration !== false && $duration <= $this->maxAudioDuration,
            'ffprobe_data' => $ffprobeData,
            'error' => null
        ];
    }
    
    // ======================
    // VALIDATION METHODS
    // ======================
    
    /**
     * Validate file for WhatsApp sending
     * 
     * @param string $filePath Path to file
     * @param int $maxSize Maximum file size in bytes (default: 16MB)
     * @return array Validation result
     */
    public function validateFile($filePath, $maxSize = null) {
        if ($maxSize === null) {
            $maxSize = $this->maxFileSize;
        }
        
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'error' => 'File not found'
            ];
        }
        
        $fileSize = filesize($filePath);
        $fileInfo = pathinfo($filePath);
        $extension = strtolower($fileInfo['extension']);
        
        if ($fileSize > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File too large. Maximum size: ' . $this->formatBytes($maxSize),
                'current_size' => $this->formatBytes($fileSize)
            ];
        }
        
        $supportedExtensions = [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', // Images
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', // Videos
            'mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma', // Audio
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z' // Documents
        ];
        
        if (!in_array($extension, $supportedExtensions)) {
            return [
                'valid' => false,
                'error' => 'Unsupported file type: ' . $extension
            ];
        }
        
        return [
            'valid' => true,
            'file_size' => $this->formatBytes($fileSize),
            'media_type' => $this->getMediaType($extension),
            'mime_type' => $this->getMimeType($extension)
        ];
    }
    
    /**
     * Validate audio file specifically
     * 
     * @param string $filePath Path to audio file
     * @return array Validation result
     */
    public function validateAudio($filePath) {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'errors' => ['File not found'],
                'requirements' => $this->getAudioRequirements()
            ];
        }
        
        $fileSize = filesize($filePath);
        $duration = $this->getAudioDuration($filePath);
        $errors = [];
        
        // Check file size
        if ($fileSize > $this->maxFileSize) {
            $errors[] = 'File too large: ' . $this->formatBytes($fileSize) . ' (max: ' . $this->formatBytes($this->maxFileSize) . ')';
        }
        
        // Check duration
        if ($duration !== false && $duration > $this->maxAudioDuration) {
            $errors[] = 'Audio too long: ' . $this->formatDuration($duration) . ' (max: ' . $this->formatDuration($this->maxAudioDuration) . ')';
        }
        
        if ($duration === false) {
            $errors[] = 'Could not determine audio duration';
        }
        
        // Check FFmpeg
        if (!$this->isFFmpegAvailable()) {
            $errors[] = 'FFmpeg is required for audio processing';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_size' => $this->formatBytes($fileSize),
            'duration' => $this->formatDuration($duration),
            'requirements' => $this->getAudioRequirements()
        ];
    }
    
    // ======================
    // UTILITY METHODS
    // ======================
    
    /**
     * Get system information and requirements
     * 
     * @return array System requirements info
     */
    public function getSystemInfo() {
        $output = [];
        $returnCode = 0;
        exec('ffmpeg -version 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            return [
                'ffmpeg_available' => false,
                'gd_available' => extension_loaded('gd'),
                'curl_available' => extension_loaded('curl'),
                'error' => 'FFmpeg not found',
                'install_instructions' => [
                    'macOS' => 'brew install ffmpeg',
                    'Ubuntu/Debian' => 'sudo apt install ffmpeg',
                    'Windows' => 'Download from https://ffmpeg.org/download.html',
                    'CentOS/RHEL' => 'sudo yum install ffmpeg (with EPEL repository)',
                    'Alpine' => 'apk add ffmpeg'
                ],
                'requirements' => $this->getAudioRequirements()
            ];
        }
        
        $version = '';
        if (!empty($output)) {
            preg_match('/ffmpeg version ([^\s]+)/', $output[0], $matches);
            $version = $matches[1] ?? 'Unknown';
        }
        
        $gdInfo = extension_loaded('gd') ? gd_info() : null;
        
        return [
            'ffmpeg_available' => true,
            'ffmpeg_version' => $version,
            'ffmpeg_info' => implode("\n", array_slice($output, 0, 5)),
            'gd_available' => extension_loaded('gd'),
            'gd_info' => $gdInfo,
            'curl_available' => extension_loaded('curl'),
            'requirements' => $this->getAudioRequirements()
        ];
    }
    
    /**
     * Get media directory listing
     * 
     * @return array Directory contents organized by type
     */
    public function getMediaListing() {
        $listing = [
            'images' => [],
            'videos' => [],
            'audio' => [],
            'documents' => [],
            'total_files' => 0,
            'total_size' => 0
        ];
        
        $mediaTypes = ['images', 'videos', 'audio', 'documents'];
        
        foreach ($mediaTypes as $type) {
            $dir = $this->mediaDirectory . $type;
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = "$dir/$file";
                        $fileSize = filesize($filePath);
                        $listing[$type][] = [
                            'name' => $file,
                            'size' => $this->formatBytes($fileSize),
                            'size_bytes' => $fileSize,
                            'path' => "$type/$file",
                            'modified' => date('Y-m-d H:i:s', filemtime($filePath))
                        ];
                        $listing['total_files']++;
                        $listing['total_size'] += $fileSize;
                    }
                }
            }
        }
        
        $listing['total_size_formatted'] = $this->formatBytes($listing['total_size']);
        
        return $listing;
    }
    
    /**
     * Cleanup temporary files
     * 
     * @return int Number of files cleaned
     */
    public function cleanup() {
        $files = glob($this->tempDirectory . 'audio_*.opus');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    // ======================
    // PRIVATE HELPER METHODS
    // ======================
    
    /**
     * Check if FFmpeg is available
     * 
     * @return bool True if FFmpeg is available
     */
    private function isFFmpegAvailable() {
        $output = [];
        $returnCode = 0;
        exec('ffmpeg -version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * Get audio duration using FFmpeg
     * 
     * @param string $inputFile Path to input file
     * @return float|false Duration in seconds or false on error
     */
    private function getAudioDuration($inputFile) {
        $command = sprintf(
            'ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($inputFile)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0 || empty($output)) {
            return false;
        }
        
        $duration = floatval($output[0]);
        return $duration > 0 ? $duration : false;
    }
    
    /**
     * Convert audio file to Opus format for WhatsApp
     * 
     * @param string $inputFile Path to input file
     * @return string|false Path to output file or false on error
     */
    private function convertToOpus($inputFile) {
        $outputFile = $this->tempDirectory . 'audio_' . uniqid() . '.opus';
        
        // FFmpeg command optimized for WhatsApp voice messages
        $command = sprintf(
            'ffmpeg -i %s -c:a libopus -b:a 64k -vbr on -compression_level 10 -frame_duration 20 -application voip %s 2>&1',
            escapeshellarg($inputFile),
            escapeshellarg($outputFile)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($outputFile)) {
            return false;
        }
        
        return $outputFile;
    }
    
    /**
     * Get MIME type from file extension
     * 
     * @param string $extension File extension
     * @return string MIME type
     */
    private function getMimeType($extension) {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'mp4' => 'video/mp4',
            'avi' => 'video/avi',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'm4a' => 'audio/mp4',
            'wma' => 'audio/x-ms-wma'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Get media type from file extension
     * 
     * @param string $extension File extension
     * @return string Media type (image, video, audio, document)
     */
    private function getMediaType($extension) {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
        $audioExtensions = ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma'];
        
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $videoExtensions)) {
            return 'video';
        } elseif (in_array($extension, $audioExtensions)) {
            return 'audio';
        } else {
            return 'document';
        }
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }
    
    /**
     * Format duration in human-readable format
     * 
     * @param float|false $seconds Duration in seconds
     * @return string Formatted duration
     */
    private function formatDuration($seconds) {
        if ($seconds === false) {
            return 'Unknown';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $remainingSeconds);
        } else {
            return sprintf('0:%02d', $remainingSeconds);
        }
    }
    
    /**
     * Get audio processing requirements
     * 
     * @return array Processing requirements
     */
    private function getAudioRequirements() {
        return [
            'max_file_size' => $this->formatBytes($this->maxFileSize),
            'max_duration' => $this->formatDuration($this->maxAudioDuration),
            'required_software' => 'FFmpeg',
            'output_format' => 'Opus',
            'supported_input_formats' => ['MP3', 'WAV', 'OGG', 'AAC', 'FLAC', 'M4A', 'WMA']
        ];
    }
    
    /**
     * Create error response
     * 
     * @param string $message Error message
     * @return array Error response
     */
    private function errorResponse($message) {
        return [
            'success' => false,
            'duration' => null,
            'base64' => null,
            'original_size' => null,
            'opus_size' => null,
            'format' => null,
            'compression_ratio' => null,
            'error' => $message
        ];
    }
    
    // ======================
    // CONFIGURATION METHODS
    // ======================
    
    /**
     * Set maximum file size
     * 
     * @param int $bytes Maximum file size in bytes
     */
    public function setMaxFileSize($bytes) {
        $this->maxFileSize = $bytes;
    }
    
    /**
     * Set maximum audio duration
     * 
     * @param int $seconds Maximum duration in seconds
     */
    public function setMaxAudioDuration($seconds) {
        $this->maxAudioDuration = $seconds;
    }
    
    /**
     * Get maximum file size
     * 
     * @return int Maximum file size in bytes
     */
    public function getMaxFileSize() {
        return $this->maxFileSize;
    }
    
    /**
     * Get maximum audio duration
     * 
     * @return int Maximum duration in seconds
     */
    public function getMaxAudioDuration() {
        return $this->maxAudioDuration;
    }
    
    /**
     * Get media directory path
     * 
     * @return string Media directory path
     */
    public function getMediaDirectory() {
        return $this->mediaDirectory;
    }
    
    /**
     * Get temp directory path
     * 
     * @return string Temp directory path
     */
    public function getTempDirectory() {
        return $this->tempDirectory;
    }
}