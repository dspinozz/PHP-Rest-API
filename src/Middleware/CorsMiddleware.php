<?php

declare(strict_types=1);

namespace RestApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Simple CORS middleware
 */
class CorsMiddleware
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization']
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
    }

    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflight($request);
        }

        $response = $next($request);
        return $this->addCorsHeaders($request, $response);
    }

    private function handlePreflight(ServerRequestInterface $request): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        
        if (!$this->isOriginAllowed($origin)) {
            return new Response(403);
        }

        $response = new Response(204);
        return $this->addCorsHeaders($request, $response);
    }

    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        } elseif (in_array('*', $this->allowedOrigins, true)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        $response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');

        return $response;
    }

    private function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }
}
