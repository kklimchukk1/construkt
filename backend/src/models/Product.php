<?php
/**
 * Product Model
 * 
 * Handles database operations for products
 */

namespace Construkt\Models;

use Construkt\Config\Database;
use Construkt\Utils\PythonConnector;

class Product {
    // Database connection
    private $db;
    
    // Python connector for chatbot integration
    private $pythonConnector;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get database connection
        $this->db = Database::getInstance()->getConnection();
        
        // Initialize Python connector
        $this->pythonConnector = new PythonConnector();
    }
    
    /**
     * Get all products with optional filtering
     * 
     * @param array $filters Optional filters (category_id, search, min_price, max_price)
     * @param int $limit Maximum number of products to return
     * @param int $offset Offset for pagination
     * @return array Products data
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        // Start building the query
        $query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN suppliers s ON p.supplier_id = s.id
                 WHERE p.is_active = 1";
        
        $params = [];
        $types = '';
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['supplier_id'])) {
            $query .= " AND p.supplier_id = ?";
            $params[] = $filters['supplier_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        if (!empty($filters['min_price'])) {
            $query .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
            $types .= 'd';
        }
        
        if (!empty($filters['max_price'])) {
            $query .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
            $types .= 'd';
        }
        
        // Add ordering and limit
        $query .= " ORDER BY p.name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        // Log the query for debugging
        error_log("getAll query: " . $query);
        error_log("getAll params: " . print_r($params, true));
        
        // Execute the query
        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch all products
        $products = [];
        while ($product = $result->fetch_assoc()) {
            $products[] = $product;
        }
        
        error_log("Found " . count($products) . " products");
        return $products;
    }
    
    /**
     * Get total count of products with filters
     * 
     * @param array $filters Optional filters (category_id, search, min_price, max_price)
     * @return int Total count
     */
    public function getCount($filters = []) {
        // Start building the query
        $query = "SELECT COUNT(*) as total FROM products p WHERE p.is_active = 1";
        
        $params = [];
        $types = '';
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['supplier_id'])) {
            $query .= " AND p.supplier_id = ?";
            $params[] = $filters['supplier_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        if (!empty($filters['min_price'])) {
            $query .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
            $types .= 'd';
        }
        
        if (!empty($filters['max_price'])) {
            $query .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
            $types .= 'd';
        }
        
        // Log the query for debugging
        error_log("getCount query: " . $query);
        
        // Execute the query
        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        error_log("Total count: " . $row['total']);
        return $row['total'];
    }
    
    /**
     * Count products by supplier
     * 
     * @param int $supplierId Supplier ID
     * @param bool $activeOnly Count only active products
     * @return int Count of products
     */
    public function countBySupplier($supplierId, $activeOnly = false) {
        $query = "SELECT COUNT(*) as total FROM products WHERE supplier_id = ?";
        
        if ($activeOnly) {
            $query .= " AND is_active = 1";
        }
        
        // Log the final query and parameters
        error_log("countBySupplier query: " . $query);
        error_log("Parameter: supplierId=" . $supplierId);
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $supplierId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    /**
     * Get products by supplier ID
     * 
     * @param int $supplierId Supplier ID
     * @param int $limit Maximum number of products to return
     * @param int $offset Offset for pagination
     * @return array Products data
     */
    public function getBySupplierId($supplierId, $limit = 10, $offset = 0) {
        // Detailed debug logging
        error_log("getBySupplierId called with supplierId: " . $supplierId . ", limit: " . $limit . ", offset: " . $offset);
        
        // Query to get products with category name and supplier name
        $query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN suppliers s ON p.supplier_id = s.id
                 WHERE p.supplier_id = ?
                 ORDER BY p.name ASC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iii", $supplierId, $limit, $offset);
        error_log("SQL Query: " . $query . " with params: supplierId=" . $supplierId . ", limit=" . $limit . ", offset=" . $offset);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        $count = 0;
        while ($product = $result->fetch_assoc()) {
            $products[] = $product;
            $count++;
        }
        error_log("Found " . $count . " products for supplier ID: " . $supplierId);
        
        return $products;
    }

    /**
     * Find a product by ID
     * 
     * @param int $id Product ID
     * @return array|null Product data or null if not found
     */
    public function findById($id) {
        // Detailed debug logging
        error_log("findById called with id: " . $id);
        
        // Query to get product with category name and supplier name
        $query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN suppliers s ON p.supplier_id = s.id
                 WHERE p.id = ?
                 LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        error_log("SQL Query: " . $query . " with params: id=" . $id);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("No product found with ID: " . $id);
            return null;
        }
        
        $product = $result->fetch_assoc();
        error_log("Found product with ID: " . $id . ", name: " . $product["name"]);
        
        return $product;
    }
    
    /**
     * Get related products based on category
     * 
     * @param int $productId Product ID to find related products for
     * @param int $limit Maximum number of related products to return
     * @return array Related products
     */
    public function getRelated($productId, $limit = 4) {
        // First, get the category of the current product
        $product = $this->findById($productId);
        
        if (!$product || !isset($product['category_id'])) {
            error_log("Cannot find related products: Product not found or has no category");
            return [];
        }
        
        $categoryId = $product['category_id'];
        
        // Build the query to get products in the same category, excluding the current product
        $query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN suppliers s ON p.supplier_id = s.id
                 WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
                 ORDER BY RAND()
                 LIMIT ?";
        
        // Log the query for debugging
        error_log("getRelated called for product ID: " . $productId . " in category: " . $categoryId);
        
        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iii", $categoryId, $productId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch all related products
        $relatedProducts = [];
        while ($relatedProduct = $result->fetch_assoc()) {
            $relatedProducts[] = $relatedProduct;
        }
        
        error_log("Found " . count($relatedProducts) . " related products");
        return $relatedProducts;
    }
    
    /**
     * Delete a product by ID
     * 
     * @param int $id Product ID
     * @return bool Success or failure
     */
    public function delete($id) {
        // Detailed debug logging
        error_log("delete called with id: " . $id);
        
        // Query to delete product
        $query = "DELETE FROM products WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        
        $result = $stmt->execute();
        
        if ($result) {
            error_log("Successfully deleted product with ID: " . $id);
            
            // Update chatbot intents to remove the deleted product
            $this->updateChatbotIntents(['id' => $id], 'delete');
        } else {
            error_log("Failed to delete product with ID: " . $id . ". Error: " . $this->db->error);
        }
        
        return $result;
    }
    
    /**
     * Update a product by ID
     * 
     * @param int $id Product ID
     * @param array $data Product data to update
     * @return bool Success or failure
     */
    public function update($id, $data) {
        // Detailed debug logging
        error_log("Update method called with product ID: " . $id);
        error_log("Update data: " . print_r($data, true));
        
        // Build the SET part of the query
        $setFields = [];
        $params = [];
        $types = '';
        
        // Fields that can be updated
        $allowedFields = [
            'name' => 's',
            'description' => 's',
            'price' => 'd',
            'category_id' => 'i',
            'supplier_id' => 'i',
            'stock_quantity' => 'i',
            'is_active' => 'i',
            'unit' => 's',
            'is_featured' => 'i',
            'thumbnail' => 's',
            'dimensions' => 's', // JSON string for product dimensions
            'calculation_type' => 's'
        ];
        
        foreach ($allowedFields as $field => $type) {
            if (array_key_exists($field, $data)) {
                $setFields[] = "`$field` = ?";
                $params[] = $data[$field];
                $types .= $type;
                error_log("Adding field to update: $field = " . $data[$field] . " (type: $type)");
            }
        }
        
        // If no fields to update, return success
        if (empty($setFields)) {
            error_log("No fields to update for product ID: " . $id);
            return true;
        }
        
        // Add the ID parameter
        $params[] = $id;
        $types .= 'i';
        
        // Build the query
        $query = "UPDATE products SET " . implode(", ", $setFields) . ", updated_at = NOW() WHERE id = ?";
        
        error_log("SQL Query: " . $query);
        error_log("Parameter types: " . $types);
        error_log("Parameters: " . print_r($params, true));
        
        try {
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . $this->db->error);
                return false;
            }
            
            $stmt->bind_param($types, ...$params);
            $result = $stmt->execute();
            
            if ($result) {
                error_log("Successfully updated product with ID: " . $id . ", Affected rows: " . $stmt->affected_rows);
                
                // Get the full product data for chatbot intent update
                $productData = $this->findById($id);
                if ($productData) {
                    $this->updateChatbotIntents($productData, 'update');
                }
                
                return true;
            } else {
                error_log("Failed to update product with ID: " . $id . ". Error: " . $stmt->error);
                return false;
            }
        } catch (\mysqli_sql_exception $mse) {
            error_log("MySQLi SQL Exception during product update: " . $mse->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log("Exception during product update: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new product
     * 
     * @param array $data Product data
     * @return int|bool Product ID on success, false on failure
     */
    public function create($data) {
        // Detailed debug logging
        error_log("create called with data: " . print_r($data, true));
        
        // Required fields
        $requiredFields = ['name', 'price', 'category_id', 'supplier_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                error_log("Missing required field: " . $field);
                return false;
            }
        }

        // Set default value for description if not provided
        $data['description'] = isset($data['description']) ? $data['description'] : '';
        
        // Set default values for optional fields
        $data['stock_quantity'] = isset($data['stock_quantity']) ? $data['stock_quantity'] : 0;
        $data['is_active'] = isset($data['is_active']) ? $data['is_active'] : 1;
        $data['unit'] = isset($data['unit']) ? $data['unit'] : '';
        
        try {
            // Insert query
            $query = "INSERT INTO products (name, description, price, category_id, supplier_id, stock_quantity, is_active, unit, thumbnail, dimensions, is_featured, calculation_type, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . $this->db->error);
                return false;
            }

            // Bind parameters
            $name = $data['name'];
            $description = $data['description'];
            $price = $data['price'];
            $category_id = $data['category_id'];
            $supplier_id = $data['supplier_id'];
            $stock_quantity = $data['stock_quantity'];
            $is_active = $data['is_active'];
            $unit = $data['unit'];
            $thumbnail = isset($data['thumbnail']) ? $data['thumbnail'] : '';
            $dimensions = isset($data['dimensions']) ? $data['dimensions'] : NULL;
            $is_featured = isset($data['is_featured']) ? ($data['is_featured'] ? 1 : 0) : 0;
            $calculation_type = isset($data['calculation_type']) ? $data['calculation_type'] : 'unit';

            // Ensure dimensions is a valid JSON string if provided
            if (isset($data['dimensions']) && is_array($data['dimensions'])) {
                $dimensions = json_encode($data['dimensions']);
            }

            $stmt->bind_param("ssdiiiisssis",
                $name,
                $description,
                $price,
                $category_id,
                $supplier_id,
                $stock_quantity,
                $is_active,
                $unit,
                $thumbnail,
                $dimensions,
                $is_featured,
                $calculation_type
            );
            
            error_log("SQL Query: " . $query);
            error_log("Parameters: " . print_r($data, true));
            
            $result = $stmt->execute();
            
            if ($result) {
                $newId = $this->db->insert_id;
                error_log("Successfully created product with ID: " . $newId);
                
                // Update chatbot intents with the new product
                $productData = array_merge(['id' => $newId], $data);
                $this->updateChatbotIntents($productData, 'create');
                
                return $newId;
            } else {
                error_log("Failed to create product. Error: " . $stmt->error);
                return false;
            }
        } catch (\mysqli_sql_exception $mse) {
            error_log("MySQLi SQL Exception during product creation: " . $mse->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log("Exception during product creation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update chatbot intents when products change
     * 
     * @param array $productData Product data
     * @param string $action Action (create, update, delete)
     * @return bool Success or failure
     */
    private function updateChatbotIntents($productData, $action = 'update') {
        try {
            // Call the Python connector to update intents
            $result = $this->pythonConnector->updateProductIntents($productData, $action);
            
            if ($result) {
                error_log("Successfully updated chatbot intents for product ID: " . ($productData['id'] ?? 'new') . " with action: " . $action);
            } else {
                error_log("Failed to update chatbot intents for product ID: " . ($productData['id'] ?? 'new') . " with action: " . $action);
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("Exception updating chatbot intents: " . $e->getMessage());
            return false;
        }
    }
}
