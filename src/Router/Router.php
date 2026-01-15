<?php

declare(strict_types=1);

namespace RestApi\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Router for matching HTTP requests to route handlers
 */
class Router
{
    /** @var Route[] */
    private array $routes = [];

    /**
     * Add a route
     */
    public function addRoute(string $method, string $path, callable|string $handler): void
    {
        $this->routes[] = new Route($method, $path, $handler);
    }

    /**
     * Match a request to a route
     */
    public function match(ServerRequestInterface $request): ?Route
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Get all registered routes
     * 
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
