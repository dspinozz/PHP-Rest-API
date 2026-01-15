<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RestApi\Exceptions\HttpException;
use RestApi\Middleware\AuthMiddleware;
use RestApi\Security\JwtService;

class AuthMiddlewareTest extends TestCase
{
    private string $secretKey;
    private AuthMiddleware $middleware;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        $this->secretKey = 'test-secret-key-for-auth-middleware-testing';
        $this->middleware = new AuthMiddleware($this->secretKey);
        $this->jwtService = new JwtService($this->secretKey);
    }

    public function testValidToken(): void
    {
        // Generate real JWT token
        $token = $this->jwtService->generateAccessToken(['sub' => '123', 'email' => 'test@example.com']);
        
        $request = new ServerRequest('GET', '/protected', [
            'Authorization' => 'Bearer ' . $token
        ]);
        
        $called = false;
        $next = function($req) use (&$called) {
            $called = true;
            $user = $req->getAttribute('user');
            $this->assertIsArray($user);
            $this->assertEquals('123', $user['sub']);
            return new \GuzzleHttp\Psr7\Response(200);
        };
        
        $response = $this->middleware($request, $next);
        
        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMissingToken(): void
    {
        $request = new ServerRequest('GET', '/protected');
        
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        
        $this->middleware($request, fn($req) => new \GuzzleHttp\Psr7\Response(200));
    }

    public function testInvalidToken(): void
    {
        $request = new ServerRequest('GET', '/protected', [
            'Authorization' => 'Bearer invalid-token-here'
        ]);
        
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        
        $this->middleware($request, fn($req) => new \GuzzleHttp\Psr7\Response(200));
    }

    public function testExpiredToken(): void
    {
        // Create token with immediate expiration
        $payload = ['sub' => '123', 'exp' => time() - 1, 'iat' => time() - 2];
        $token = JWT::encode($payload, $this->secretKey, 'HS256');
        
        $request = new ServerRequest('GET', '/protected', [
            'Authorization' => 'Bearer ' . $token
        ]);
        
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        
        $this->middleware($request, fn($req) => new \GuzzleHttp\Psr7\Response(200));
    }

    public function testTokenWithWrongSecret(): void
    {
        // Generate token with different secret
        $wrongService = new JwtService('different-secret-key');
        $token = $wrongService->generateAccessToken(['sub' => '123']);
        
        $request = new ServerRequest('GET', '/protected', [
            'Authorization' => 'Bearer ' . $token
        ]);
        
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        
        $this->middleware($request, fn($req) => new \GuzzleHttp\Psr7\Response(200));
    }

    public function testBearerTokenFormat(): void
    {
        $token = $this->jwtService->generateAccessToken(['sub' => '123']);
        
        // Test different Authorization header formats
        $formats = [
            'Bearer ' . $token,
            'bearer ' . $token,
            'BEARER ' . $token,
        ];
        
        foreach ($formats as $authHeader) {
            $request = new ServerRequest('GET', '/protected', [
                'Authorization' => $authHeader
            ]);
            
            $called = false;
            $next = function($req) use (&$called) {
                $called = true;
                return new \GuzzleHttp\Psr7\Response(200);
            };
            
            $response = $this->middleware($request, $next);
            $this->assertTrue($called, "Failed for format: $authHeader");
        }
    }
}
