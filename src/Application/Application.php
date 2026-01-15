<?php

declare(strict_types=1);

namespace RestApi\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RestApi\Router\Router;
use RestApi\Http\ResponseFactory;
use RestApi\Http\ServerRequestFactory;
use RestApi\Exceptions\HttpException;

/**
 * Main application class
 * 
 * This is the entry point for the REST API framework.
 * It handles request routing, middleware execution, and response generation.
 */
class Application implements RequestHandlerInterface
{
    private Router $router;
    private ResponseFactory $responseFactory;
    private array $middleware = [];

    public function __construct(?Router $router = null, ?ResponseFactory $responseFactory = null)
    {
        $this->router = $router ?? new Router();
        $this->responseFactory = $responseFactory ?? new ResponseFactory();
    }

    /**
     * Register a GET route
     */
    public function get(string $path, callable|string $handler): void
    {
        $this->router->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable|string $handler): void
    {
        $this->router->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable|string $handler): void
    {
        $this->router->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable|string $handler): void
    {
        $this->router->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $path, callable|string $handler): void
    {
        $this->router->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register an OPTIONS route
     */
    public function options(string $path, callable|string $handler): void
    {
        $this->router->addRoute('OPTIONS', $path, $handler);
    }

    /**
     * Register middleware
     */
    public function middleware(callable|string $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Handle incoming request
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Execute middleware pipeline
            $response = $this->executeMiddleware($request);
            
            if ($response !== null) {
                return $response;
            }

            // Route the request
            $route = $this->router->match($request);
            
            if ($route === null) {
                return $this->responseFactory->notFound('Route not found');
            }

            // Execute route handler
            $handler = $route->getHandler();
            $result = $this->executeHandler($handler, $request, $route->getParams());

            // Format response
            return $this->formatResponse($result);

        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Run the application
     */
    public function run(?ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            $request = $this->createRequestFromGlobals();
        }

        $response = $this->handle($request);
        $this->sendResponse($response);
    }

    /**
     * Execute middleware pipeline
     */
    private function executeMiddleware(ServerRequestInterface $request): ?ResponseInterface
    {
        if (empty($this->middleware)) {
            return null;
        }

        foreach ($this->middleware as $middleware) {
            if (is_callable($middleware)) {
                $next = function(ServerRequestInterface $req): ResponseInterface {
                    return $this->handle($req);
                };
                
                $result = $middleware($request, $next);
                
                if ($result instanceof ResponseInterface) {
                    return $result;
                }
                
                // If middleware returns a request, use it for next middleware
                if ($result instanceof ServerRequestInterface) {
                    $request = $result;
                }
            }
        }

        return null;
    }

    /**
     * Execute route handler
     */
    private function executeHandler(callable|string $handler, ServerRequestInterface $request, array $params): mixed
    {
        if (is_callable($handler)) {
            return $handler($request, $params);
        }

        // TODO: Handle class-based controllers
        throw new \RuntimeException('Class-based controllers not yet implemented');
    }

    /**
     * Format handler result as response
     */
    private function formatResponse(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        // Default to JSON response
        return $this->responseFactory->json($result);
    }

    /**
     * Handle exceptions
     */
    private function handleException(\Throwable $e): ResponseInterface
    {
        // Handle HTTP exceptions (client errors) - show message
        if ($e instanceof \RestApi\Exceptions\HttpException) {
            return $this->responseFactory->error($e->getMessage(), $e->getCode());
        }

        // Don't expose internal errors - generic message only
        $message = 'Internal Server Error';
        $code = 500;

        // Log the actual error (in production, use proper logging)
        error_log(sprintf(
            'Unhandled exception: %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        return $this->responseFactory->error($message, $code);
    }

    /**
     * Create request from PHP globals
     */
    private function createRequestFromGlobals(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals();
    }

    /**
     * Send response to client
     */
    private function sendResponse(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
        
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        echo $response->getBody()->__toString();
    }
}
