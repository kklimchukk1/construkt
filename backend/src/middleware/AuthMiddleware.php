<?php
/**
 * Authentication Middleware
 * 
 * Verifies JWT tokens for protected routes
 */

namespace Construkt\Middleware;

class AuthMiddleware {
    /**
     * Verify authentication token
     * 
     * @param string $token JWT token
     * @return array|bool User data if token is valid, false otherwise
     */
    public static function verifyToken($token) {
        // Get secret key from environment
        $secretKey = $_ENV['JWT_SECRET'] ?? 'default_secret_key';
        
        // Split token into parts
        $tokenParts = explode('.', $token);
        
        // Check if token has three parts
        if (count($tokenParts) != 3) {
            return false;
        }
        
        // Get header and payload
        $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
        
        // Check if header and payload are valid JSON
        $headerData = json_decode($header, true);
        $payloadData = json_decode($payload, true);
        
        if (!$headerData || !$payloadData) {
            return false;
        }
        
        // Verify signature
        $signature = hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64UrlSignature !== $tokenParts[2]) {
            return false;
        }
        
        // Check if token has expired
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }
        
        return $payloadData;
    }
    
    /**
     * Extract token from request headers
     * 
     * @return string|null Token or null if not found
     */
    public static function getTokenFromRequest() {
        // Check if Authorization header exists
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        
        if (!$authHeader) {
            return null;
        }
        
        // Check if header starts with 'Bearer '
        if (strpos($authHeader, 'Bearer ') !== 0) {
            return null;
        }
        
        // Extract token
        return substr($authHeader, 7);
    }
    
    /**
     * Authenticate request
     * 
     * @return array|bool User data if authenticated, false otherwise
     */
    public static function authenticate() {
        // Get token from request
        $token = self::getTokenFromRequest();
        
        if (!$token) {
            return false;
        }
        
        // Verify token
        return self::verifyToken($token);
    }
    
    /**
     * Check if user has required role
     * 
     * @param array $userData User data
     * @param string|array $roles Required role(s)
     * @return bool True if user has required role
     */
    public static function hasRole($userData, $roles) {
        // Convert single role to array
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        // Check if user has required role
        return in_array($userData['role'], $roles);
    }
}
