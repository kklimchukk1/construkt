<?php
/**
 * Python Connector
 * 
 * Handles communication with the Python chatbot service
 */

namespace Construkt\Utils;

class PythonConnector {
    // Chatbot API base URL
    private $apiUrl;
    
    // Endpoint for updating product intents
    private $updateProductIntentsEndpoint = '/api/chatbot/update-product-intents';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get chatbot API URL from environment or use default
        $this->apiUrl = getenv('CHATBOT_API_URL') ?: 'http://localhost:5000';
    }
    
    /**
     * Send a message to the chatbot and get a response
     * 
     * @param string $message User message
     * @param string $userId User ID for conversation tracking
     * @return array Response data
     * @throws \Exception If request fails
     */
    public function sendMessage($message, $userId) {
        $endpoint = '/api/chatbot';
        $data = [
            'message' => $message,
            'user_id' => $userId
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        // Check if we got a direct response from the Python chatbot (no status field)
        if (isset($response['message']) && !isset($response['status'])) {
            // Convert to our standard format
            return [
                'status' => 'success',
                'message' => $response['message'],
                'data' => $response['data'] ?? null,
                'intent' => $response['intent'] ?? 'unknown',
                'confidence' => $response['confidence'] ?? 0,
                'user_id' => $response['user_id'] ?? $userId
            ];
        }
        
        // Check for error response
        if (isset($response['error'])) {
            throw new \Exception('Chatbot error: ' . $response['error']);
        }
        
        // If we get here and don't have a status field, something is wrong
        if (!isset($response['status'])) {
            throw new \Exception('Invalid response format from chatbot: ' . json_encode($response));
        }
        
        return $response;
    }
    
    /**
     * Get conversation history for a user
     * 
     * @param string $userId User ID
     * @param int $limit Maximum number of messages to return
     * @return array Conversation history
     * @throws \Exception If request fails
     */
    public function getHistory($userId, $limit = null) {
        $endpoint = '/api/chatbot/history';
        $params = ['user_id' => $userId];
        
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        
        $response = $this->makeRequest('GET', $endpoint, null, $params);
        
        if (!isset($response['status']) || $response['status'] !== 'success') {
            throw new \Exception('Invalid response from chatbot: ' . json_encode($response));
        }
        
        return $response['history'] ?? [];
    }
    
    /**
     * Clear conversation context for a user
     * 
     * @param string $userId User ID
     * @return bool Success or failure
     * @throws \Exception If request fails
     */
    public function clearContext($userId) {
        $endpoint = '/api/chatbot/context/clear';
        $data = ['user_id' => $userId];
        
        try {
            $response = $this->makeRequest('POST', $endpoint, $data);
            
            // Check for direct response format from Python chatbot
            if (isset($response['success']) && $response['success'] === true) {
                return true;
            }
            
            // Check for our standard format
            if (isset($response['status']) && $response['status'] === 'success') {
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            // Log the error but don't throw it - clearing context should be non-critical
            error_log('Error clearing context: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check the health of the chatbot service
     * 
     * @return array Health status
     * @throws \Exception If request fails
     */
    public function checkHealth() {
        $endpoint = '/api/chatbot/health';
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Make an HTTP request to the chatbot API
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $data Request data for POST requests
     * @param array $params Query parameters for GET requests
     * @return array Response data
     * @throws \Exception If request fails
     */
    
    /**
     * Update product intents in the chatbot
     * 
     * @param array $productData Product data (id, name, description, price, etc.)
     * @param string $action Action (create, update, delete)
     * @return bool Success or failure
     * @throws \Exception If request fails
     */
    public function updateProductIntents($productData, $action = 'update') {
        try {
            $data = [
                'product' => $productData,
                'action' => $action
            ];
            
            $response = $this->makeRequest('POST', $this->updateProductIntentsEndpoint, $data);
            
            // Check for direct response format from Python chatbot
            if (isset($response['success']) && $response['success'] === true) {
                return true;
            }
            
            // Check for our standard format
            if (isset($response['status']) && $response['status'] === 'success') {
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            // Log the error but don't throw it - updating intents should be non-critical
            error_log('Error updating product intents: ' . $e->getMessage());
            return false;
        }
    }
    
    private function makeRequest($method, $endpoint, $data = null, $params = null) {
        $url = rtrim($this->apiUrl, '/') . $endpoint;
        
        // Add query parameters for GET requests
        if ($method === 'GET' && $params) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init($url);
        
        $headers = ['Content-Type: application/json'];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        // Set a reasonable timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new \Exception('HTTP error: ' . $httpCode . ' - ' . $response);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $decodedResponse;
    }
}
