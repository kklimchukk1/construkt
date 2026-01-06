<?php
/**
 * Category Model
 * 
 * Handles database operations for product categories
 */

namespace Construkt\Models;

use Construkt\Config\Database;

class Category {
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
     * Get all categories
     * 
     * @param bool $activeOnly Only return active categories
     * @return array Categories data
     */
    public function getAll($activeOnly = true) {
        $query = "SELECT * FROM categories";
        
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY name";
        
        $result = $this->db->query($query);
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    /**
     * Get category by ID
     * 
     * @param int $id Category ID
     * @return array|null Category data or null if not found
     */
    public function getById($id) {
        $query = "SELECT * FROM categories WHERE id = ?";
        
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
     * Get categories with product counts
     *
     * @param bool $activeOnly Only return active categories
     * @return array Categories data with product counts
     */
    public function getAllWithProductCounts($activeOnly = true) {
        $query = "SELECT c.*, COUNT(p.id) as product_count
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                 WHERE 1=1";

        if ($activeOnly) {
            $query .= " AND c.is_active = 1";
        }

        $query .= " GROUP BY c.id
                  ORDER BY c.name";

        $result = $this->db->query($query);

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        return $categories;
    }

    /**
     * Create a new category
     *
     * @param array $data Category data
     * @return int|false Category ID on success, false on failure
     */
    public function create($data) {
        $query = "INSERT INTO categories (name, description, parent_id, is_active) VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }

        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $parent_id = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $is_active = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1;

        $stmt->bind_param("ssii", $name, $description, $parent_id, $is_active);

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }

        error_log("Failed to create category: " . $stmt->error);
        return false;
    }

    /**
     * Update a category
     *
     * @param int $id Category ID
     * @param array $data Category data
     * @return bool Success status
     */
    public function update($id, $data) {
        $setFields = [];
        $params = [];
        $types = '';

        $allowedFields = [
            'name' => 's',
            'description' => 's',
            'parent_id' => 'i',
            'is_active' => 'i'
        ];

        foreach ($allowedFields as $field => $type) {
            if (array_key_exists($field, $data)) {
                $setFields[] = "`$field` = ?";
                if ($field === 'is_active') {
                    $params[] = $data[$field] ? 1 : 0;
                } elseif ($field === 'parent_id') {
                    $params[] = !empty($data[$field]) ? (int)$data[$field] : null;
                } else {
                    $params[] = $data[$field];
                }
                $types .= $type;
            }
        }

        if (empty($setFields)) {
            return true;
        }

        $params[] = $id;
        $types .= 'i';

        $query = "UPDATE categories SET " . implode(", ", $setFields) . " WHERE id = ?";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }

        $stmt->bind_param($types, ...$params);

        return $stmt->execute();
    }

    /**
     * Delete a category
     *
     * @param int $id Category ID
     * @return bool Success status
     */
    public function delete($id) {
        $query = "DELETE FROM categories WHERE id = ?";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}
