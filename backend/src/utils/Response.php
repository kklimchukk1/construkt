<?php

namespace Construkt\Utils;

class Response {
    /**
     * Send a JSON response
     * 
     * @param mixed $data The data to send
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send a success response
     * 
     * @param mixed $data The data to include
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        self::json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param mixed $errors Additional error details
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function error($message = 'An error occurred', $errors = null, $statusCode = 400) {
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::json($response, $statusCode);
    }
}
