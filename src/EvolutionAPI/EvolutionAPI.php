<?php

/**
 * Evolution API PHP Library - Main Class
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
    public function makeRequest($endpoint, $data = null, $method = 'POST') {
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
    
    /**
     * Get server URL
     * 
     * @return string
     */
    public function getServerUrl() {
        return $this->server_url;
    }
    
    /**
     * Get API key
     * 
     * @return string
     */
    public function getApiKey() {
        return $this->api_key;
    }
    
    /**
     * Get instance name
     * 
     * @return string
     */
    public function getInstance() {
        return $this->instance;
    }
    
    /**
     * Set instance name
     * 
     * @param string $instance
     */
    public function setInstance($instance) {
        $this->instance = $instance;
    }
    
    /**
     * Get API information
     * 
     * @return array Response data
     */
    public function getInformation() {
        return $this->makeRequest("", null, 'GET');
    }
}