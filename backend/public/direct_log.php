<?php
/**
 * Direct Chat Logger
 * 
 * A minimal script that directly inserts chat logs into the database
 */

// Set headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Content-Type: image/gif'); // Return a tiny image

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log file for debugging
$logFile = $logDir . '/direct_log.txt';

// Get request data from GET parameters
$requestData = $_GET;

// Log the incoming data for debugging
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Request: ' . json_encode($requestData) . "\n", FILE_APPEND);

// Process the request
if (isset($requestData['user_id']) && isset($requestData['message'])) {
    try {
        // Connect to database
        $db = new mysqli('localhost', 'root', 'root', 'construkt');
        
        if ($db->connect_error) {
            throw new Exception("Database connection failed: " . $db->connect_error);
        }
        
        // Get user ID
        $userId = $requestData['user_id'];
        
        // Generate session ID
        $sessionId = 'direct_' . time();
        
        // Get message content
        $userMessage = $requestData['message'];
        
        // Get bot response if available
        $botResponse = isset($requestData['bot_response']) ? $requestData['bot_response'] : '';
        
        // Get intent if available
        $intent = isset($requestData['intent']) ? $requestData['intent'] : 'unknown';
        
        // Current timestamp
        $timestamp = date('Y-m-d H:i:s');
        
        // Insert user message
        $isBot = 0; // User message
        $query = "INSERT INTO chat_logs (user_id, session_id, message, is_bot, intent, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        
        $stmt->bind_param("sissss", $userId, $sessionId, $userMessage, $isBot, $intent, $timestamp);
        $userResult = $stmt->execute();
        
        // Insert bot response if available
        if (!empty($botResponse)) {
            $isBot = 1; // Bot message
            $stmt = $db->prepare($query);
            $stmt->bind_param("sissss", $userId, $sessionId, $botResponse, $isBot, $intent, $timestamp);
            $stmt->execute();
        }
        
        // Close database connection
        $db->close();
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Success: Logged messages for user {$userId}\n", FILE_APPEND);
    } catch (Exception $e) {
        // Log the error
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Return a 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
