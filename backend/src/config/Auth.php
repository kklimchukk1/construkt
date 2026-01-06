<?php
/**
 * Auth Configuration
 * 
 * Handles authentication and authorization
 */

namespace Construkt\Config;

class Auth {
    /**
     * Get current authenticated user
     * 
     * @return array|null User data or null if not authenticated
     */
    public function getCurrentUser() {
        // Check for JWT token in Authorization header
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        
        // Validate JWT token
        try {
            // Get JWT secret from environment
            $secret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
            
            // Decode token
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return null;
            }
            
            $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
            $signature = $tokenParts[2];
            
            // Parse payload
            $data = json_decode($payload, true);
            
            // Check if token is expired
            if (isset($data['exp']) && $data['exp'] < time()) {
                return null;
            }
            
            // Get user ID from token
            $userId = $data['user_id'] ?? null;
            
            if (!$userId) {
                return null;
            }
            
            // Get user from database
            $userModel = new \Construkt\Models\User();
            $user = $userModel->findById($userId);
            
            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }
}
