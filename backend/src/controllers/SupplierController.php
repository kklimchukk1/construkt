<?php
/**
 * Supplier Controller
 * 
 * Handles API requests related to suppliers
 */

namespace Construkt\Controllers;

use Construkt\Models\Supplier;
use Construkt\Models\User;
use Construkt\Models\Product;
use Construkt\Config\Auth;

class SupplierController {
    private $supplierModel;
    private $userModel;
    private $productModel;
    private $auth;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->supplierModel = new Supplier();
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->auth = new Auth();
    }
    
    /**
     * Get all suppliers
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @return array Response with suppliers data
     */
    public function getAll($request, $response) {
        // Check if user is admin
        $user = $this->auth->getCurrentUser();
        if (!$user || $user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get query parameters
        $params = $request->getQueryParams();
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        // Get filters
        $filters = [];
        if (isset($params['name'])) {
            $filters['name'] = $params['name'];
        }
        if (isset($params['is_verified'])) {
            $filters['is_verified'] = $params['is_verified'];
        }
        if (isset($params['is_featured'])) {
            $filters['is_featured'] = $params['is_featured'];
        }
        
        // Get suppliers
        $suppliers = $this->supplierModel->getAll($filters, $limit, $offset);
        $total = $this->supplierModel->countAll($filters);
        
        // Calculate pagination
        $totalPages = ceil($total / $limit);
        
        return [
            'success' => true,
            'data' => [
                'suppliers' => $suppliers,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages
                ]
            ]
        ];
    }
    
    /**
     * Get supplier by ID
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @param array $args Route arguments
     * @return array Response with supplier data
     */
    public function getById($request, $response, $args) {
        // Get supplier by ID
        $supplier = $this->supplierModel->findById($args['id']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier not found'
            ];
        }
        
        return [
            'success' => true,
            'data' => $supplier
        ];
    }
    
    /**
     * Get supplier profile
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @return array Response with supplier data
     */
    public function getProfile($request, $response) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Get supplier by user ID
        $supplier = $this->supplierModel->findByUserId($user['id']);
        
        // Log supplier info
        error_log("Supplier info - User ID: " . $user['id'] . ", Supplier ID: " . ($supplier ? $supplier['id'] : "not found") . ", Email: " . $user['email']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier profile not found'
            ];
        }
        
        return [
            'success' => true,
            'data' => $supplier
        ];
    }
    
    /**
     * Create a new supplier
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @return array Response with created supplier data
     */
    public function create($request, $response) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Check if user already has a supplier profile
        $existingSupplier = $this->supplierModel->findByUserId($user['id']);
        if ($existingSupplier) {
            header('HTTP/1.1 400 Bad Request');
            return [
                'success' => false,
                'message' => 'User already has a supplier profile'
            ];
        }
        
        // Get request data - handle different request formats
        if (is_object($request) && method_exists($request, 'getParsedBody')) {
            $data = $request->getParsedBody();
        } else if (is_object($request) && isset($request->data)) {
            $data = $request->data;
        } else {
            // Fallback to getting data from input stream
            $data = json_decode(file_get_contents('php://input'), true);
        }
        
        // Validate required fields based on the database schema
        $requiredFields = ['company_name', 'phone', 'address', 'city', 'state', 'postal_code', 'country'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                header('HTTP/1.1 400 Bad Request');
                return [
                    'success' => false,
                    'message' => "Missing required field: $field"
                ];
            }
        }
        
        // Add user ID to data
        $data['user_id'] = $user['id'];
        
        // Create supplier
        $supplierId = $this->supplierModel->create($data);
        
        if (!$supplierId) {
            header('HTTP/1.1 500 Internal Server Error');
            return [
                'success' => false,
                'message' => 'Failed to create supplier'
            ];
        }
        
        // Update user role to supplier
        $this->userModel->update($user['id'], ['role' => 'supplier']);
        
        // Get created supplier
        $supplier = $this->supplierModel->findById($supplierId);
        
        header('HTTP/1.1 201 Created');
        return [
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ];
    }
    
    /**
     * Update supplier
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @param array $args Route arguments
     * @return array Response with updated supplier data
     */
    public function update($request, $response, $args) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Get supplier by ID
        $supplier = $this->supplierModel->findById($args['id']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier not found'
            ];
        }
        
        // Check if user is authorized to update this supplier
        if ($supplier['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get request data
        $data = [];
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true) ?? [];
        }
        
        // Log the create product request for debugging
        error_log("Create product request - User ID: {$user['id']}, Supplier ID: {$supplier['id']}");
        
        // Update supplier
        $success = $this->supplierModel->update($args['id'], $data);
        
        if (!$success) {
            header('HTTP/1.1 500 Internal Server Error');
            return [
                'success' => false,
                'message' => 'Failed to update supplier'
            ];
        }
        
        // Get updated supplier
        $supplier = $this->supplierModel->findById($args['id']);
        
        return [
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ];
    }
    
    /**
     * Delete supplier
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @param array $args Route arguments
     * @return array Response with success status
     */
    public function delete($request, $response, $args) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Get supplier by ID
        $supplier = $this->supplierModel->findById($args['id']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier not found'
            ];
        }
        
        // Check if user is authorized to delete this supplier
        if ($supplier['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Delete supplier
        $success = $this->supplierModel->delete($args['id']);
        
        if (!$success) {
            header('HTTP/1.1 500 Internal Server Error');
            return [
                'success' => false,
                'message' => 'Failed to delete supplier'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Supplier deleted successfully'
        ];
    }
    
    /**
     * Get supplier stats
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @return array Response with supplier stats
     */
    public function getStats($request, $response) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Check if user is a supplier
        if ($user['role'] !== 'supplier') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get supplier by user ID
        $supplier = $this->supplierModel->findByUserId($user['id']);
        
        // Log supplier info
        error_log("Supplier info - User ID: " . $user['id'] . ", Supplier ID: " . ($supplier ? $supplier['id'] : "not found") . ", Email: " . $user['email']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier profile not found'
            ];
        }
        
        // Get supplier stats
        $totalProducts = $this->productModel->countBySupplier($supplier['id']);
        $activeProducts = $this->productModel->countBySupplier($supplier['id'], true);
        $totalOrders = 0; // TODO: Implement order counting
        $totalRevenue = 0; // TODO: Implement revenue calculation
        
        return [
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue
            ]
        ];
    }
    
    /**
     * Get supplier products
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @return array Response with supplier products
     */
    public function getProducts($request, $response) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Check if user is a supplier
        if ($user['role'] !== 'supplier') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get supplier by user ID
        $supplier = $this->supplierModel->findByUserId($user['id']);
        
        // Log supplier info
        error_log("Supplier info - User ID: " . $user['id'] . ", Supplier ID: " . ($supplier ? $supplier['id'] : "not found") . ", Email: " . $user['email']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier profile not found'
            ];
        }
        
        // Get query parameters
        $params = [];
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $params);
        }
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Log supplier info for debugging
        error_log("Supplier info - User ID: {$user['id']}, Supplier ID: {$supplier['id']}, Email: {$user['email']}");
        
        // Get products
        $products = $this->productModel->getBySupplierId($supplier['id'], $limit, $offset);
        $total = $this->productModel->countBySupplier($supplier['id']);
        
        // Log product count for debugging
        error_log("Products found for supplier ID {$supplier['id']}: " . count($products) . ", Total: {$total}");
        
        // Calculate pagination
        $totalPages = ceil($total / $limit);
        
        // Format response to match frontend expectations
        return [
            'success' => true,
            'data' => [
                'products' => $products,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Get product categories
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @return array Response with categories
     */
    public function getCategories($request, $response) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Get categories from the database
        // For now, return a static list of categories
        $categories = [
            ['id' => 1, 'name' => 'Building Materials'],
            ['id' => 2, 'name' => 'Tools & Equipment'],
            ['id' => 3, 'name' => 'Electrical'],
            ['id' => 4, 'name' => 'Plumbing'],
            ['id' => 5, 'name' => 'HVAC'],
            ['id' => 6, 'name' => 'Flooring'],
            ['id' => 7, 'name' => 'Roofing'],
            ['id' => 8, 'name' => 'Paint & Supplies'],
            ['id' => 9, 'name' => 'Doors & Windows'],
            ['id' => 10, 'name' => 'Hardware']
        ];
        
        return [
            'success' => true,
            'data' => $categories
        ];
    }
    
    /**
     * Create a new product
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @return array Response with created product data
     */
    public function createProduct($request, $response) {
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Check if user is a supplier
        if ($user['role'] !== 'supplier') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get supplier by user ID
        $supplier = $this->supplierModel->findByUserId($user['id']);
        
        // Log supplier info
        error_log("Supplier info - User ID: " . $user['id'] . ", Supplier ID: " . ($supplier ? $supplier['id'] : "not found") . ", Email: " . $user['email']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier profile not found'
            ];
        }
        
        // Get request data
        $data = [];
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true) ?? [];
        }
        
        // Log the create product request for debugging
        error_log("Create product request - User ID: {$user['id']}, Supplier ID: {$supplier['id']}");
        
        // Validate required fields
        $requiredFields = ['name', 'price', 'category_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                header('HTTP/1.1 400 Bad Request');
                return [
                    'success' => false,
                    'message' => "Missing required field: $field"
                ];
            }
        }

        // Set default value for description if not provided
        if (!isset($data['description'])) {
            $data['description'] = '';
        }
        
        // Add supplier ID to data
        $data['supplier_id'] = $supplier['id'];
        
        // Create product
        $productId = $this->productModel->create($data);
        
        if (!$productId) {
            header('HTTP/1.1 500 Internal Server Error');
            return [
                'success' => false,
                'message' => 'Failed to create product'
            ];
        }
        
        // Get created product
        $product = $this->productModel->findById($productId);
        
        header('HTTP/1.1 201 Created');
        return [
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ];
    }
    
    /**
     * Update a product
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @param array $args Route arguments
     * @return array Response with updated product data
     */
    public function updateProduct($request, $response, $args) {
        // Debug the args array
        error_log("UPDATE ARGS: " . print_r($args, true));
        
        // Extract product ID correctly, handling the nested array structure
        $productId = null;
        if (isset($args['id']['id'])) {
            // Nested array structure
            $productId = (int)$args['id']['id'];
        } elseif (isset($args['id'])) {
            // Direct value
            $productId = (int)$args['id'];
        } else {
            // Fallback - try to get from the URL
            $uri = $request->getUri();
            $path = $uri->getPath();
            $pathParts = explode('/', $path);
            $productId = (int)end($pathParts);
        }
        
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Check if user is a supplier
        if ($user['role'] !== 'supplier') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get supplier by user ID
        $supplier = $this->supplierModel->findByUserId($user['id']);
        
        // Log supplier info
        error_log("Supplier info - User ID: " . $user['id'] . ", Supplier ID: " . ($supplier ? $supplier['id'] : "not found") . ", Email: " . $user['email']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier profile not found'
            ];
        }
        
        // Get product by ID
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            header('HTTP/1.1 404 Not Found');
            error_log("Product not found - ID: " . $productId);
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        // Check if product belongs to the supplier
        $productSupplierId = (int)$product['supplier_id'];
        $currentSupplierId = (int)$supplier['id'];
        
        // Log detailed information about the supplier IDs
        error_log("Product supplier ID: " . $productSupplierId . " (type: " . gettype($productSupplierId) . ")");
        error_log("Current supplier ID: " . $currentSupplierId . " (type: " . gettype($currentSupplierId) . ")");
        
        // Use loose comparison (==) instead of strict comparison (!==)
        if ($productSupplierId != $currentSupplierId) {
            header('HTTP/1.1 403 Forbidden');
            error_log("Access denied - Product supplier ID: " . $productSupplierId . " does not match current supplier ID: " . $currentSupplierId);
            return [
                'success' => false,
                'message' => 'Access denied - You can only update your own products'
            ];
        }
        
        // Get request data
        $data = isset($request->getParsedBody) ? $request->getParsedBody : [];
        
        // Ensure supplier_id is set to the current supplier
        $data['supplier_id'] = $supplier['id'];
        
        // Update product
        $success = $this->productModel->update($productId, $data);
        
        if (!$success) {
            header('HTTP/1.1 500 Internal Server Error');
            error_log("Failed to update product - ID: " . $productId);
            return [
                'success' => false,
                'message' => 'Failed to update product'
            ];
        }
        
        error_log("Product updated successfully - ID: " . $productId);
        return [
            'success' => true,
            'message' => 'Product updated successfully'
        ];
    }
    
    /**
     * Delete a product
     * 
     * @param object $request Request object
     * @param object $response Response object
     * @param array $args Route arguments
     * @return array Response with success status
     */
    public function deleteProduct($request, $response, $args) {
        // Debug the args array
        error_log("DELETE ARGS: " . print_r($args, true));
        
        // Extract product ID correctly, handling the nested array structure
        $productId = null;
        if (isset($args['id']['id'])) {
            // Nested array structure
            $productId = (int)$args['id']['id'];
        } elseif (isset($args['id'])) {
            // Direct value
            $productId = (int)$args['id'];
        } else {
            // Fallback - try to get from the URL
            $uri = $request->getUri();
            $path = $uri->getPath();
            $pathParts = explode('/', $path);
            $productId = (int)end($pathParts);
        }
        
        // Get current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }
        
        // Check if user is a supplier
        if ($user['role'] !== 'supplier') {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        // Get supplier by user ID
        $supplier = $this->supplierModel->findByUserId($user['id']);
        
        // Log supplier info
        error_log("Supplier info - User ID: " . $user['id'] . ", Supplier ID: " . ($supplier ? $supplier['id'] : "not found") . ", Email: " . $user['email']);
        
        if (!$supplier) {
            header('HTTP/1.1 404 Not Found');
            return [
                'success' => false,
                'message' => 'Supplier profile not found'
            ];
        }
        
        // Log the request for debugging
        error_log("Delete product request - User ID: " . $user['id'] . ", Supplier ID: " . $supplier['id'] . ", Product ID: " . $productId);
        
        // Get product by ID
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            header('HTTP/1.1 404 Not Found');
            error_log("Product not found - ID: " . $productId);
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        // Check if product belongs to the supplier
        $productSupplierId = (int)$product['supplier_id'];
        $currentSupplierId = (int)$supplier['id'];
        
        // Log detailed information about the supplier IDs
        error_log("Product supplier ID: " . $productSupplierId . " (type: " . gettype($productSupplierId) . ")");
        error_log("Current supplier ID: " . $currentSupplierId . " (type: " . gettype($currentSupplierId) . ")");
        
        // Use loose comparison (==) instead of strict comparison (!==)
        if ($productSupplierId != $currentSupplierId) {
            header('HTTP/1.1 403 Forbidden');
            error_log("Access denied - Product supplier ID: " . $productSupplierId . " does not match current supplier ID: " . $currentSupplierId);
            return [
                'success' => false,
                'message' => 'Access denied - You can only delete your own products'
            ];
        }
        
        // Delete product
        $success = $this->productModel->delete($productId);
        
        if (!$success) {
            header('HTTP/1.1 500 Internal Server Error');
            error_log("Failed to delete product - ID: " . $productId);
            return [
                'success' => false,
                'message' => 'Failed to delete product'
            ];
        }
        
        error_log("Product deleted successfully - ID: " . $productId);
        return [
            'success' => true,
            'message' => 'Product deleted successfully'
        ];
    }
}
