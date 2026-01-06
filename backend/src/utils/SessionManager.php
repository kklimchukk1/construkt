<?php
/**
 * Session Manager
 * 
 * Handles chatbot session management and conversation tracking
 */

namespace Construkt\Utils;

class SessionManager {
    // Session storage directory
    private $storageDir;
    
    // Session timeout in seconds (default: 30 minutes)
    private $sessionTimeout;
    
    /**
     * Constructor
     * 
     * @param int $sessionTimeout Session timeout in seconds
     */
    public function __construct($sessionTimeout = 1800) {
        // Set storage directory
        $this->storageDir = dirname(__DIR__, 2) . '/storage/sessions';
        
        // Create storage directory if it doesn't exist
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
        
        $this->sessionTimeout = $sessionTimeout;
        
        // Clean expired sessions
        $this->cleanExpiredSessions();
    }
    
    /**
     * Get session data for a user
     * 
     * @param string $userId User ID
     * @return array Session data or empty array if not found
     */
    public function getSession($userId) {
        $sessionFile = $this->getSessionFilePath($userId);
        
        if (!file_exists($sessionFile)) {
            return [];
        }
        
        $sessionData = json_decode(file_get_contents($sessionFile), true);
        
        // Check if session has expired
        if (time() - $sessionData['last_access'] > $this->sessionTimeout) {
            $this->deleteSession($userId);
            return [];
        }
        
        // Update last access time
        $sessionData['last_access'] = time();
        file_put_contents($sessionFile, json_encode($sessionData));
        
        return $sessionData['data'] ?? [];
    }
    
    /**
     * Set session data for a user
     * 
     * @param string $userId User ID
     * @param array $data Session data
     * @return bool Success or failure
     */
    public function setSession($userId, $data) {
        $sessionFile = $this->getSessionFilePath($userId);
        
        $sessionData = [
            'user_id' => $userId,
            'data' => $data,
            'created' => time(),
            'last_access' => time()
        ];
        
        return file_put_contents($sessionFile, json_encode($sessionData)) !== false;
    }
    
    /**
     * Update session data for a user
     * 
     * @param string $userId User ID
     * @param array $data Session data to update
     * @return bool Success or failure
     */
    public function updateSession($userId, $data) {
        $sessionFile = $this->getSessionFilePath($userId);
        
        if (!file_exists($sessionFile)) {
            return $this->setSession($userId, $data);
        }
        
        $sessionData = json_decode(file_get_contents($sessionFile), true);
        
        // Update session data
        $sessionData['data'] = array_merge($sessionData['data'] ?? [], $data);
        $sessionData['last_access'] = time();
        
        return file_put_contents($sessionFile, json_encode($sessionData)) !== false;
    }
    
    /**
     * Delete session for a user
     * 
     * @param string $userId User ID
     * @return bool Success or failure
     */
    public function deleteSession($userId) {
        $sessionFile = $this->getSessionFilePath($userId);
        
        if (file_exists($sessionFile)) {
            return unlink($sessionFile);
        }
        
        return true;
    }
    
    /**
     * Add a message to the conversation history
     * 
     * @param string $userId User ID
     * @param string $message Message text
     * @param bool $isUser Whether the message is from the user
     * @param array $metadata Additional message metadata
     * @return bool Success or failure
     */
    public function addMessage($userId, $message, $isUser = true, $metadata = []) {
        $session = $this->getSession($userId);
        
        if (!isset($session['conversation_history'])) {
            $session['conversation_history'] = [];
        }
        
        // Add message to history
        $session['conversation_history'][] = [
            'message' => $message,
            'isUser' => $isUser,
            'timestamp' => date('Y-m-d H:i:s'),
            'metadata' => $metadata
        ];
        
        // Limit history size (keep last 50 messages)
        if (count($session['conversation_history']) > 50) {
            $session['conversation_history'] = array_slice($session['conversation_history'], -50);
        }
        
        return $this->updateSession($userId, [
            'conversation_history' => $session['conversation_history'],
            'last_message_time' => time()
        ]);
    }
    
    /**
     * Get conversation history for a user
     * 
     * @param string $userId User ID
     * @param int $limit Maximum number of messages to return
     * @return array Conversation history
     */
    public function getConversationHistory($userId, $limit = null) {
        $session = $this->getSession($userId);
        
        if (!isset($session['conversation_history'])) {
            return [];
        }
        
        $history = $session['conversation_history'];
        
        if ($limit && count($history) > $limit) {
            $history = array_slice($history, -$limit);
        }
        
        return $history;
    }
    
    /**
     * Clean expired sessions
     */
    private function cleanExpiredSessions() {
        $files = glob($this->storageDir . '/*.json');
        $now = time();
        
        foreach ($files as $file) {
            $sessionData = json_decode(file_get_contents($file), true);
            
            if ($now - $sessionData['last_access'] > $this->sessionTimeout) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get session file path for a user
     * 
     * @param string $userId User ID
     * @return string Session file path
     */
    private function getSessionFilePath($userId) {
        return $this->storageDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $userId) . '.json';
    }
}
