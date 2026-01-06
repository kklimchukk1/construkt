<?php
/**
 * API Routes Definition
 * 
 * This file defines all the routes for the API.
 * It will be expanded as we implement more features.
 */

// Define routes array
$routes = [
    // Health check route
    'GET /api/health' => function() {
        return [
            'status' => 'success',
            'message' => 'API is running',
            'data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'environment' => $_ENV['APP_ENV'] ?? 'development'
            ]
        ];
    },
    
    // User routes
    'POST /api/auth/register' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create user controller
        $userController = new \Construkt\Controllers\UserController();
        
        // Register user
        return $userController->register($data);
    },
    
    'POST /api/auth/login' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create user controller
        $userController = new \Construkt\Controllers\UserController();
        
        // Login user
        return $userController->login($data);
    },
    
    // Protected route example
    'GET /api/auth/me' => function() {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();
        
        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'status' => 'error',
                'message' => 'Unauthorized'
            ];
        }
        
        // Get user details from database
        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);
        
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'User not found'
            ];
        }
        
        // Remove sensitive data
        unset($user['password']);
        
        return [
            'status' => 'success',
            'data' => [
                'user' => $user
            ]
        ];
    },
    
    // Update user profile
    'PUT /api/auth/profile' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create user controller
        $userController = new \Construkt\Controllers\UserController();
        
        // Update profile
        return $userController->updateProfile($data);
    },
    
    // Supplier profile routes
    'GET /api/supplier/profile' => function() {
        // Create request and response objects
        $request = new \stdClass();
        $response = new \stdClass();
        
        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();
        
        // Get supplier profile
        return $supplierController->getProfile($request, $response);
    },
    
    'POST /api/supplier/profile' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create request object
        $request = new \stdClass();
        $request->data = $data;
        
        // Create response object
        $response = new \stdClass();
        
        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();
        
        // Create supplier profile
        return $supplierController->create($request, $response);
    },
    
    // Supplier stats route
    'GET /api/supplier/stats' => function() {
        // Create request and response objects
        $request = new \stdClass();
        $response = new \stdClass();
        
        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();
        
        // Get supplier stats
        return $supplierController->getStats($request, $response);
    },
    
    // Supplier products routes
    'GET /api/supplier/products' => function() {
        // Create request and response objects
        $request = new \stdClass();
        // Define getQueryParams as a method
        $request->getQueryParams = $_GET;
        $response = new \stdClass();
        
        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();
        
        // Get supplier products
        return $supplierController->getProducts($request, $response);
    },
    
    'POST /api/supplier/products' => function() {
        // Create request and response objects
        $request = new \stdClass();
        // Define getParsedBody as a property with the parsed request body
        $request->getParsedBody = json_decode(file_get_contents('php://input'), true);
        $response = new \stdClass();
        
        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();
        
        // Create product
        return $supplierController->createProduct($request, $response);
    },
    
    'PUT /api/supplier/products/{id}' => function($id) {
        // Create request and response objects
        $request = new \stdClass();
        // Define getParsedBody as a property with the parsed request body
        $request->getParsedBody = json_decode(file_get_contents('php://input'), true);
        $response = new \stdClass();
        
        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();
        
        // Update product
        return $supplierController->updateProduct($request, $response, ['id' => $id]);
    },
    
    'DELETE /api/supplier/products/{id}' => function($id) {
        // Create request and response objects
        $request = new \stdClass();
        $response = new \stdClass();
        
        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();
        
        // Delete product
        return $supplierController->deleteProduct($request, $response, ['id' => $id]);
    },
    
    // User management routes (admin only)
    'GET /api/users' => function() {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Get user role
        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || $user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            return ['success' => false, 'message' => 'Access denied'];
        }

        $userController = new \Construkt\Controllers\UserController();
        return $userController->getAll();
    },

    'PUT /api/users/{id}/role' => function($params) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || $user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userController = new \Construkt\Controllers\UserController();
        return $userController->updateRole($params['id'], $data);
    },

    'PUT /api/users/{id}/status' => function($params) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || $user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userController = new \Construkt\Controllers\UserController();
        return $userController->updateStatus($params['id'], $data);
    },

    'DELETE /api/users/{id}' => function($params) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || $user['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            return ['success' => false, 'message' => 'Access denied'];
        }

        $userController = new \Construkt\Controllers\UserController();
        return $userController->deleteUser($params['id']);
    },

    // Categories routes
    'GET /api/categories' => function() {
        // Create request and response objects
        $request = new \stdClass();
        $response = new \stdClass();

        // Create supplier controller
        $supplierController = new \Construkt\Controllers\SupplierController();

        // Get categories
        return $supplierController->getCategories($request, $response);
    },

    'POST /api/categories' => function() {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || !in_array($user['role'], ['admin', 'supplier'])) {
            header('HTTP/1.1 403 Forbidden');
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $categoryModel = new \Construkt\Models\Category();
        $categoryId = $categoryModel->create($data);

        if (!$categoryId) {
            return ['success' => false, 'message' => 'Failed to create category'];
        }

        return ['success' => true, 'message' => 'Category created successfully', 'data' => ['id' => $categoryId]];
    },

    'PUT /api/categories/{id}' => function($params) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || !in_array($user['role'], ['admin', 'supplier'])) {
            header('HTTP/1.1 403 Forbidden');
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $categoryModel = new \Construkt\Models\Category();
        $success = $categoryModel->update($params['id'], $data);

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to update category'];
        }

        return ['success' => true, 'message' => 'Category updated successfully'];
    },

    'DELETE /api/categories/{id}' => function($params) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || !in_array($user['role'], ['admin', 'supplier'])) {
            header('HTTP/1.1 403 Forbidden');
            return ['success' => false, 'message' => 'Access denied'];
        }

        $categoryModel = new \Construkt\Models\Category();
        $success = $categoryModel->delete($params['id']);

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to delete category'];
        }

        return ['success' => true, 'message' => 'Category deleted successfully'];
    },

    // Product routes
    'GET /api/products' => function() {
        // Get query parameters
        $queryParams = [];
        
        // Parse query string
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queryParams);
        }
        
        // Create product controller
        $productController = new \Construkt\Controllers\ProductController();
        
        // Get products
        return $productController->getProducts($queryParams);
    },
    
    'GET /api/products/featured' => function() {
        // Get limit parameter
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
        
        // Create product controller
        $productController = new \Construkt\Controllers\ProductController();
        
        // Get featured products
        return $productController->getFeaturedProducts($limit);
    },
    
    'GET /api/products/{id}' => function($params) {
        // Get product ID from URL parameters
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'status' => 'error',
                'message' => 'Product ID is required'
            ];
        }

        // Create product controller
        $productController = new \Construkt\Controllers\ProductController();

        // Get product by ID
        return $productController->getProductById($id);
    },

    'POST /api/products' => function() {
        // Authenticate request - only admin and supplier can create products
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }

        // Get user role
        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || !in_array($user['role'], ['admin', 'supplier'])) {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // For admin, set default supplier_id if not provided
        if ($user['role'] === 'admin' && empty($data['supplier_id'])) {
            $data['supplier_id'] = 1; // Default system supplier
        }

        // For supplier, get their supplier_id
        if ($user['role'] === 'supplier') {
            $supplierModel = new \Construkt\Models\Supplier();
            $supplier = $supplierModel->findByUserId($user['id']);
            if ($supplier) {
                $data['supplier_id'] = $supplier['id'];
            } else {
                return [
                    'success' => false,
                    'message' => 'Supplier profile not found'
                ];
            }
        }

        // Create product controller
        $productController = new \Construkt\Controllers\ProductController();

        // Create product
        return $productController->createProduct($data);
    },

    'PUT /api/products/{id}' => function($params) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }

        // Get user role
        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || !in_array($user['role'], ['admin', 'supplier'])) {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }

        // Get product ID
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'success' => false,
                'message' => 'Product ID is required'
            ];
        }

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Create product controller
        $productController = new \Construkt\Controllers\ProductController();

        // Update product
        return $productController->updateProduct($id, $data);
    },

    'DELETE /api/products/{id}' => function($params) {
        // Authenticate request
        $userData = \Construkt\Middleware\AuthMiddleware::authenticate();

        if (!$userData) {
            header('HTTP/1.1 401 Unauthorized');
            return [
                'success' => false,
                'message' => 'Unauthorized'
            ];
        }

        // Get user role
        $userModel = new \Construkt\Models\User();
        $user = $userModel->findById($userData['user_id']);

        if (!$user || !in_array($user['role'], ['admin', 'supplier'])) {
            header('HTTP/1.1 403 Forbidden');
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }

        // Get product ID
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'success' => false,
                'message' => 'Product ID is required'
            ];
        }

        // Create product controller
        $productController = new \Construkt\Controllers\ProductController();

        // Delete product
        return $productController->deleteProduct($id);
    },
    
    // Calculator routes
    'POST /api/calculator/area' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create calculator controller
        $calculatorController = new \Construkt\Controllers\CalculatorController();
        
        // Calculate area materials
        return $calculatorController->calculateArea($data);
    },
    
    'POST /api/calculator/volume' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create calculator controller
        $calculatorController = new \Construkt\Controllers\CalculatorController();
        
        // Calculate volume materials
        return $calculatorController->calculateVolume($data);
    },
    
    'POST /api/calculator/linear' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create calculator controller
        $calculatorController = new \Construkt\Controllers\CalculatorController();
        
        // Calculate linear materials
        return $calculatorController->calculateLinear($data);
    },
    
    'POST /api/calculator/calculate' => function() {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create calculator controller
        $calculatorController = new \Construkt\Controllers\CalculatorController();
        
        // Generic calculate endpoint
        return $calculatorController->calculate($data);
    }
];

// This will be used by the router to match routes
return $routes;
