<?php

declare(strict_types=1);

namespace RestApi\Router;

/**
 * Represents a single route
 */
class Route
{
    private string $method;
    private string $path;
    private mixed $handler;
    private array $params = [];

    public function __construct(string $method, string $path, mixed $handler)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * Check if route matches the given method and path
     */
    public function matches(string $method, string $path): bool
    {
        if (strtoupper($method) !== $this->method) {
            return false;
        }

        return $this->matchPath($path);
    }

    /**
     * Match path with parameter extraction
     */
    private function matchPath(string $path): bool
    {
        $this->params = [];

        // Simple exact match first
        if ($this->path === $path) {
            return true;
        }

        // Check if route has parameters
        if (strpos($this->path, '{') === false) {
            return false;
        }

        // Convert route pattern to regex
        $pattern = $this->convertToRegex($this->path);
        
        if (preg_match($pattern, $path, $matches)) {
            // Extract named parameters
            $this->extractParams($matches);
            return true;
        }

        return false;
    }

    /**
     * Convert route path to regex pattern
     * 
     * Handles paths like /users/{id} -> /users/([^/]+)
     */
    private function convertToRegex(string $path): string
    {
        // Validate path format
        if (empty($path) || $path[0] !== '/') {
            throw new \InvalidArgumentException('Route path must start with /');
        }

        // Replace parameters with placeholders temporarily
        $tempPath = preg_replace('/\{[a-zA-Z0-9_]+\}/', '___PARAM___', $path);
        
        // Quote the path for regex using # as delimiter (avoids escaping /)
        $quotedPath = preg_quote($tempPath, '#');
        
        // Replace the placeholders with the actual regex pattern
        $pattern = str_replace('___PARAM___', '([^/]+)', $quotedPath);

        return '#^' . $pattern . '$#';
    }

    /**
     * Extract parameters from matched path
     */
    private function extractParams(array $matches): void
    {
        // Extract named parameters from route path
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $this->path, $paramNames);
        
        if (!empty($paramNames[1])) {
            foreach ($paramNames[1] as $index => $paramName) {
                $this->params[$paramName] = $matches[$index + 1] ?? null;
            }
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
