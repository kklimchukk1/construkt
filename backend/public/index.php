<?php
/**
 * Construction Materials Marketplace API
 * Main entry point for all API requests
 */

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Manual class autoloader until Composer is set up
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'Construkt\\';
    $base_dir = SRC_PATH . '/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    // and append .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables from .env file
if (file_exists(BASE_PATH . '/.env')) {
    $env = parse_ini_file(BASE_PATH . '/.env');
    if ($env) {
        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}

// Handle CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Basic routing (will be replaced with a proper router)
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Use the Router to dispatch the request
try {
    // Include the Router class manually if not autoloaded
    if (!class_exists('\Construkt\Utils\Router')) {
        require SRC_PATH . '/utils/Router.php';
    }
    
    $router = new \Construkt\Utils\Router();
    $response = $router->dispatch($requestMethod, $requestUri);
    
    // If the response is already sent by the handler, we're done
    if (!headers_sent()) {
        // Otherwise, encode and send the response
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
} catch (\Exception $e) {
    // Log the error
    error_log($e->getMessage());
    
    // Send a 500 response with detailed error information
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'status' => 'error',
            'message' => 'Internal server error',
            'errors' => [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ]
        ], JSON_PRETTY_PRINT);
    }
}
