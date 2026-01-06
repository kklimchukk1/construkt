<?php
/**
 * Supplier Model
 * 
 * Handles database operations for suppliers
 */

namespace Construkt\Models;

use Construkt\Config\Database;

class Supplier {
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
     * Find supplier by ID
     * 
     * @param int $id Supplier ID
     * @return array|null Supplier data or null if not found
     */
    public function findById($id) {
        $query = "SELECT * FROM suppliers WHERE id = ?";
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
     * Find supplier by user ID
     * 
     * @param int $userId User ID
     * @return array|null Supplier data or null if not found
     */
    public function findByUserId($userId) {
        $query = "SELECT * FROM suppliers WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get all suppliers with optional filtering
     * 
     * @param array $filters Optional filters (name, is_verified, etc.)
     * @param int $limit Maximum number of suppliers to return
     * @param int $offset Offset for pagination
     * @return array Suppliers data
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        // Start building query
        $query = "SELECT s.*, u.email, u.first_name, u.last_name 
                 FROM suppliers s
                 JOIN users u ON s.user_id = u.id
                 WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Apply filters
        if (!empty($filters)) {
            // Filter by name
            if (isset($filters['name']) && $filters['name']) {
                $searchTerm = "%" . $filters['name'] . "%";
                $query .= " AND (s.company_name LIKE ? OR s.contact_name LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }
            
            // Filter by verification status
            if (isset($filters['is_verified']) && $filters['is_verified'] !== '') {
                $query .= " AND s.is_verified = ?";
                $params[] = (int)$filters['is_verified'];
                $types .= "i";
            }
            
            // Filter by featured status
            if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
                $query .= " AND s.is_featured = ?";
                $params[] = (int)$filters['is_featured'];
                $types .= "i";
            }
            
            // Filter by city
            if (isset($filters['city']) && $filters['city']) {
                $query .= " AND s.city = ?";
                $params[] = $filters['city'];
                $types .= "s";
            }
            
            // Filter by state
            if (isset($filters['state']) && $filters['state']) {
                $query .= " AND s.state = ?";
                $params[] = $filters['state'];
                $types .= "s";
            }
            
            // Filter by country
            if (isset($filters['country']) && $filters['country']) {
                $query .= " AND s.country = ?";
                $params[] = $filters['country'];
                $types .= "s";
            }
        }
        
        // Add ordering
        $query .= " ORDER BY s.company_name ASC";
        
        // Add limit and offset
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $suppliers = [];
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
        
        return $suppliers;
    }
    
    /**
     * Get total count of suppliers with filters
     * 
     * @param array $filters Optional filters (name, is_verified)
     * @return int Total count
     */
    public function getCount($filters = []) {
        // Start building query
        $query = "SELECT COUNT(*) as total 
                 FROM suppliers s
                 JOIN users u ON s.user_id = u.id
                 WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Apply filters
        if (!empty($filters)) {
            // Filter by name
            if (isset($filters['name']) && $filters['name']) {
                $searchTerm = "%" . $filters['name'] . "%";
                $query .= " AND (s.company_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            // Filter by verification status
            if (isset($filters['is_verified']) && $filters['is_verified'] !== '') {
                $query .= " AND s.is_verified = ?";
                $params[] = (int)$filters['is_verified'];
                $types .= "i";
            }
        }
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    /**
     * Create a new supplier
     * 
     * @param array $data Supplier data
     * @return int|false New supplier ID or false on failure
     */
    public function create($data) {
        // Match the actual database schema from full_setup.sql
        $query = "INSERT INTO suppliers (
            user_id, company_name, contact_name, contact_title, email, phone, 
            website, address, city, state, postal_code, country, description, 
            year_established, num_employees, service_regions, is_verified, is_featured
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Set default values for optional fields
        $contactName = isset($data['contact_name']) ? $data['contact_name'] : $data['company_name'];
        $contactTitle = isset($data['contact_title']) ? $data['contact_title'] : 'Owner';
        $email = isset($data['email']) ? $data['email'] : '';
        $phone = isset($data['phone']) ? $data['phone'] : '';
        $website = isset($data['website']) ? $data['website'] : '';
        $address = isset($data['address']) ? $data['address'] : '';
        $city = isset($data['city']) ? $data['city'] : '';
        $state = isset($data['state']) ? $data['state'] : '';
        $postalCode = isset($data['postal_code']) ? $data['postal_code'] : '';
        $country = isset($data['country']) ? $data['country'] : '';
        $description = isset($data['description']) ? $data['description'] : '';
        $yearEstablished = isset($data['year_established']) ? (int)$data['year_established'] : null;
        $numEmployees = isset($data['num_employees']) ? (int)$data['num_employees'] : null;
        $serviceRegions = isset($data['service_regions']) ? $data['service_regions'] : '';
        $isVerified = isset($data['is_verified']) ? (int)$data['is_verified'] : 0;
        $isFeatured = isset($data['is_featured']) ? (int)$data['is_featured'] : 0;
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            "issssssssssssiisii",
            $data['user_id'],
            $data['company_name'],
            $contactName,
            $contactTitle,
            $email,
            $phone,
            $website,
            $address,
            $city,
            $state,
            $postalCode,
            $country,
            $description,
            $yearEstablished,
            $numEmployees,
            $serviceRegions,
            $isVerified,
            $isFeatured
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing supplier
     * 
     * @param int $id Supplier ID
     * @param array $data Supplier data to update
     * @return bool Success or failure
     */
    public function update($id, $data) {
        // Start building query
        $query = "UPDATE suppliers SET ";
        $params = [];
        $types = "";
        
        // Add fields to update
        $updateFields = [];
        
        if (isset($data['company_name'])) {
            $updateFields[] = "company_name = ?";
            $params[] = $data['company_name'];
            $types .= "s";
        }
        
        if (isset($data['contact_name'])) {
            $updateFields[] = "contact_name = ?";
            $params[] = $data['contact_name'];
            $types .= "s";
        }
        
        if (isset($data['contact_title'])) {
            $updateFields[] = "contact_title = ?";
            $params[] = $data['contact_title'];
            $types .= "s";
        }
        
        if (isset($data['email'])) {
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
            $types .= "s";
        }
        
        if (isset($data['phone'])) {
            $updateFields[] = "phone = ?";
            $params[] = $data['phone'];
            $types .= "s";
        }
        
        if (isset($data['website'])) {
            $updateFields[] = "website = ?";
            $params[] = $data['website'];
            $types .= "s";
        }
        
        if (isset($data['address'])) {
            $updateFields[] = "address = ?";
            $params[] = $data['address'];
            $types .= "s";
        }
        
        if (isset($data['city'])) {
            $updateFields[] = "city = ?";
            $params[] = $data['city'];
            $types .= "s";
        }
        
        if (isset($data['state'])) {
            $updateFields[] = "state = ?";
            $params[] = $data['state'];
            $types .= "s";
        }
        
        if (isset($data['postal_code'])) {
            $updateFields[] = "postal_code = ?";
            $params[] = $data['postal_code'];
            $types .= "s";
        }
        
        if (isset($data['country'])) {
            $updateFields[] = "country = ?";
            $params[] = $data['country'];
            $types .= "s";
        }
        
        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
            $types .= "s";
        }
        
        if (isset($data['logo'])) {
            $updateFields[] = "logo = ?";
            $params[] = $data['logo'];
            $types .= "s";
        }
        
        if (isset($data['business_license'])) {
            $updateFields[] = "business_license = ?";
            $params[] = $data['business_license'];
            $types .= "s";
        }
        
        if (isset($data['tax_id'])) {
            $updateFields[] = "tax_id = ?";
            $params[] = $data['tax_id'];
            $types .= "s";
        }
        
        if (isset($data['year_established'])) {
            $updateFields[] = "year_established = ?";
            $params[] = $data['year_established'];
            $types .= "i";
        }
        
        if (isset($data['num_employees'])) {
            $updateFields[] = "num_employees = ?";
            $params[] = $data['num_employees'];
            $types .= "i";
        }
        
        if (isset($data['service_regions'])) {
            $updateFields[] = "service_regions = ?";
            $params[] = $data['service_regions'];
            $types .= "s";
        }
        
        if (isset($data['is_verified'])) {
            $updateFields[] = "is_verified = ?";
            $params[] = (int)$data['is_verified'];
            $types .= "i";
        }
        
        if (isset($data['is_featured'])) {
            $updateFields[] = "is_featured = ?";
            $params[] = (int)$data['is_featured'];
            $types .= "i";
        }
        
        if (isset($data['rating'])) {
            $updateFields[] = "rating = ?";
            $params[] = $data['rating'];
            $types .= "d";
        }
        
        // If no fields to update, return true
        if (empty($updateFields)) {
            return true;
        }
        
        // Complete the query
        $query .= implode(", ", $updateFields);
        $query .= " WHERE id = ?";
        
        // Add ID parameter
        $params[] = $id;
        $types .= "i";
        
        // Prepare and execute query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a supplier
     * 
     * @param int $id Supplier ID
     * @return bool Success or failure
     */
    public function delete($id) {
        $query = "DELETE FROM suppliers WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get products by supplier ID
     * 
     * @param int $supplierId Supplier ID
     * @param int $limit Maximum number of products to return
     * @param int $offset Offset for pagination
     * @return array Products data
     */
    public function getProducts($supplierId, $limit = 20, $offset = 0) {
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.supplier_id = ? AND p.is_active = 1
                 ORDER BY p.name ASC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iii", $supplierId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    }
    
    /**
     * Get count of products by supplier ID
     * 
     * @param int $supplierId Supplier ID
     * @return int Total count
     */
    public function getProductsCount($supplierId) {
        $query = "SELECT COUNT(*) as total 
                 FROM products 
                 WHERE supplier_id = ? AND is_active = 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $supplierId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    /**
     * Verify a supplier
     * 
     * @param int $id Supplier ID
     * @return bool Success or failure
     */
    public function verify($id) {
        $query = "UPDATE suppliers SET is_verified = 1 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
}
