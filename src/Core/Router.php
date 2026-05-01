<?php

namespace App\Core;

/**
 * Router Class
 *
 * Handles HTTP routing and dispatching
 *
 * @package App\Core
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $groupStack = [];

    /**
     * Register a GET route
     */
    public function get(string $uri, $action): self
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route
     */
    public function post(string $uri, $action): self
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a PUT route
     */
    public function put(string $uri, $action): self
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $uri, $action): self
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a route for any HTTP method
     */
    public function any(string $uri, $action): self
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        foreach ($methods as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    /**
     * Add middleware to last added route
     */
    public function middleware($middleware): self
    {
        if (!empty($this->routes)) {
            $lastKey = array_key_last($this->routes);
            $this->routes[$lastKey]['middleware'][] = $middleware;
        }
        return $this;
    }

    /**
     * Create route group with shared attributes
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Add a route to the routing table
     */
    private function addRoute(string $method, string $uri, $action): self
    {
        // Apply group attributes
        $uri = $this->applyGroupPrefix($uri);
        $middleware = $this->getGroupMiddleware();

        // Normalize URI
        $uri = '/' . trim($uri, '/');

        // Create route pattern
        $pattern = $this->createPattern($uri);

        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => $middleware,
            'params' => $this->extractParams($uri),
        ];

        return $this;
    }

    /**
     * Apply group prefix to URI
     */
    private function applyGroupPrefix(string $uri): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return $prefix . '/' . trim($uri, '/');
    }

    /**
     * Get group middleware
     */
    private function getGroupMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array)$group['middleware']);
            }
        }
        return $middleware;
    }

    /**
     * Create regex pattern from URI
     */
    private function createPattern(string $uri): string
    {
        // Replace {param} with named capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);

        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Extract parameter names from URI
     */
    private function extractParams(string $uri): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $uri, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Dispatch the request to the appropriate route
     */
    public function dispatch(Request $request, Response $response): mixed
    {
        $method = $request->method();
        $uri = $request->uri();

        // Find matching route
        $route = $this->match($method, $uri);

        if (!$route) {
            $response->setStatusCode(404);
            $errorView = __DIR__ . '/../../views/errors/404.php';
            if (file_exists($errorView)) {
                return $response->view('errors.404', ['title' => 'Page Not Found'])->send();
            }
            return $response->setContent('<h1>404 - Page Not Found</h1><p><a href="/">Go Home</a></p>')
                ->send();
        }

        // Execute middleware
        foreach ($route['middleware'] as $middleware) {
            if (is_string($middleware)) {
                $middleware = new $middleware();
            }

            $result = $middleware->handle($request, $response);
            if ($result !== null) {
                return $result;
            }
        }

        // Dispatch to controller/action
        return $this->callAction($route, $request, $response);
    }

    /**
     * Find route matching the request
     */
    private function match(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract route parameters
                $params = [];
                foreach ($route['params'] as $param) {
                    if (isset($matches[$param])) {
                        $params[$param] = $matches[$param];
                    }
                }

                $route['matchedParams'] = $params;
                return $route;
            }
        }

        return null;
    }

    /**
     * Call the route action
     */
    private function callAction(array $route, Request $request, Response $response): mixed
    {
        $action = $route['action'];

        // Closure action
        if ($action instanceof \Closure) {
            return $action($request, $response, $route['matchedParams'] ?? []);
        }

        // Controller@method action
        if (is_string($action) && str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action);

            // Resolve controller namespace
            if (!str_contains($controller, '\\')) {
                $controller = "App\\Controllers\\{$controller}";
            }

            if (!class_exists($controller)) {
                throw new \RuntimeException("Controller not found: $controller");
            }

            $controllerInstance = new $controller($request, $response);

            if (!method_exists($controllerInstance, $method)) {
                throw new \RuntimeException("Method not found: {$controller}@{$method}");
            }

            return $controllerInstance->$method($route['matchedParams'] ?? []);
        }

        // Array action [Controller, 'method']
        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;

            if (is_string($controller)) {
                if (!class_exists($controller)) {
                    throw new \RuntimeException("Controller not found: $controller");
                }
                $controller = new $controller($request, $response);
            }

            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method not found: " . get_class($controller) . "@{$method}");
            }

            return $controller->$method($route['matchedParams'] ?? []);
        }

        throw new \RuntimeException("Invalid route action");
    }

    /**
     * Generate URL for named route
     */
    public function url(string $name, array $params = []): string
    {
        // This would require route naming - simplified for now
        // You can extend this to support named routes
        return '#';
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
