<?php
// src/Core/Router.php - Updated for new file structure

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
        
        // Determine handler directory based on route type  
        if ($route['type'] === 'api') {
            $handlerFile = __DIR__ . "/../Api/OpenAI/{$handlerName}.php";
        } else {
            $handlerFile = __DIR__ . "/../Web/Controllers/{$handlerName}.php";
        }
        
        if (!file_exists($handlerFile)) {
            $this->handle404("Handler file not found: {$handlerFile}");
            return;
        }
        
        require_once $handlerFile;
        
        if (!class_exists($handlerName)) {
            $this->handle404("Handler class not found: {$handlerName}");
            return;
        }
        
        $handler = new $handlerName();
        
        if (!method_exists($handler, $methodName)) {
            $this->handle404("Method not found: {$handlerName}@{$methodName}");
            return;
        }
        
        // For API routes, wrap in try-catch to ensure clean JSON responses
        if ($route['type'] === 'api') {
            try {
                // Call method with route parameters
                if (!empty($this->routeParameters)) {
                    call_user_func_array([$handler, $methodName], $this->routeParameters);
                } else {
                    $handler->$methodName();
                }
            } catch (Exception $e) {
                // Clean any output buffer and return JSON error
                if (ob_get_level()) {
                    ob_clean();
                }
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
                exit;
            }
        } else {
            // Call method with route parameters for web routes
            if (!empty($this->routeParameters)) {
                call_user_func_array([$handler, $methodName], $this->routeParameters);
            } else {
                $handler->$methodName();
            }
        }
    }
    
    private function handle404($message = "Page not found") {
        http_response_code(404);
        
        // Check if this is an API request
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (strpos($path, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not found']);
        } else {
            // Load 404 view - UPDATED PATH
            include __DIR__ . '/../Web/Views/error.php';
        }
        exit;
    }
}