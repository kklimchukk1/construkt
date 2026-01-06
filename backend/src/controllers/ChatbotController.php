<?php
/**
 * Chatbot Controller
 * 
 * Handles chatbot-related API endpoints
 */

namespace Construkt\Controllers;

use Construkt\Models\ChatLog;
use Construkt\Utils\PythonConnector;
use Construkt\Utils\SessionManager;

class ChatbotController {
    // ChatLog model instance
    private $chatLogModel;
    
    // Python connector instance
    private $pythonConnector;
    
    // Session manager instance
    private $sessionManager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->chatLogModel = new ChatLog();
        $this->pythonConnector = new PythonConnector();
        $this->sessionManager = new SessionManager();
    }
    
    /**
     * Process a message from the user
     * 
     * @param array $data Request data
     * @return array Response data
     */
    public function processMessage($data) {
        // Validate required fields
        if (!isset($data['message']) || !isset($data['user_id'])) {
            return [
                'status' => 'error',
                'message' => 'Missing required fields'
            ];
        }
        
        // Get message and user ID
        $message = $data['message'];
        $userId = $data['user_id'];
        
        // Log the incoming request for debugging
        error_log("Processing message: {$message} from user: {$userId}");
        
        // Add message to session
        $this->sessionManager->addMessage($userId, $message, true);
        
        // Get conversation history
        $history = $this->sessionManager->getConversationHistory($userId);
        
        try {
            // Send message to Python chatbot
            $response = $this->pythonConnector->sendMessage($message, $userId);
            
            // Check if response is valid
            if (!$response || !isset($response['message'])) {
                $errorResponse = [
                    'status' => 'error',
                    'message' => 'Failed to get response from chatbot'
                ];
                
                // Add error message to session
                $this->sessionManager->addMessage(
                    $userId, 
                    'Sorry, I encountered an error. Please try again later.', 
                    false, 
                    ['error' => true]
                );
                
                error_log("Invalid response format from chatbot for user: {$userId}");
                return $errorResponse;
            }
            
            // Add bot response to session
            $this->sessionManager->addMessage(
                $userId, 
                $response['message'], 
                false, 
                [
                    'intent' => $response['intent'] ?? 'unknown',
                    'confidence' => $response['confidence'] ?? 0,
                    'data' => $response['data'] ?? null
                ]
            );
            
            // Log conversation to database
            $this->logConversation($userId, $message, $response['message'], $response['intent'] ?? 'unknown');
            
            // Return response
            return [
                'status' => 'success',
                'message' => $response['message'],
                'intent' => $response['intent'] ?? 'unknown',
                'confidence' => $response['confidence'] ?? 0,
                'data' => $response['data'] ?? null,
                'user_id' => $userId
            ];
        } catch (\Exception $e) {
            error_log("Exception processing message: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to process message: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get conversation history for a user
     * 
     * @param string $userId User ID
     * @param int $limit Maximum number of messages to return
     * @return array Response
     */
    public function getConversationHistory($userId, $limit = 20) {
        if (!$userId) {
            return [
                'status' => 'error',
                'message' => 'No user ID provided'
            ];
        }
        
        try {
            // Get conversation history from session (in-memory, more recent)
            $sessionHistory = $this->sessionManager->getConversationHistory($userId, $limit);
            
            // Get conversation history from database (persistent, may include older messages)
            $dbHistory = $this->chatLogModel->getByUserId($userId, $limit);
            
            // Combine histories, prioritizing session data for recent messages
            $combinedHistory = [];
            
            // If we have session history, use it as the primary source
            if (!empty($sessionHistory)) {
                $combinedHistory = $sessionHistory;
            } 
            // If session history is empty or less than the limit, supplement with database history
            else if (!empty($dbHistory)) {
                // Format database history to match session history format
                foreach ($dbHistory as $entry) {
                    $combinedHistory[] = [
                        'message' => $entry['isUser'] ? $entry['user_message'] : $entry['bot_response'],
                        'isUser' => $entry['isUser'],
                        'timestamp' => $entry['timestamp'],
                        'metadata' => [
                            'intent' => $entry['intent']
                        ]
                    ];
                }
            }
            
            // Limit the combined history to the requested limit
            if (count($combinedHistory) > $limit) {
                $combinedHistory = array_slice($combinedHistory, -$limit);
            }
            
            return [
                'status' => 'success',
                'data' => [
                    'history' => $combinedHistory
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to get conversation history: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear conversation context for a user
     * 
     * @param string $userId User ID
     * @return array Response
     */
    public function clearContext($userId) {
        if (!$userId) {
            return [
                'status' => 'error',
                'message' => 'No user ID provided'
            ];
        }
        
        try {
            // Clear context in Python chatbot
            $this->pythonConnector->clearContext($userId);
            
            // Clear session data
            $this->sessionManager->deleteSession($userId);
            
            return [
                'status' => 'success',
                'message' => 'Context cleared successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to clear context: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check the health of the chatbot service
     * 
     * @return array Response
     */
    public function checkHealth() {
        try {
            $health = $this->pythonConnector->checkHealth();
            
            return [
                'status' => 'success',
                'data' => $health
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Chatbot service is unavailable: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Log a conversation exchange to the database
     * 
     * @param string $userId User ID
     * @param string $userMessage User's message
     * @param string $botResponse Bot's response
     * @param string $intent Detected intent
     * @return bool Success or failure
     */
    private function logConversation($userId, $userMessage, $botResponse, $intent) {
        try {
            // Get the current session ID from the session manager
            $sessionData = $this->sessionManager->getSession($userId);
            $sessionId = isset($sessionData['session_id']) ? $sessionData['session_id'] : null;
            
            // If no session ID exists, create one
            if (!$sessionId) {
                $sessionId = 'session_' . time() . '_' . substr(md5($userId), 0, 8);
                $this->sessionManager->setSession($userId, ['session_id' => $sessionId]);
            }
            
            // Log the conversation attempt
            error_log("Attempting to log conversation for user: {$userId}, session: {$sessionId}");
            
            // Create the log entry
            $result = $this->chatLogModel->create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'user_message' => $userMessage,
                'bot_response' => $botResponse,
                'intent' => $intent,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                error_log("Successfully logged conversation for user: {$userId}");
            } else {
                error_log("Failed to log conversation for user: {$userId}");
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("Exception logging conversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate a new user ID
     * 
     * @return string User ID
     */
    private function generateUserId() {
        return 'user_' . time() . '_' . bin2hex(random_bytes(8));
    }
}
