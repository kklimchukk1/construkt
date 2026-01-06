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
        $data = $request->getParsedBody();
        
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
}
