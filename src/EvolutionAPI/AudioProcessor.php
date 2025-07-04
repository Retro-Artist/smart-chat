<?php

/**
 * Audio Processor for WhatsApp Voice Messages
 * 
 * This class handles audio file processing for WhatsApp voice messages:
 * - Validates file size and duration
 * - Converts audio to Opus format
 * - Returns base64 encoded data ready for WhatsApp API
 */

class AudioProcessor {
    private $maxFileSize = 16 * 1024 * 1024; // 16MB in bytes
    private $maxDuration = 300; // 5 minutes in seconds
    private $tempDir = 'temp/';
    
    public function __construct($tempDir = 'temp/') {
        $this->tempDir = rtrim($tempDir, '/') . '/';
        
        // Create temp directory if it doesn't exist
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
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
            if ($duration > $this->maxDuration) {
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
                'error' => null
            ];
            
        } catch (Exception $e) {
            return $this->errorResponse('Processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if FFmpeg is available
     */
    private function isFFmpegAvailable() {
        $output = [];
        $returnCode = 0;
        exec('ffmpeg -version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * Get audio duration using FFmpeg
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
     * Convert audio file to Opus format
     */
    private function convertToOpus($inputFile) {
        $outputFile = $this->tempDir . 'audio_' . uniqid() . '.opus';
        
        // FFmpeg command to convert to Opus
        // -c:a libopus: Use Opus codec
        // -b:a 64k: Set bitrate to 64kbps (good for voice)
        // -vbr on: Enable variable bitrate
        // -compression_level 10: Maximum compression
        // -frame_duration 20: 20ms frame duration (WhatsApp standard)
        // -application voip: Optimize for voice
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
     * Get audio file information
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
            'size_mb' => round($fileSize / (1024 * 1024), 2),
            'duration' => $duration,
            'duration_formatted' => $this->formatDuration($duration),
            'is_valid_size' => $fileSize <= $this->maxFileSize,
            'is_valid_duration' => $duration <= $this->maxDuration,
            'ffprobe_data' => $ffprobeData,
            'error' => null
        ];
    }
    
    /**
     * Format duration in human-readable format
     */
    private function formatDuration($seconds) {
        if ($seconds === false) {
            return 'Unknown';
        }
        
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        } else {
            return sprintf('0:%02d', $seconds);
        }
    }
    
    /**
     * Create error response
     */
    private function errorResponse($message) {
        return [
            'success' => false,
            'duration' => null,
            'base64' => null,
            'original_size' => null,
            'opus_size' => null,
            'format' => null,
            'error' => $message
        ];
    }
    
    /**
     * Set maximum file size (in bytes)
     */
    public function setMaxFileSize($bytes) {
        $this->maxFileSize = $bytes;
    }
    
    /**
     * Set maximum duration (in seconds)
     */
    public function setMaxDuration($seconds) {
        $this->maxDuration = $seconds;
    }
    
    /**
     * Get system FFmpeg info
     */
    public function getFFmpegInfo() {
        $output = [];
        $returnCode = 0;
        exec('ffmpeg -version 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            return [
                'available' => false,
                'error' => 'FFmpeg not found',
                'install_instructions' => [
                    'macOS' => 'brew install ffmpeg',
                    'Ubuntu/Debian' => 'sudo apt install ffmpeg',
                    'Windows' => 'Download from https://ffmpeg.org/download.html'
                ]
            ];
        }
        
        $version = '';
        if (!empty($output)) {
            preg_match('/ffmpeg version ([^\s]+)/', $output[0], $matches);
            $version = $matches[1] ?? 'Unknown';
        }
        
        return [
            'available' => true,
            'version' => $version,
            'full_info' => implode("\n", array_slice($output, 0, 5))
        ];
    }
    
    /**
     * Cleanup temp directory
     */
    public function cleanupTempFiles() {
        $files = glob($this->tempDir . 'audio_*.opus');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}