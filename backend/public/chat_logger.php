<?php
/**
 * Chat Logger Middleware
 * 
 * This script intercepts chat messages and logs them directly to the database
 */

// Set headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request data
$requestData = json_decode(file_get_contents('php://input'), true);
if (!$requestData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Log the request with detailed information
error_log('Chat logger received request at ' . date('Y-m-d H:i:s') . ': ' . json_encode($requestData));

// Write to a log file for easier debugging
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/chat_logger_' . date('Y-m-d') . '.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Request: ' . json_encode($requestData) . "\n", FILE_APPEND);

// Extract required fields
$userId = isset($requestData['user_id']) ? $requestData['user_id'] : null;
$message = isset($requestData['message']) ? $requestData['message'] : null;
$botResponse = isset($requestData['bot_response']) ? $requestData['bot_response'] : null;

// Validate required fields
if (!$userId || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Connect to the database
    $db = new mysqli('localhost', 'root', 'root', 'construkt');
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    
    // Generate a session ID
    $sessionId = 'session_' . time() . '_' . $userId;
    $timestamp = date('Y-m-d H:i:s');
    $intent = isset($requestData['intent']) ? $requestData['intent'] : 'unknown';
    
    // Insert user message
    $userInserted = insertMessage($db, $userId, $sessionId, $message, 0, $intent, $timestamp);
    
    // Insert bot response if provided
    $botInserted = true;
    if ($botResponse) {
        $botInserted = insertMessage($db, $userId, $sessionId, $botResponse, 1, $intent, $timestamp);
    }
    
    // Close database connection
    $db->close();
    
    // Return success response
    echo json_encode([
        'success' => $userInserted && $botInserted,
        'message' => 'Chat messages logged successfully',
        'session_id' => $sessionId
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Chat logger error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Failed to log chat messages: ' . $e->getMessage()]);
}

/**
 * Insert a message into the database
 * 
 * @param mysqli $db Database connection
 * @param string $userId User ID
 * @param string $sessionId Session ID
 * @param string $message Message content
 * @param int $isBot Whether the message is from the bot (1) or user (0)
 * @param string $intent Detected intent
 * @param string $timestamp Timestamp
 * @return bool Success or failure
 */
function insertMessage($db, $userId, $sessionId, $message, $isBot, $intent, $timestamp) {
    // Prepare query
    $query = "INSERT INTO chat_logs (user_id, session_id, message, is_bot, intent, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $db->error);
        return false;
    }
    
    // Bind parameters
    $stmt->bind_param("sissss", $userId, $sessionId, $message, $isBot, $intent, $timestamp);
    
    // Execute query
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("Failed to insert message: " . $stmt->error);
    }
    
    return $success;
}
