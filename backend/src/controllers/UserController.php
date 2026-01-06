<?php
/**
 * User Controller
 * 
 * Handles user authentication and registration
 */

namespace Construkt\Controllers;

use Construkt\Models\User;

class UserController {
    // User model instance
    private $userModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Register a new user
     * 
     * @param array $data Request data
     * @return array Response
     */
    public function register($data) {
        // Validate request data
        $errors = $this->validateRegistration($data);
        
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errors
            ];
        }
        
        // Check if email already exists
        $existingUser = $this->userModel->findByEmail($data['email']);
        
        if ($existingUser) {
            return [
                'status' => 'error',
                'message' => 'Registration failed',
                'errors' => [
                    'email' => 'Email already in use'
                ]
            ];
        }
        
        // Create user
        $user = $this->userModel->create([
            'email' => $data['email'],
            'password' => $data['password'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => isset($data['role']) && in_array($data['role'], ['customer', 'supplier']) ? $data['role'] : 'customer' // Use provided role or default to customer
        ]);
        
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Registration failed',
                'errors' => [
                    'general' => 'Failed to create user'
                ]
            ];
        }
        
        // Remove password from response
        unset($user['password']);
        
        // Generate token
        $token = $this->generateToken($user['id']);
        
        return [
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ];
    }
    
    /**
     * Login user
     * 
     * @param array $data Request data
     * @return array Response
     */
    public function login($data) {
        // Validate request data
        $errors = $this->validateLogin($data);
        
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errors
            ];
        }
        
        // Find user by email
        $user = $this->userModel->findByEmail($data['email']);
        
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Login failed',
                'errors' => [
                    'email' => 'Invalid email or password'
                ]
            ];
        }
        
        // Verify password
        if (!$this->userModel->verifyPassword($data['password'], $user['password'])) {
            return [
                'status' => 'error',
                'message' => 'Login failed',
                'errors' => [
                    'password' => 'Invalid email or password'
                ]
            ];
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            return [
                'status' => 'error',
                'message' => 'Login failed',
                'errors' => [
                    'account' => 'Account is inactive'
                ]
            ];
        }
        
        // Remove password from response
        unset($user['password']);
        
        // Generate token
        $token = $this->generateToken($user['id']);
        
        return [
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ];
    }
    
    /**
     * Validate registration data
     * 
     * @param array $data Registration data
     * @return array Validation errors
     */
    private function validateRegistration($data) {
        $errors = [];
        
        // Check required fields
        $requiredFields = ['email', 'password', 'first_name', 'last_name'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Validate email
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        // Validate password
        if (isset($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        return $errors;
    }
    
    /**
     * Validate login data
     * 
     * @param array $data Login data
     * @return array Validation errors
     */
    private function validateLogin($data) {
        $errors = [];
        
        // Check required fields
        $requiredFields = ['email', 'password'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        
        // Validate email
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        return $errors;
    }
    
    /**
     * Update user profile
     * 
     * @param array $data Profile data
     * @return array Response
     */
    public function updateProfile($data) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();
        
        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'status' => 'error',
                'message' => 'Unauthorized'
            ];
        }
        
        // Validate request data
        $errors = $this->validateProfileUpdate($data);
        
        if (!empty($errors)) {
            return [
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errors
            ];
        }
        
        // Update user
        $userId = $userData['user_id'];
        $updateData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? 'United States'
        ];
        
        $user = $this->userModel->update($userId, $updateData);
        
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Profile update failed',
                'errors' => [
                    'general' => 'Failed to update profile'
                ]
            ];
        }
        
        // Remove password from response
        unset($user['password']);
        
        return [
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user
            ]
        ];
    }
    
    /**
     * Validate profile update data
     * 
     * @param array $data Profile data
     * @return array Validation errors
     */
    private function validateProfileUpdate($data) {
        $errors = [];
        
        // Check required fields
        $requiredFields = ['first_name', 'last_name'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get all users (admin only)
     *
     * @return array Response
     */
    public function getAll() {
        $users = $this->userModel->getAll();

        // Remove passwords from response
        foreach ($users as &$user) {
            unset($user['password']);
        }

        return [
            'success' => true,
            'data' => $users
        ];
    }

    /**
     * Update user role (admin only)
     *
     * @param int $userId User ID
     * @param array $data Request data containing role
     * @return array Response
     */
    public function updateRole($userId, $data) {
        if (!isset($data['role']) || !in_array($data['role'], ['customer', 'supplier', 'admin'])) {
            return [
                'success' => false,
                'message' => 'Invalid role'
            ];
        }

        $success = $this->userModel->update($userId, ['role' => $data['role']]);

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Failed to update user role'
            ];
        }

        return [
            'success' => true,
            'message' => 'User role updated successfully'
        ];
    }

    /**
     * Update user status (admin only)
     *
     * @param int $userId User ID
     * @param array $data Request data containing is_active
     * @return array Response
     */
    public function updateStatus($userId, $data) {
        if (!isset($data['is_active'])) {
            return [
                'success' => false,
                'message' => 'Missing is_active field'
            ];
        }

        $success = $this->userModel->update($userId, ['is_active' => $data['is_active'] ? 1 : 0]);

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Failed to update user status'
            ];
        }

        return [
            'success' => true,
            'message' => 'User status updated successfully'
        ];
    }

    /**
     * Delete user (admin only)
     *
     * @param int $userId User ID
     * @return array Response
     */
    public function deleteUser($userId) {
        $success = $this->userModel->delete($userId);

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Failed to delete user'
            ];
        }

        return [
            'success' => true,
            'message' => 'User deleted successfully'
        ];
    }

    /**
     * Generate JWT token
     *
     * @param int $userId User ID
     * @return string JWT token
     */
    private function generateToken($userId) {
        // Get secret key from environment
        $secretKey = $_ENV['JWT_SECRET'] ?? 'default_secret_key';
        
        // Set token expiration (24 hours)
        $expirationTime = time() + 86400;
        
        // Create token payload
        $payload = [
            'user_id' => $userId,
            'exp' => $expirationTime
        ];
        
        // Encode header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        
        // Encode payload
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        
        // Create signature
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        // Create JWT token
        $jwt = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
        
        return $jwt;
    }
}
