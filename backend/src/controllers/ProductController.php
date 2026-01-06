<?php
/**
 * Product Controller
 * 
 * Handles product-related API endpoints
 */

namespace Construkt\Controllers;

use Construkt\Models\Product;
use Construkt\Models\Category;

class ProductController {
    // Product model instance
    private $productModel;
    
    // Category model instance
    private $categoryModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }
    
    /**
     * Get all products with optional filtering
     * 
     * @param array $queryParams Query parameters for filtering
     * @return array Response
     */
    public function getProducts($queryParams = []) {
        // Parse query parameters
        $filters = [];
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        // Apply filters
        if (isset($queryParams['category'])) {
            $filters['category_id'] = (int)$queryParams['category'];
        }
        
        if (isset($queryParams['search'])) {
            $filters['search'] = $queryParams['search'];
        }
        
        if (isset($queryParams['min_price'])) {
            $filters['min_price'] = (float)$queryParams['min_price'];
        }
        
        if (isset($queryParams['max_price'])) {
            $filters['max_price'] = (float)$queryParams['max_price'];
        }
        
        if (isset($queryParams['supplier'])) {
            $filters['supplier_id'] = (int)$queryParams['supplier'];
        }
        
        // Get products
        $products = $this->productModel->getAll($filters, $limit, $offset);
        
        // Get total count for pagination
        $totalCount = $this->productModel->getCount($filters);
        $totalPages = ceil($totalCount / $limit);
        
        // Log response data for debugging
        error_log("Products API response - Total products: " . count($products) . ", Total count: {$totalCount}");
        
        return [
            'success' => true,
            'data' => [
                'products' => $products,
                'pagination' => [
                    'total' => $totalCount,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => $totalPages
                ]
            ]
        ];
    }
    
    /**
     * Get product by ID
     * 
     * @param int $id Product ID
     * @return array Response
     */
    public function getProductById($id) {
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        // Get related products
        $relatedProducts = $this->productModel->getRelated($id, 4);
        
        return [
            'success' => true,
            'data' => [
                'product' => $product,
                'related_products' => $relatedProducts
            ]
        ];
    }
    
    /**
     * Get featured products
     * 
     * @param int $limit Maximum number of products to return
     * @return array Response
     */
    public function getFeaturedProducts($limit = 6) {
        $products = $this->productModel->getFeatured($limit);
        
        return [
            'success' => true,
            'data' => [
                'products' => $products
            ]
        ];
    }
    
    /**
     * Get all categories
     * 
     * @param bool $withProductCounts Include product counts
     * @return array Response
     */
    public function getCategories($withProductCounts = false) {
        $categories = $withProductCounts 
            ? $this->categoryModel->getAllWithProductCounts() 
            : $this->categoryModel->getAll();
        
        return [
            'success' => true,
            'data' => [
                'categories' => $categories
            ]
        ];
    }
    
    /**
     * Create a new product
     * 
     * @param array $data Product data
     * @return array Response
     */
    public function createProduct($data) {
        // Validate required fields
        $requiredFields = ['name', 'price', 'category_id', 'stock_quantity', 'supplier_id'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            return [
                'success' => false,
                'message' => 'Missing required fields: ' . implode(', ', $missingFields)
            ];
        }
        
        // Create product
        $productId = $this->productModel->create($data);
        
        if (!$productId) {
            return [
                'success' => false,
                'message' => 'Failed to create product'
            ];
        }
        
        // Get the newly created product
        $product = $this->productModel->getById($productId);
        
        return [
            'success' => true,
            'message' => 'Product created successfully',
            'data' => [
                'product' => $product
            ]
        ];
    }
    
    /**
     * Update an existing product
     * 
     * @param int $id Product ID
     * @param array $data Product data to update
     * @return array Response
     */
    public function updateProduct($id, $data) {
        // Check if product exists
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        // Update product
        $success = $this->productModel->update($id, $data);
        
        if (!$success) {
            return [
                'success' => false,
                'message' => 'Failed to update product'
            ];
        }
        
        // Get the updated product
        $updatedProduct = $this->productModel->getById($id);
        
        return [
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => [
                'product' => $updatedProduct
            ]
        ];
    }
    
    /**
     * Delete a product
     * 
     * @param int $id Product ID
     * @return array Response
     */
    public function deleteProduct($id) {
        // Check if product exists
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        // Delete product (soft delete)
        $success = $this->productModel->delete($id);
        
        if (!$success) {
            return [
                'success' => false,
                'message' => 'Failed to delete product'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Product deleted successfully'
        ];
    }
}
