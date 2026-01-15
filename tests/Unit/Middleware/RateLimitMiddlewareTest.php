<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Middleware;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RestApi\Exceptions\HttpException;
use RestApi\Middleware\RateLimitMiddleware;

class RateLimitMiddlewareTest extends TestCase
{
    public function testRateLimitNotExceeded(): void
    {
        $middleware = new RateLimitMiddleware(10, 60); // 10 requests per 60 seconds
        
        $request = new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        
        $callCount = 0;
        $next = function($req) use (&$callCount) {
            $callCount++;
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        // Make 5 requests (under limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $middleware($request, $next);
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        $this->assertEquals(5, $callCount);
    }

    public function testRateLimitExceeded(): void
    {
        $middleware = new RateLimitMiddleware(3, 60); // 3 requests per 60 seconds
        
        $request = new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        
        $next = function($req) {
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        // Make 3 requests (at limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $middleware($request, $next);
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        // 4th request should be rate limited
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(429);
        
        $middleware($request, $next);
    }

    public function testRateLimitByUser(): void
    {
        $middleware = new RateLimitMiddleware(5, 60);
        
        // Create request with user attribute (simulating authenticated user)
        $request = new ServerRequest('GET', '/test');
        $request = $request->withAttribute('user', ['sub' => 'user123']);
        
        $callCount = 0;
        $next = function($req) use (&$callCount) {
            $callCount++;
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        // Make requests - should be tracked by user ID
        for ($i = 0; $i < 5; $i++) {
            $response = $middleware($request, $next);
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        $this->assertEquals(5, $callCount);
        
        // 6th should be rate limited
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(429);
        
        $middleware($request, $next);
    }

    public function testRateLimitByIp(): void
    {
        $middleware = new RateLimitMiddleware(3, 60);
        
        $request1 = new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => '192.168.1.1']);
        $request2 = new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => '192.168.1.2']);
        
        $next = function($req) {
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        // IP 1 makes 3 requests
        for ($i = 0; $i < 3; $i++) {
            $response = $middleware($request1, $next);
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        // IP 2 should still be able to make requests
        $response = $middleware($request2, $next);
        $this->assertEquals(200, $response->getStatusCode());
        
        // IP 1 should now be rate limited
        $this->expectException(HttpException::class);
        $middleware($request1, $next);
    }
}
