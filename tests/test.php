<?php

/**
 * Test Suite for Evolution API
 * 
 * This file tests different media files that can be sent to a WhatsApp number using Evolution API
 * Updated to reflect the new project architecture
 * 
 * WARNING: Sending multiple messages rapidly to the same WhatsApp number can result in:
 * - Temporary or permanent bans from WhatsApp
 * - Account suspension
 * - Rate limiting by the Evolution API server
 * 
 * Recommendation: Test with different numbers, messages, add delays between requests, or use sparingly
 */

require_once __DIR__ . '/../src/EvolutionAPI/EvolutionAPI.php';
require_once __DIR__ . '/../src/EvolutionAPI/AudioProcessor.php';
require_once __DIR__ . '/../src/EvolutionAPI/MediaHelper.php';
require_once __DIR__ . '/../src/Core/Logger.php';

$config = require_once __DIR__ . '/../app/config.php';

// You're loading config variables but then using different ones
$serverUrl = $config['evolutionAPI']['api_url'];
$apiKey = $config['evolutionAPI']['api_key'];
$testing = $config['testing']['enabled'];
$instance = $config['testing']['instance'];
$testNumber = $config['testing']['phone_number'];

// Validate required test configuration
if (empty($testNumber) || empty($apiKey) || empty($serverUrl)) {
    die("ERROR: Missing required test configuration in .env file\n");
}

if (!$testing) {
    die("ERROR: Testing is disabled. Set TEST_EVOLUTION_API_ENABLED=true in .env\n");
}

// Initialize Logger
$logger = new Logger(__DIR__ . '/../logs');

// Initialize the Evolution API
$api = new EvolutionAPI($serverUrl, $apiKey, $instance);

// Initialize MediaHelper
$mediaHelper = new MediaHelper($api, __DIR__ . '/../media/');

// ======================
// HELPER FUNCTION TO DISPLAY RESULTS
// ======================

function displayResult($description, $result, $logger = null)
{
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Test: " . $description . "\n";
    echo str_repeat("-", 60) . "\n";

    if ($result['success']) {
        echo "SUCCESS: Message sent successfully\n";
        echo "HTTP Code: " . $result['http_code'] . "\n";
        if (isset($result['data']['key']['id'])) {
            echo "Message ID: " . $result['data']['key']['id'] . "\n";
        }
        if (isset($result['data']['messageTimestamp'])) {
            echo "Timestamp: " . $result['data']['messageTimestamp'] . "\n";
        }

        // Log success
        if ($logger) {
            $logger->info("SUCCESS: $description", [
                'http_code' => $result['http_code'],
                'message_id' => $result['data']['key']['id'] ?? null
            ]);
        }
    } else {
        echo "FAILED: " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
        if (isset($result['raw_response'])) {
            echo "Raw Response: " . substr($result['raw_response'], 0, 200) . "...\n";
        }

        // Log error
        if ($logger) {
            $logger->error("FAILED: $description", [
                'error' => $result['error'] ?? 'Unknown error',
                'http_code' => $result['http_code'] ?? null,
                'raw_response' => $result['raw_response'] ?? null
            ]);
        }
    }

    echo str_repeat("=", 60) . "\n";
}

// ======================
// MAIN TEST EXECUTION
// ======================

echo "Evolution API Test Suite\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . $serverUrl . "\n";
echo "Instance: " . $instance . "\n";
echo "Test Number: " . $testNumber . "\n\n";

$logger->info('Starting Evolution API Test Suite', $config);

echo "WARNING: Multiple rapid requests can result in WhatsApp bans\n";
echo "Only audio tests are enabled to prevent rate limiting\n\n";

// ======================
// ACTIVE TESTS
// ======================

try {
    // Only test audio to prevent WhatsApp bans
    include __DIR__ . '/POST/send-plain-text.php';
} catch (Exception $e) {
    echo "Error during tests: " . $e->getMessage() . "\n";
    $logger->error('Test execution error', ['exception' => $e->getMessage()]);
}

// ======================
// UNACTIVE TESTS (Beware)
// ======================

/*
// WARNING: Multiple requests can trigger WhatsApp anti-spam protection
// 

// Test Plain Text
echo "\nTesting plain text messages...\n";
include __DIR__ . '/tests/POST/send-plain-text.php';

// Test Images
echo "\nTesting image files...\n";
include __DIR__ . '/tests/POST/send-images.php';

// Test Documents
echo "\nTesting document files...\n";
include __DIR__ . '/tests/POST/send-docs.php';

// Test Video
echo "\nTesting video files...\n";
include __DIR__ . '/tests/POST/send-video.php';
*/

// ======================
// SYSTEM INFORMATION
// ======================

echo "\nAvailable media files:\n";
echo str_repeat("-", 40) . "\n";

$mediaTypes = ['images', 'documents', 'audio', 'videos'];
$totalFiles = 0;
$totalSize = 0;

foreach ($mediaTypes as $type) {
    $dir = __DIR__ . "/../media/$type";
    if (is_dir($dir)) {
        echo "$type/\n";
        $files = scandir($dir);
        $typeFiles = 0;
        $typeSize = 0;

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = "$dir/$file";
                $fileSize = filesize($filePath);
                $fileSizeFormatted = number_format($fileSize / 1024, 2) . " KB";
                echo "  $file ($fileSizeFormatted)\n";

                $typeFiles++;
                $typeSize += $fileSize;
                $totalFiles++;
                $totalSize += $fileSize;
            }
        }

        if ($typeFiles > 0) {
            $typeSizeFormatted = number_format($typeSize / 1024, 2) . " KB";
            echo "  Total: $typeFiles files, $typeSizeFormatted\n";
        }
        echo "\n";
    } else {
        echo "Directory not found: $dir\n";
        $logger->warning("Media directory not found", ['directory' => $dir]);
    }
}

$totalSizeFormatted = number_format($totalSize / (1024 * 1024), 2) . " MB";
echo "SUMMARY: $totalFiles total files, $totalSizeFormatted total size\n";

// ======================
// SYSTEM CHECKS
// ======================

echo "\nSystem checks:\n";
echo str_repeat("-", 40) . "\n";

// Check FFmpeg
$audioProcessor = new AudioProcessor(__DIR__ . '/../temp/');
$ffmpegInfo = $audioProcessor->getFFmpegInfo();

if ($ffmpegInfo['available']) {
    echo "FFmpeg: Available (v" . $ffmpegInfo['version'] . ")\n";
} else {
    echo "FFmpeg: Not available\n";
    echo "Install instructions:\n";
    foreach ($ffmpegInfo['install_instructions'] as $os => $cmd) {
        echo "  $os: $cmd\n";
    }
}

// Check GD extension
if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    echo "GD Extension: Available\n";
    echo "Supported formats: ";
    $formats = [];
    if ($gdInfo['JPEG Support']) $formats[] = 'JPEG';
    if ($gdInfo['PNG Support']) $formats[] = 'PNG';
    if ($gdInfo['GIF Create Support']) $formats[] = 'GIF';
    if ($gdInfo['WebP Support']) $formats[] = 'WebP';
    echo implode(', ', $formats) . "\n";
} else {
    echo "GD Extension: Not available\n";
}

// Check cURL
if (extension_loaded('curl')) {
    $curlInfo = curl_version();
    echo "cURL: Available (v" . $curlInfo['version'] . ")\n";
} else {
    echo "cURL: Not available\n";
}

// Check write permissions
$checkDirs = [
    __DIR__ . '/../temp/' => 'Temp directory',
    __DIR__ . '/../logs/' => 'Logs directory',
    __DIR__ . '/../media/' => 'Media directory'
];

echo "\nDirectory permissions:\n";
foreach ($checkDirs as $dir => $name) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "$name: Writable\n";
        } else {
            echo "$name: Not writable\n";
        }
    } else {
        echo "$name: Directory not found\n";
        // Try to create it
        if (mkdir($dir, 0755, true)) {
            echo "$name: Created successfully\n";
        } else {
            echo "$name: Failed to create\n";
        }
    }
}

// ======================
// API CONNECTIVITY TEST
// ======================

echo "\nAPI connectivity test:\n";
echo str_repeat("-", 40) . "\n";

$apiInfo = $api->getInformation();
displayResult("API Information Check", $apiInfo, $logger);

// ======================
// CLEANUP AND FINISH
// ======================

echo "\nCleaning up...\n";
$cleaned = $audioProcessor->cleanupTempFiles();
echo "Cleaned up $cleaned temporary files\n";

$endTime = date('Y-m-d H:i:s');
echo "\nTest suite completed at $endTime\n";
echo "Check the logs directory for detailed logs\n";
echo "Check your WhatsApp to see the sent messages\n";

$logger->info('Test suite completed', [
    'end_time' => $endTime,
    'total_files_tested' => $totalFiles,
    'total_size' => $totalSizeFormatted,
    'temp_files_cleaned' => $cleaned
]);
