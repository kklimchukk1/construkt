<?php

namespace Construkt\Utils;

/**
 * Simple Router class for API endpoints
 * 
 * This is a temporary implementation until Composer is installed with FastRoute
 */
class Router {
    private $routes = [];
    
    public function __construct() {
        // Load routes from routes.php
        $this->routes = require SRC_PATH . '/routes.php';
    }
    
    public function dispatch($httpMethod, $uri) {
        // Strip query string and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        
        // Simple routing implementation
        $routeKey = $httpMethod . ' ' . $uri;
        
        // Check for exact route match
        if (isset($this->routes[$routeKey]) && is_callable($this->routes[$routeKey])) {
            return $this->routes[$routeKey]([]);
        }
        
        // Check for simple pattern matches (e.g., /api/products/{id})
        foreach ($this->routes as $route => $handler) {
            if ($route === '404' || !is_callable($handler)) continue;
            
            list($routeMethod, $routePath) = explode(' ', $route, 2);
            
            if ($routeMethod !== $httpMethod) continue;
            
            // Extract parameter names from route pattern
            $paramNames = [];
            preg_match_all('/{([^}]+)}/', $routePath, $paramMatches);
            if (!empty($paramMatches[1])) {
                $paramNames = $paramMatches[1];
            }
            
            // Convert route pattern to regex
            $pattern = preg_replace('/{([^}]+)}/', '([^/]+)', $routePath);
            $pattern = '@^' . $pattern . '$@D';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove the full match
                
                // Create named parameters array
                $params = [];
                foreach ($paramNames as $index => $name) {
                    if (isset($matches[$index])) {
                        $params[$name] = $matches[$index];
                    }
                }
                
                return $handler($params);
            }
        }
        
        // No route found, use 404 handler
        if (isset($this->routes['404']) && is_callable($this->routes['404'])) {
            return $this->routes['404']();
        }
        
        // Fallback if no 404 handler
        header('HTTP/1.1 404 Not Found');
        return [
            'status' => 'error',
            'message' => 'Endpoint not found'
        ];
    }
}
