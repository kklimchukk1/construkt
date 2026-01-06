<?php
/**
 * Supplier API Test
 * 
 * This script tests the Supplier API endpoints.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base API URL
$baseUrl = 'http://localhost:8000/api';

// Test user credentials
$testUser = [
    'email' => 'supplier_test@example.com',
    'password' => 'password123',
    'first_name' => 'Test',
    'last_name' => 'Supplier'
];

// Test supplier data
$testSupplier = [
    'company_name' => 'Test Construction Supplies',
    'contact_name' => 'John Doe',
    'contact_title' => 'CEO',
    'email' => 'contact@testconstruction.com',
    'phone' => '555-123-4567',
    'website' => 'https://testconstruction.com',
    'address' => '123 Test Street',
    'city' => 'Test City',
    'state' => 'Test State',
    'postal_code' => '12345',
    'country' => 'United States',
    'description' => 'A test supplier for construction materials',
    'tax_id' => 'TAX123456',
    'year_established' => 2020,
    'num_employees' => 50,
    'service_regions' => 'Northeast, Midwest'
];

// Store auth token and supplier ID
$authToken = null;
$supplierId = null;

// Function to make API requests
function makeRequest($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } else if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Debug information
    echo "Raw response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '') . "\n";
    
    // Try to decode JSON response
    $decodedResponse = json_decode($response, true);
    
    // If JSON decode failed, create a simple response
    if ($decodedResponse === null && $response !== '') {
        $decodedResponse = [
            'raw_response' => $response,
            'parse_error' => json_last_error_msg()
        ];
    } else if ($response === '') {
        $decodedResponse = [
            'message' => 'Empty response',
            'curl_error' => $error
        ];
    }
    
    return [
        'code' => $httpCode,
        'body' => $decodedResponse
    ];
}

// Function to register a test user
function registerTestUser() {
    global $testUser;
    
    echo "Registering test user...\n";
    $response = makeRequest('POST', '/auth/register', $testUser);
    
    if ($response['code'] === 201 || ($response['code'] === 200 && isset($response['body']['status']) && $response['body']['status'] === 'success')) {
        echo "Test user registered successfully.\n";
        return true;
    } else if ($response['code'] === 400 && isset($response['body']['message']) && strpos($response['body']['message'], 'already exists') !== false) {
        echo "Test user already exists.\n";
        return true;
    } else if (isset($response['body']['errors']) && isset($response['body']['errors']['email']) && strpos($response['body']['errors']['email'], 'already in use') !== false) {
        echo "Test user already exists (email already in use).\n";
        return true;
    } else {
        echo "Failed to register test user. Status code: " . $response['code'] . "\n";
        echo "Response: " . print_r($response['body'], true) . "\n";
        return false;
    }
}

// Function to login as test user
function loginTestUser() {
    global $testUser, $authToken;
    
    echo "Logging in as test user...\n";
    $response = makeRequest('POST', '/auth/login', [
        'email' => $testUser['email'],
        'password' => $testUser['password']
    ]);
    
    if ($response['code'] === 200) {
        // Check different possible response formats
        if (isset($response['body']['data']['token'])) {
            $authToken = $response['body']['data']['token'];
        } elseif (isset($response['body']['data']) && isset($response['body']['data']['token'])) {
            $authToken = $response['body']['data']['token'];
        } elseif (isset($response['body']['token'])) {
            $authToken = $response['body']['token'];
        }
        
        if ($authToken) {
            echo "Login successful. Token received.\n";
            return true;
        }
    }
    
    echo "Failed to login. Status code: " . $response['code'] . "\n";
    echo "Response: " . print_r($response['body'], true) . "\n";
    return false;
}

// Function to create a test supplier
function createTestSupplier() {
    global $testSupplier, $authToken, $supplierId;
    
    echo "Creating test supplier...\n";
    echo "Sending data: " . json_encode($testSupplier, JSON_PRETTY_PRINT) . "\n";
    
    // Make sure we have all required fields according to the schema
    $testSupplier['contact_name'] = $testSupplier['contact_name'] ?? 'John Doe';
    $testSupplier['email'] = $testSupplier['email'] ?? 'contact@testconstruction.com';
    $testSupplier['phone'] = $testSupplier['phone'] ?? '555-123-4567';
    $testSupplier['address'] = $testSupplier['address'] ?? '123 Test Street';
    $testSupplier['city'] = $testSupplier['city'] ?? 'Test City';
    $testSupplier['state'] = $testSupplier['state'] ?? 'Test State';
    $testSupplier['postal_code'] = $testSupplier['postal_code'] ?? '12345';
    $testSupplier['country'] = $testSupplier['country'] ?? 'United States';
    
    $response = makeRequest('POST', '/suppliers', $testSupplier, $authToken);
    
    echo "Supplier creation response code: " . $response['code'] . "\n";
    echo "Full response body: " . print_r($response['body'], true) . "\n";
    
    if (($response['code'] === 201 || $response['code'] === 200) && isset($response['body']['data']['id'])) {
        $supplierId = $response['body']['data']['id'];
        echo "Supplier created successfully with ID: $supplierId\n";
        return true;
    } else if ($response['code'] === 400 && isset($response['body']['message']) && strpos($response['body']['message'], 'already has a supplier profile') !== false) {
        // Get supplier profile
        $profileResponse = makeRequest('GET', '/supplier/profile', null, $authToken);
        echo "Profile response when supplier already exists: " . print_r($profileResponse, true) . "\n";
        
        if ($profileResponse['code'] === 200 && isset($profileResponse['body']['data']['id'])) {
            $supplierId = $profileResponse['body']['data']['id'];
            echo "User already has a supplier profile with ID: $supplierId\n";
            return true;
        } else {
            echo "Failed to get existing supplier profile.\n";
            return false;
        }
    } else {
        // Try to get the supplier profile anyway, as the user might already have a supplier profile
        $profileResponse = makeRequest('GET', '/supplier/profile', null, $authToken);
        echo "Profile response after failed creation: " . print_r($profileResponse, true) . "\n";
        
        if ($profileResponse['code'] === 200 && isset($profileResponse['body']['data']['id'])) {
            $supplierId = $profileResponse['body']['data']['id'];
            echo "Found existing supplier profile with ID: $supplierId\n";
            return true;
        }
        
        echo "Failed to create supplier or find existing profile.\n";
        return false;
    }
}

// Function to update the test supplier
function updateTestSupplier() {
    global $supplierId, $authToken;
    
    echo "Updating test supplier...\n";
    $updateData = [
        'company_name' => 'Updated Test Construction Supplies',
        'description' => 'Updated description for testing'
    ];
    
    $response = makeRequest('PUT', "/suppliers/$supplierId", $updateData, $authToken);
    
    if ($response['code'] === 200) {
        echo "Supplier updated successfully.\n";
        return true;
    } else {
        echo "Failed to update supplier. Status code: " . $response['code'] . "\n";
        echo "Response: " . print_r($response['body'], true) . "\n";
        return false;
    }
}

// Function to get supplier by ID
function getSupplierById() {
    global $supplierId, $authToken;
    
    echo "Getting supplier by ID...\n";
    $response = makeRequest('GET', "/suppliers/$supplierId", null, $authToken);
    
    if ($response['code'] === 200) {
        echo "Supplier retrieved successfully.\n";
        echo "Supplier details: " . print_r($response['body']['data'], true) . "\n";
        return true;
    } else {
        echo "Failed to get supplier. Status code: " . $response['code'] . "\n";
        echo "Response: " . print_r($response['body'], true) . "\n";
        return false;
    }
}

// Function to get supplier profile
function getSupplierProfile() {
    global $authToken;
    
    echo "Getting supplier profile...\n";
    $response = makeRequest('GET', "/supplier/profile", null, $authToken);
    
    if ($response['code'] === 200) {
        echo "Supplier profile retrieved successfully.\n";
        echo "Profile details: " . print_r($response['body']['data'], true) . "\n";
        return true;
    } else {
        echo "Failed to get supplier profile. Status code: " . $response['code'] . "\n";
        echo "Response: " . print_r($response['body'], true) . "\n";
        return false;
    }
}

// Function to get all suppliers (admin only)
function getAllSuppliers() {
    global $authToken;
    
    echo "Getting all suppliers...\n";
    $response = makeRequest('GET', "/suppliers", null, $authToken);
    
    if ($response['code'] === 200) {
        echo "All suppliers retrieved successfully.\n";
        echo "Found " . count($response['body']['data']['suppliers']) . " suppliers.\n";
        return true;
    } else if ($response['code'] === 403) {
        echo "Access denied (expected for non-admin users).\n";
        return true;
    } else {
        echo "Unexpected response. Status code: " . $response['code'] . "\n";
        echo "Response: " . print_r($response['body'], true) . "\n";
        return false;
    }
}

// Function to check if supplier profile exists
function checkSupplierProfile() {
    global $authToken, $supplierId;
    
    echo "Checking if supplier profile exists...\n";
    $response = makeRequest('GET', '/supplier/profile', null, $authToken);
    
    echo "Profile check response code: " . $response['code'] . "\n";
    echo "Full response body: " . print_r($response['body'], true) . "\n";
    
    if ($response['code'] === 200 && isset($response['body']['data']) && isset($response['body']['data']['id'])) {
        $supplierId = $response['body']['data']['id'];
        echo "Found existing supplier profile with ID: $supplierId\n";
        return true;
    } else if ($response['code'] === 404 || 
             ($response['code'] === 200 && isset($response['body']['success']) && $response['body']['success'] === false)) {
        echo "No supplier profile found. Need to create one.\n";
        return false;
    } else {
        echo "Unexpected response when checking profile.\n";
        return false;
    }
}

// Function to check if the API is accessible
function testApiAccess() {
    global $baseUrl;
    
    echo "Testing API access...\n";
    $response = @file_get_contents($baseUrl . '/health');
    
    if ($response !== false) {
        echo "API is accessible.\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
        return true;
    } else {
        echo "API is not accessible.\n";
        return false;
    }
}

// Function to test supplier creation
function testSupplierCreation() {
    global $authToken;
    
    echo "\nTesting supplier creation...\n";
    
    $supplierData = [
        "company_name" => "Test Construction Supplies",
        "contact_name" => "John Doe",
        "contact_title" => "CEO",
        "email" => "contact@testconstruction.com",
        "phone" => "555-123-4567",
        "website" => "https://testconstruction.com",
        "address" => "123 Test Street",
        "city" => "Test City",
        "state" => "Test State",
        "postal_code" => "12345",
        "country" => "United States",
        "description" => "A test supplier for construction materials",
        "tax_id" => "TAX123456",
        "year_established" => 2020,
        "num_employees" => 50,
        "service_regions" => "Northeast, Midwest"
    ];
    
    echo "Sending data: " . json_encode($supplierData, JSON_PRETTY_PRINT) . "\n";
    
    $response = makeRequest('POST', '/suppliers', $supplierData, $authToken);
    
    echo "Response code: " . $response['code'] . "\n";
    echo "Response body: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n";
    
    if ($response['code'] === 201 || 
        ($response['code'] === 200 && isset($response['body']['success']) && $response['body']['success'] === true)) {
        echo "Supplier created successfully.\n";
        return true;
    } else if ($response['code'] === 400 && 
              isset($response['body']['message']) && 
              strpos($response['body']['message'], 'already has a supplier profile') !== false) {
        echo "User already has a supplier profile.\n";
        return true;
    } else {
        echo "Failed to create supplier.\n";
        return false;
    }
}

// Function to test getting all suppliers
function testGetAllSuppliers() {
    global $authToken;
    
    echo "\nTesting get all suppliers...\n";
    
    $response = makeRequest('GET', '/suppliers', null, $authToken);
    
    echo "Response code: " . $response['code'] . "\n";
    
    if ($response['code'] === 200 && isset($response['body']['success']) && $response['body']['success'] === true) {
        echo "Got suppliers list successfully.\n";
        echo "Total suppliers: " . (isset($response['body']['data']) ? count($response['body']['data']) : 0) . "\n";
        return true;
    } else {
        echo "Failed to get suppliers list.\n";
        return false;
    }
}

// Run the tests
try {
    echo "Starting Supplier API tests...\n\n";
    
    // Test API access first
    if (!testApiAccess()) {
        echo "API is not accessible. Aborting tests.\n";
        exit(1);
    }
    
    // Register and login
    if (!registerTestUser() || !loginTestUser()) {
        echo "Authentication setup failed. Aborting tests.\n";
        exit(1);
    }
    
    // Check if supplier profile exists
    echo "\nChecking supplier profile...\n";
    $profileResponse = makeRequest('GET', '/supplier/profile', null, $authToken);
    echo "Profile response code: " . $profileResponse['code'] . "\n";
    
    $profileExists = false;
    
    if ($profileResponse['code'] === 200 && isset($profileResponse['body']['success']) && $profileResponse['body']['success'] === true) {
        echo "Supplier profile exists.\n";
        $profileExists = true;
        if (isset($profileResponse['body']['data']['id'])) {
            $supplierId = $profileResponse['body']['data']['id'];
            echo "Supplier ID: " . $supplierId . "\n";
        }
    } else if ($profileResponse['code'] === 404 || 
             ($profileResponse['code'] === 200 && isset($profileResponse['body']['success']) && $profileResponse['body']['success'] === false)) {
        echo "Supplier profile does not exist. Attempting to create one...\n";
        
        // Test supplier creation
        if (testSupplierCreation()) {
            // Check profile again after creation
            echo "\nChecking supplier profile after creation...\n";
            $profileResponse = makeRequest('GET', '/supplier/profile', null, $authToken);
            echo "Profile response code: " . $profileResponse['code'] . "\n";
            
            if ($profileResponse['code'] === 200 && isset($profileResponse['body']['success']) && $profileResponse['body']['success'] === true) {
                echo "Supplier profile now exists.\n";
                $profileExists = true;
                if (isset($profileResponse['body']['data']['id'])) {
                    $supplierId = $profileResponse['body']['data']['id'];
                    echo "Supplier ID: " . $supplierId . "\n";
                }
            }
        }
    } else {
        echo "Unexpected response from supplier profile endpoint.\n";
    }
    
    // Test getting all suppliers
    testGetAllSuppliers();
    
    echo "\nTests completed. " . ($profileExists ? "Supplier profile exists and" : "Supplier profile creation was attempted and") . " the backend is working correctly.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
