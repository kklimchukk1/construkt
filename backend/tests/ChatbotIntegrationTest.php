<?php
/**
 * Chatbot Integration Test
 * 
 * Tests the integration between PHP backend and Python chatbot
 */

// Include autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Construkt\Controllers\ChatbotController;
use Construkt\Utils\PythonConnector;
use Construkt\Utils\SessionManager;
use Construkt\Config\Database;

// Test class
class ChatbotIntegrationTest {
    // Test results
    private $results = [];
    
    // Valid user ID for testing
    private $validUserId;
    
    /**
     * Get a valid user ID from the database
     * 
     * @return string Valid user ID
     */
    private function getValidUserId() {
        if ($this->validUserId) {
            return $this->validUserId;
        }
        
        try {
            // Connect to database
            $db = Database::getInstance()->getConnection();
            
            // Get first user from database
            $query = "SELECT id FROM users LIMIT 1";
            $result = $db->query($query);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $this->validUserId = $row['id'];
                return $this->validUserId;
            }
            
            // If no users found, create a test user
            echo "No users found in database. Creating a test user...\n";
            $query = "INSERT INTO users (email, password, first_name, last_name, role) 
                     VALUES ('test@example.com', 'password', 'Test', 'User', 'customer')";
            
            if ($db->query($query)) {
                $this->validUserId = $db->insert_id;
                return $this->validUserId;
            }
            
            // If all else fails, return a default ID
            return '1';
        } catch (\Exception $e) {
            echo "Error getting valid user ID: {$e->getMessage()}\n";
            return '1';
        }
    }
    
    /**
     * Run all tests
     */
    public function run() {
        echo "Running Chatbot Integration Tests\n";
        echo "================================\n\n";
        
        // Run tests
        $this->testPythonConnector();
        $this->testSessionManagement();
        $this->testChatbotController();
        
        // Print summary
        $this->printSummary();
    }
    
    /**
     * Test Python connector
     */
    private function testPythonConnector() {
        echo "Testing Python Connector...\n";
        
        try {
            $connector = new PythonConnector();
            
            // Test health check
            echo "  - Testing health check: ";
            $response = $connector->checkHealth();
            $success = isset($response['status']) && $response['status'] === 'online';
            $this->logResult('python_connector_health', $success, $response);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test sending message
            echo "  - Testing message sending: ";
            $userId = $this->getValidUserId();
            $response = $connector->sendMessage("Hello, this is a test message", $userId);
            $success = isset($response['message']) && !empty($response['message']);
            $this->logResult('python_connector_message', $success, $response);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test context clearing
            echo "  - Testing context clearing: ";
            $userId = $this->getValidUserId();
            $response = $connector->clearContext($userId);
            $success = isset($response['status']) && $response['status'] === 'success';
            $this->logResult('python_connector_clear', $success, $response);
            echo $success ? "PASS\n" : "FAIL\n";
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $this->logResult('python_connector', false, ['error' => $e->getMessage()]);
        }
        
        echo "\n";
    }
    
    /**
     * Test session management
     */
    private function testSessionManagement() {
        echo "Testing Session Management...\n";
        
        try {
            $sessionManager = new SessionManager();
            $userId = $this->getValidUserId();
            
            // Test setting session data
            echo "  - Testing session data storage: ";
            $success = $sessionManager->setSession($userId, ['test_key' => 'test_value']);
            $this->logResult('session_set', $success, ['userId' => $userId]);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test getting session data
            echo "  - Testing session data retrieval: ";
            $data = $sessionManager->getSession($userId);
            $success = isset($data['test_key']) && $data['test_key'] === 'test_value';
            $this->logResult('session_get', $success, $data);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test adding message
            echo "  - Testing message addition: ";
            $success = $sessionManager->addMessage($userId, "Test message", true);
            $this->logResult('session_add_message', $success, ['userId' => $userId]);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test getting conversation history
            echo "  - Testing conversation history: ";
            $history = $sessionManager->getConversationHistory($userId);
            $success = !empty($history) && isset($history[0]['message']) && $history[0]['message'] === "Test message";
            $this->logResult('session_history', $success, ['history_count' => count($history)]);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test deleting session
            echo "  - Testing session deletion: ";
            $success = $sessionManager->deleteSession($userId);
            $this->logResult('session_delete', $success, ['userId' => $userId]);
            echo $success ? "PASS\n" : "FAIL\n";
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $this->logResult('session_management', false, ['error' => $e->getMessage()]);
        }
        
        echo "\n";
    }
    
    /**
     * Test chatbot controller
     */
    private function testChatbotController() {
        echo "Testing Chatbot Controller...\n";
        
        try {
            $controller = new ChatbotController();
            $userId = $this->getValidUserId();
            
            // Test processing message
            echo "  - Testing message processing: ";
            $response = $controller->processMessage([
                'message' => 'Hello, this is a test message',
                'user_id' => $userId
            ]);
            $success = isset($response['status']) && $response['status'] === 'success' && !empty($response['message']);
            $this->logResult('controller_process', $success, $response);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test getting conversation history
            echo "  - Testing conversation history: ";
            $response = $controller->getConversationHistory($userId);
            $success = isset($response['status']) && $response['status'] === 'success' && isset($response['data']['history']);
            $this->logResult('controller_history', $success, ['history_count' => count($response['data']['history'] ?? [])]);
            echo $success ? "PASS\n" : "FAIL\n";
            
            // Test clearing context
            echo "  - Testing context clearing: ";
            $response = $controller->clearContext($userId);
            $success = isset($response['status']) && $response['status'] === 'success';
            $this->logResult('controller_clear', $success, $response);
            echo $success ? "PASS\n" : "FAIL\n";
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $this->logResult('chatbot_controller', false, ['error' => $e->getMessage()]);
        }
        
        echo "\n";
    }
    
    /**
     * Log test result
     * 
     * @param string $test Test name
     * @param bool $success Whether the test passed
     * @param array $data Additional data
     */
    private function logResult($test, $success, $data = []) {
        $this->results[$test] = [
            'success' => $success,
            'data' => $data
        ];
    }
    
    /**
     * Print test summary
     */
    private function printSummary() {
        echo "Test Summary\n";
        echo "============\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->results as $test => $result) {
            echo sprintf("%-30s: %s\n", $test, $result['success'] ? 'PASS' : 'FAIL');
            
            if ($result['success']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "\n";
        echo sprintf("Passed: %d, Failed: %d, Total: %d\n", $passed, $failed, count($this->results));
        echo sprintf("Success Rate: %.2f%%\n", (count($this->results) > 0 ? ($passed / count($this->results) * 100) : 0));
    }
}

// Run tests if script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new ChatbotIntegrationTest();
    $test->run();
}
