<?php
/**
 * Direct database test for chat log functionality
 * 
 * This script bypasses all application logic and directly tests the database connection
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Get request data
$requestData = json_decode(file_get_contents('php://input'), true);
if (!$requestData) {
    // If no JSON data, try GET/POST parameters
    $requestData = $_REQUEST;
}

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Invalid request'
];

try {
    // Direct database connection
    $db = new mysqli('localhost', 'root', 'root', 'construkt');
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    
    // Get user ID from request or use default
    $userId = isset($requestData['user_id']) ? $requestData['user_id'] : '1';
    
    // Generate a session ID
    $sessionId = 'direct_test_' . time();
    
    // Create message content
    $userMessage = isset($requestData['message']) ? $requestData['message'] : 'Direct test user message';
    $botResponse = 'Direct test bot response';
    $intent = 'test';
    $timestamp = date('Y-m-d H:i:s');
    
    // Log the test
    error_log("Direct database test for user: {$userId}, session: {$sessionId}");
    
    // Insert user message
    $isBot = 0;
    $query = "INSERT INTO chat_logs (user_id, session_id, message, is_bot, intent, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $stmt->bind_param("sissss", $userId, $sessionId, $userMessage, $isBot, $intent, $timestamp);
    $userResult = $stmt->execute();
    
    if (!$userResult) {
        throw new Exception("User message insert failed: " . $stmt->error);
    }
    
    // Insert bot response
    $isBot = 1;
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $stmt->bind_param("sissss", $userId, $sessionId, $botResponse, $isBot, $intent, $timestamp);
    $botResult = $stmt->execute();
    
    if (!$botResult) {
        throw new Exception("Bot response insert failed: " . $stmt->error);
    }
    
    // Verify the inserts
    $verifyQuery = "SELECT * FROM chat_logs WHERE session_id = ?";
    $verifyStmt = $db->prepare($verifyQuery);
    
    if (!$verifyStmt) {
        throw new Exception("Verify prepare failed: " . $db->error);
    }
    
    $verifyStmt->bind_param("s", $sessionId);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Close database connection
    $db->close();
    
    // Return success response
    $response = [
        'status' => 'success',
        'message' => 'Direct database test completed successfully',
        'data' => [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'messages' => $messages
        ]
    ];
} catch (Exception $e) {
    // Log the error
    error_log("Direct database test error: " . $e->getMessage());
    
    // Return error response
    $response = [
        'status' => 'error',
        'message' => 'Direct database test failed: ' . $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
