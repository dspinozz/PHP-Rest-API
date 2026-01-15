<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Middleware;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RestApi\Middleware\CorsMiddleware;

class CorsMiddlewareTest extends TestCase
{
    public function testCorsHeadersAdded(): void
    {
        $middleware = new CorsMiddleware();
        
        $request = new ServerRequest('GET', '/test', [
            'Origin' => 'https://example.com'
        ]);
        
        $next = function($req) {
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        $response = $middleware($request, $next);
        
        $this->assertEquals('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public function testPreflightRequest(): void
    {
        $middleware = new CorsMiddleware();
        
        $request = new ServerRequest('OPTIONS', '/test', [
            'Origin' => 'https://example.com',
            'Access-Control-Request-Method' => 'POST'
        ]);
        
        $next = function($req) {
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        $response = $middleware($request, $next);
        
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testWildcardOrigin(): void
    {
        $middleware = new CorsMiddleware(['*']);
        
        $request = new ServerRequest('GET', '/test', [
            'Origin' => 'https://any-domain.com'
        ]);
        
        $next = function($req) {
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        $response = $middleware($request, $next);
        
        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testRestrictedOrigin(): void
    {
        $middleware = new CorsMiddleware(['https://allowed.com']);
        
        $request = new ServerRequest('GET', '/test', [
            'Origin' => 'https://not-allowed.com'
        ]);
        
        $next = function($req) {
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        $response = $middleware($request, $next);
        
        // Should not have CORS headers for disallowed origin
        $this->assertEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }
}
