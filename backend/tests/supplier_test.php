<?php
/**
 * Supplier Model Test
 * 
 * This script tests the Supplier model functionality.
 */

// Load dependencies
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../src/models/Supplier.php';

use Construkt\Models\Supplier;
use Construkt\Models\User;

// Create test user if not exists
function createTestUser() {
    $userModel = new User();
    
    // Check if test user exists
    $testUser = $userModel->findByEmail('supplier_test@example.com');
    
    if ($testUser) {
        echo "Test user already exists with ID: " . $testUser['id'] . "\n";
        return $testUser['id'];
    }
    
    // Create test user
    $userData = [
        'email' => 'supplier_test@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'first_name' => 'Test',
        'last_name' => 'Supplier',
        'role' => 'user'
    ];
    
    $userId = $userModel->create($userData);
    
    if (!$userId) {
        echo "Failed to create test user\n";
        exit(1);
    }
    
    echo "Created test user with ID: " . $userId . "\n";
    return $userId;
}

// Test Supplier model
function testSupplierModel($userId) {
    $supplierModel = new Supplier();
    
    echo "\n=== Testing Supplier Model ===\n";
    
    // Test create supplier
    echo "\n--- Testing create() ---\n";
    $supplierData = [
        'user_id' => $userId,
        'company_name' => 'Test Company',
        'business_type' => 'Construction',
        'address' => '123 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'postal_code' => '12345',
        'country' => 'Test Country',
        'phone' => '123-456-7890',
        'tax_id' => 'TAX123456',
        'is_verified' => 0
    ];
    
    $supplierId = $supplierModel->create($supplierData);
    
    if (!$supplierId) {
        echo "Failed to create supplier\n";
        return;
    }
    
    echo "Created supplier with ID: " . $supplierId . "\n";
    
    // Test findById
    echo "\n--- Testing findById() ---\n";
    $supplier = $supplierModel->findById($supplierId);
    
    if (!$supplier) {
        echo "Failed to find supplier by ID\n";
        return;
    }
    
    echo "Found supplier: " . $supplier['company_name'] . "\n";
    
    // Test findByUserId
    echo "\n--- Testing findByUserId() ---\n";
    $supplierByUser = $supplierModel->findByUserId($userId);
    
    if (!$supplierByUser) {
        echo "Failed to find supplier by user ID\n";
        return;
    }
    
    echo "Found supplier by user ID: " . $supplierByUser['company_name'] . "\n";
    
    // Test update
    echo "\n--- Testing update() ---\n";
    $updateData = [
        'company_name' => 'Updated Test Company',
        'business_type' => 'Updated Construction'
    ];
    
    $updateResult = $supplierModel->update($supplierId, $updateData);
    
    if (!$updateResult) {
        echo "Failed to update supplier\n";
        return;
    }
    
    $updatedSupplier = $supplierModel->findById($supplierId);
    echo "Updated supplier name: " . $updatedSupplier['company_name'] . "\n";
    
    // Test getAll
    echo "\n--- Testing getAll() ---\n";
    $suppliers = $supplierModel->getAll();
    echo "Found " . count($suppliers) . " suppliers\n";
    
    // Test getCount
    echo "\n--- Testing getCount() ---\n";
    $count = $supplierModel->getCount();
    echo "Total suppliers count: " . $count . "\n";
    
    // Test verify
    echo "\n--- Testing verify() ---\n";
    $verifyResult = $supplierModel->verify($supplierId);
    
    if (!$verifyResult) {
        echo "Failed to verify supplier\n";
        return;
    }
    
    $verifiedSupplier = $supplierModel->findById($supplierId);
    echo "Supplier verification status: " . $verifiedSupplier['is_verified'] . "\n";
    
    // Test delete
    echo "\n--- Testing delete() ---\n";
    $deleteResult = $supplierModel->delete($supplierId);
    
    if (!$deleteResult) {
        echo "Failed to delete supplier\n";
        return;
    }
    
    $deletedSupplier = $supplierModel->findById($supplierId);
    
    if (!$deletedSupplier) {
        echo "Supplier deleted successfully\n";
    } else {
        echo "Supplier not deleted\n";
    }
    
    echo "\n=== Supplier Model Tests Completed ===\n";
}

// Run tests
try {
    echo "Starting Supplier model tests...\n";
    
    // Create test user
    $userId = createTestUser();
    
    // Test Supplier model
    testSupplierModel($userId);
    
    echo "All tests completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
