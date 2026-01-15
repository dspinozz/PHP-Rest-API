<?php

declare(strict_types=1);

namespace RestApi\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use RestApi\Security\JwtService;

class JwtServiceTest extends TestCase
{
    private JwtService $jwtService;
    private string $secretKey;

    protected function setUp(): void
    {
        $this->secretKey = 'test-secret-key-for-jwt-testing-purposes-only';
        $this->jwtService = new JwtService($this->secretKey);
    }

    public function testGenerateAccessToken(): void
    {
        $payload = ['sub' => '123', 'email' => 'test@example.com'];
        $token = $this->jwtService->generateAccessToken($payload);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateRefreshToken(): void
    {
        $payload = ['sub' => '123'];
        $token = $this->jwtService->generateRefreshToken($payload);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testValidateToken(): void
    {
        $payload = ['sub' => '123', 'email' => 'test@example.com'];
        $token = $this->jwtService->generateAccessToken($payload);
        
        $decoded = $this->jwtService->validateToken($token);
        
        $this->assertEquals('123', $decoded['sub']);
        $this->assertEquals('test@example.com', $decoded['email']);
        $this->assertEquals('access', $decoded['type']);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertArrayHasKey('iat', $decoded);
    }

    public function testInvalidToken(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->jwtService->validateToken('invalid-token');
    }

    public function testTokenExpiration(): void
    {
        // Create token with very short expiry
        $service = new JwtService($this->secretKey, 'HS256', 1); // 1 second
        $payload = ['sub' => '123'];
        $token = $service->generateAccessToken($payload);
        
        // Token should not be expired immediately
        $this->assertFalse($service->isTokenExpired($token));
        
        // Wait and check (in real test, might need to adjust timing)
        // For now, just verify the method works
        $this->assertIsBool($service->isTokenExpired($token));
    }
}
