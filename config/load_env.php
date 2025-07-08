<?php
/**
 * Simple environment variable loader
 */

if (!function_exists('loadEnv')) {
    function loadEnv($path = '.env') {
        // Check if file exists
        if (!file_exists($path)) {
            return false;
        }
        
        // Read file line by line
        $handle = fopen($path, 'r');
        if (!$handle) return false;
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line) || $line[0] == '#') continue;
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present and value is not empty
                if (!empty($value)) {
                    if ((strlen($value) >= 2) && 
                        (($value[0] == '"' && $value[strlen($value) - 1] == '"') || 
                         ($value[0] == "'" && $value[strlen($value) - 1] == "'"))) {
                        $value = substr($value, 1, -1);
                    }
                }
                
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
        fclose($handle);
        
        return true;
    }
}