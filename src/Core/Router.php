<?php
// src/Core/Router.php - FIXED to handle WhatsApp controllers

class Router {
    private $routes = [];
    private $routeParameters = [];
    
    public function addWebRoute($method, $path, $handler) {
        $this->routes[] = [
            'type' => 'web',
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function addApiRoute($method, $path, $handler) {
        $this->routes[] = [
            'type' => 'api',
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash except for root
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                $this->callHandler($route);
                return;
            }
        }
        
        $this->handle404();
    }
    
    private function matchRoute($route, $method, $path) {
        // Check if method matches
        if ($route['method'] !== $method) {
            return false;
        }
        
        // Handle routes with parameters like /api/threads/{id}/messages
        $routePath = $route['path'];
        
        // If no parameters, do exact match
        if (strpos($routePath, '{') === false) {
            return $routePath === $path;
        }
        
        // Convert route pattern to regex
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $path, $matches)) {
            // Store parameters for later use
            $this->routeParameters = array_slice($matches, 1);
            return true;
        }
        
        return false;
    }
    
    private function callHandler($route) {
        list($handlerName, $methodName) = explode('@', $route['handler']);
        
        // FIXED: Determine handler directory based on route type AND handler name
        if ($route['type'] === 'api') {
            // Check if it's a WhatsApp/Instance controller (should be in Web/Controllers)
            if (strpos($handlerName, 'WhatsApp') !== false || strpos($handlerName, 'Instance') !== false) {
                $handlerFile = __DIR__ . "/../Web/Controllers/{$handlerName}.php";
            } else {
                // Other API controllers are in Api/OpenAI
                $handlerFile = __DIR__ . "/../Api/OpenAI/{$handlerName}.php";
            }
        } else {
            // Web controllers are in Web/Controllers
            $handlerFile = __DIR__ . "/../Web/Controllers/{$handlerName}.php";
        }
        
        if (!file_exists($handlerFile)) {
            error_log("Router: Handler file not found: {$handlerFile}");
            $this->handle404();
            return;
        }
        
        require_once $handlerFile;
        
        if (!class_exists($handlerName)) {
            error_log("Router: Handler class not found: {$handlerName}");
            $this->handle404();
            return;
        }
        
        $handler = new $handlerName();
        
        if (!method_exists($handler, $methodName)) {
            error_log("Router: Handler method not found: {$handlerName}::{$methodName}");
            $this->handle404();
            return;
        }
        
        // Pass route parameters to the method if available
        if (!empty($this->routeParameters)) {
            call_user_func_array([$handler, $methodName], $this->routeParameters);
        } else {
            $handler->$methodName();
        }
    }
    
    private function handle404() {
        $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
        
        if ($isApiRequest) {
            // Clean any output and return JSON error for API requests
            if (ob_get_level()) {
                ob_clean();
            }
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Not found',
                'message' => 'API endpoint not found'
            ]);
        } else {
            // Load 404 error page for web requests
            http_response_code(404);
            
            $errorViewFile = __DIR__ . '/../Web/Views/error.php';
            if (file_exists($errorViewFile)) {
                include $errorViewFile;
            } else {
                echo '<h1>404 - Page Not Found</h1>';
            }
        }
    }
    
    /**
     * Get route parameters for the current matched route
     */
    public function getRouteParameters() {
        return $this->routeParameters;
    }
}