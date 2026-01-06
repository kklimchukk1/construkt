<?php
/**
 * ChatLog Model
 * 
 * Handles database operations for chat logs
 */

namespace Construkt\Models;

use Construkt\Config\Database;

class ChatLog {
    // Database connection
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get database connection
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new chat log entry
     * 
     * @param array $data Chat log data
     * @return int|false New chat log ID or false on failure
     */
    public function create($data) {
        // Log the incoming data for debugging
        error_log("ChatLog::create() called with data: " . json_encode($data));
        
        try {
            // Validate required fields
            if (!isset($data['user_id'])) {
                error_log("ChatLog::create() - Missing user_id");
                return false;
            }
            
            // Ensure user_id is numeric
            $userId = is_numeric($data['user_id']) ? $data['user_id'] : preg_replace('/[^0-9]/', '', $data['user_id']);
            if (empty($userId)) {
                $userId = 1; // Fallback to default user ID
            }
            
            // Set default values for optional fields
            $intent = isset($data['intent']) ? $data['intent'] : 'unknown';
            $timestamp = isset($data['timestamp']) ? $data['timestamp'] : date('Y-m-d H:i:s');
            
            // Get session ID if provided, otherwise generate one
            $sessionId = isset($data['session_id']) ? $data['session_id'] : 'session_' . time() . '_' . $userId;
            
            // Handle different data formats
            if (isset($data['user_message']) && isset($data['bot_response'])) {
                // Format 1: Separate fields for user message and bot response
                $success = $this->insertChatMessage($userId, $sessionId, $data['user_message'], 0, $intent, $timestamp);
                if ($success) {
                    $success = $this->insertChatMessage($userId, $sessionId, $data['bot_response'], 1, $intent, $timestamp);
                }
                return $success;
            } 
            else if (isset($data['message'])) {
                // Format 2: Single message field with is_bot flag
                $isBot = isset($data['is_bot']) ? $data['is_bot'] : 0;
                return $this->insertChatMessage($userId, $sessionId, $data['message'], $isBot, $intent, $timestamp);
            } 
            else {
                error_log("ChatLog::create() - No message data provided");
                return false;
            }
        } catch (\Exception $e) {
            // Log the error and continue
            error_log("Error in ChatLog::create(): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insert a single chat message
     * 
     * @param string $userId User ID
     * @param string $sessionId Session ID
     * @param string $message Message content
     * @param int $isBot Whether the message is from the bot (1) or user (0)
     * @param string $intent Detected intent
     * @param string $timestamp Timestamp
     * @return bool Success or failure
     */
    private function insertChatMessage($userId, $sessionId, $message, $isBot, $intent, $timestamp) {
        try {
            // Direct query to insert the message
            $query = "INSERT INTO chat_logs (user_id, session_id, message, is_bot, intent, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            
            // Prepare statement
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . $this->db->error);
                return false;
            }
            
            // Bind parameters
            $stmt->bind_param("sissss", $userId, $sessionId, $message, $isBot, $intent, $timestamp);
            
            // Execute query
            $success = $stmt->execute();
            
            // Log result
            if ($success) {
                error_log("Successfully inserted message for user: {$userId}, is_bot: {$isBot}");
                return true;
            } else {
                error_log("Failed to insert message: " . $stmt->error);
                return false;
            }
        } catch (\Exception $e) {
            error_log("Exception inserting chat message: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get chat logs for a specific user
     * 
     * @param string $userId User ID
     * @param int $limit Maximum number of logs to return
     * @return array Chat logs
     */
    public function getByUserId($userId, $limit = 20) {
        // Prepare query - adjust column names to match actual database structure
        $query = "SELECT * FROM chat_logs 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?";
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Return chat logs
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            // Format the log entry for the frontend
            $logs[] = [
                'message' => $row['message'],
                'isUser' => $row['is_bot'] == 0, // 0 = user message, 1 = bot message
                'intent' => $row['intent'],
                'timestamp' => $row['created_at']
            ];
        }
        
        // Reverse the array to get chronological order
        return array_reverse($logs);
    }
    
    /**
     * Get all chat logs with pagination
     * 
     * @param int $limit Maximum number of logs to return
     * @param int $offset Offset for pagination
     * @return array Chat logs
     */
    public function getAll($limit = 20, $offset = 0) {
        // Prepare query - adjust column names to match actual database structure
        $query = "SELECT * FROM chat_logs 
                 ORDER BY created_at DESC 
                 LIMIT ? OFFSET ?";
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Return chat logs
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Get chat logs by intent
     * 
     * @param string $intent Intent to filter by
     * @param int $limit Maximum number of logs to return
     * @return array Chat logs
     */
    public function getByIntent($intent, $limit = 20) {
        // Prepare query - adjust column names to match actual database structure
        $query = "SELECT * FROM chat_logs 
                 WHERE intent = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?";
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $intent, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Return chat logs
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Delete chat logs for a specific user
     * 
     * @param string $userId User ID
     * @return bool Success or failure
     */
    public function deleteByUserId($userId) {
        // Prepare query
        $query = "DELETE FROM chat_logs WHERE user_id = ?";
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Check if a user exists in the database
     * 
     * @param string $userId User ID
     * @return bool True if user exists, false otherwise
     */
    private function checkUserExists($userId) {
        try {
            // Prepare query
            $query = "SELECT id FROM users WHERE id = ? LIMIT 1";
            
            // Prepare and execute query
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result && $result->num_rows > 0;
        } catch (\Exception $e) {
            error_log("Error checking user existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log chat message to a file when database logging fails
     * 
     * @param string $userId User ID
     * @param string $userMessage User message
     * @param string $botResponse Bot response
     * @param string $intent Detected intent
     * @param string $timestamp Timestamp
     * @return bool Success or failure
     */
    private function logToFile($userId, $userMessage, $botResponse, $intent, $timestamp) {
        try {
            // Create logs directory if it doesn't exist
            $logDir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            // Log file path
            $logFile = $logDir . '/chatbot_' . date('Y-m-d') . '.log';
            
            // Format log entry
            $logEntry = "[{$timestamp}] User ID: {$userId}, Intent: {$intent}\n";
            $logEntry .= "User: {$userMessage}\n";
            $logEntry .= "Bot: {$botResponse}\n\n";
            
            // Write to log file
            return file_put_contents($logFile, $logEntry, FILE_APPEND) !== false;
        } catch (\Exception $e) {
            error_log("Error logging to file: " . $e->getMessage());
            return false;
        }
    }
}
