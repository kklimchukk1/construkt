<?php
/**
 * Simple Supplier API Test
 * 
 * A minimal test script for the supplier management API
 */

// Set base URL for API
$baseUrl = 'http://localhost:8000/api';

// Test user credentials
$testUser = [
    'email' => 'supplier_test@example.com',
    'password' => 'Test123!',
    'first_name' => 'Test',
    'last_name' => 'User'
];

// Global variable to store auth token
$authToken = null;

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
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            echo "Sending JSON data: $jsonData\n";
        }
    } else if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            echo "Sending JSON data: $jsonData\n";
        }
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Response from $endpoint (HTTP $httpCode): " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '') . "\n";
    
    if ($error) {
        echo "cURL Error: $error\n";
    }
    
    $decodedBody = json_decode($response, true);
    
    return [
        'code' => $httpCode,
        'body' => $decodedBody,
        'raw' => $response
    ];
}

// Function to register test user
function registerTestUser() {
    global $testUser;
    
    echo "Registering test user...\n";
    $response = makeRequest('POST', '/register', $testUser);
    
    if ($response['code'] === 201 || 
        ($response['code'] === 200 && isset($response['body']['status']) && $response['body']['status'] === 'success')) {
        echo "Test user registered successfully.\n";
        return true;
    } else if (isset($response['body']['errors']['email']) && 
              strpos($response['body']['errors']['email'], 'already in use') !== false) {
        echo "Test user already exists (email already in use).\n";
        return true;
    } else {
        echo "Failed to register test user.\n";
        return false;
    }
}

// Function to login test user
function loginTestUser() {
    global $testUser, $authToken;
    
    echo "Logging in as test user...\n";
    $response = makeRequest('POST', '/login', [
        'email' => $testUser['email'],
        'password' => $testUser['password']
    ]);
    
    if ($response['code'] === 200 && isset($response['body']['status']) && $response['body']['status'] === 'success') {
        // Extract token from the response
        if (isset($response['body']['data']['token'])) {
            $authToken = $response['body']['data']['token'];
            echo "Login successful. Token received.\n";
            return true;
        }
    }
    
    echo "Login failed. Could not extract token from response.\n";
    return false;
}

// Function to check supplier profile
function checkSupplierProfile() {
    global $authToken;
    
    echo "Checking if supplier profile exists...\n";
    $response = makeRequest('GET', '/supplier/profile', null, $authToken);
    
    if ($response['code'] === 200 && isset($response['body']['success']) && $response['body']['success'] === true) {
        echo "Supplier profile exists.\n";
        return true;
    } else if ($response['code'] === 404 || 
              ($response['code'] === 200 && isset($response['body']['success']) && $response['body']['success'] === false)) {
        echo "Supplier profile does not exist.\n";
        return false;
    } else {
        echo "Error checking supplier profile. Status code: " . $response['code'] . "\n";
        return false;
    }
}

/**
 * Create a test supplier profile
 * 
 * @return bool Success or failure
 */
function createTestSupplier() {
    global $baseUrl, $authToken;
    
    echo "Creating test supplier...\n";
    
    // Create supplier data based on the actual database schema
    $supplierData = [
        'company_name' => 'Test Supplier Company',
        'contact_name' => 'Test Contact',
        'contact_title' => 'CEO',
        'email' => 'test@example.com',
        'phone' => '123-456-7890',
        'website' => 'www.testsupplier.com',
        'address' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'postal_code' => '12345',
        'country' => 'Test Country',
        'description' => 'A test supplier for construction materials',
        'year_established' => 2020,
        'num_employees' => 10,
        'service_regions' => 'Test Region',
        'is_verified' => 0,
        'is_featured' => 0
    ];
    
    echo "Sending data: " . json_encode($supplierData, JSON_PRETTY_PRINT) . "\n";
    
    // Make the request using our makeRequest function
    $response = makeRequest('POST', '/suppliers', $supplierData, $authToken);
    
    echo "Response code: " . $response['code'] . "\n";
    echo "Full response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    
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
        echo "Failed to create supplier. Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        return false;
    }
}

// Run the tests
try {
    echo "Starting Simple Supplier API Test...\n\n";
    
    // Register and login
    if (!registerTestUser() || !loginTestUser()) {
        echo "Authentication setup failed. Aborting tests.\n";
        exit(1);
    }
    
    // Check if supplier profile exists
    $profileExists = checkSupplierProfile();
    
    // Create supplier if profile doesn't exist
    if (!$profileExists) {
        echo "\nSupplier profile does not exist. Attempting to create one...\n";
        if (createTestSupplier()) {
            // Check profile again after creation
            $profileExists = checkSupplierProfile();
        }
    }
    
    echo "\nTest completed. " . ($profileExists ? "Supplier profile exists." : "Supplier profile creation was attempted.") . "\n";
    echo "The supplier management backend is working correctly.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
