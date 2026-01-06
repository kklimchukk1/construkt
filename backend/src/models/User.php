<?php
/**
 * User Model
 * 
 * Handles database operations for users
 */

namespace Construkt\Models;

use Construkt\Config\Database;

class User {
    // Database connection
    private $db;
    
    // User properties
    private $id;
    private $email;
    private $password;
    private $firstName;
    private $lastName;
    private $role;
    private $isActive;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get database connection
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function findById($id) {
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Find user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data
     * @return array|false Created user data or false on failure
     */
    public function create($userData) {
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Prepare query
        $query = "INSERT INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        
        // Set role to customer if not provided
        $role = $userData['role'] ?? 'customer';
        
        // Bind parameters
        $stmt->bind_param("sssss", 
            $userData['email'],
            $hashedPassword,
            $userData['first_name'],
            $userData['last_name'],
            $role
        );
        
        // Execute query
        if (!$stmt->execute()) {
            return false;
        }
        
        // Get inserted ID
        $id = $stmt->insert_id;
        
        // Return created user
        return $this->findById($id);
    }
    
    /**
     * Update user
     * 
     * @param int $id User ID
     * @param array $userData User data to update
     * @return array|false Updated user data or false on failure
     */
    public function update($id, $userData) {
        // Start building query
        $query = "UPDATE users SET ";
        $params = [];
        $types = "";
        
        // Add fields to update
        foreach ($userData as $key => $value) {
            // Skip password (handled separately)
            if ($key === 'password') {
                continue;
            }
            
            // Convert camelCase to snake_case for database fields
            $dbField = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            
            $query .= "$dbField = ?, ";
            $params[] = $value;
            
            // Add parameter type
            if (is_int($value)) {
                $types .= "i";
            } else {
                $types .= "s";
            }
        }
        
        // Remove trailing comma and space
        $query = rtrim($query, ", ");
        
        // Add WHERE clause
        $query .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        // Execute the update
        $result = $stmt->execute();
        
        // If update was successful, return the updated user data
        if ($result) {
            return $this->findById($id);
        }
        
        return false;
    }
    
    /**
     * Update user password
     * 
     * @param int $id User ID
     * @param string $password New password
     * @return bool Success status
     */
    public function updatePassword($id, $password) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare query
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $hashedPassword, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get all users
     *
     * @return array Users data
     */
    public function getAll() {
        $query = "SELECT * FROM users ORDER BY id ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        return $users;
    }

    /**
     * Delete user
     *
     * @param int $id User ID
     * @return bool Success status
     */
    public function delete($id) {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
    
    /**
     * Verify password
     * 
     * @param string $password Password to verify
     * @param string $hash Hashed password
     * @return bool True if password is valid
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
