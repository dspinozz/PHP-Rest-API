<?php

declare(strict_types=1);

namespace RestApi\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT Service for token generation and validation
 */
class JwtService
{
    private string $secretKey;
    private string $algorithm;
    private int $accessTokenExpiry; // seconds
    private int $refreshTokenExpiry; // seconds

    public function __construct(
        string $secretKey,
        string $algorithm = 'HS256',
        int $accessTokenExpiry = 3600, // 1 hour
        int $refreshTokenExpiry = 604800 // 7 days
    ) {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
        $this->accessTokenExpiry = $accessTokenExpiry;
        $this->refreshTokenExpiry = $refreshTokenExpiry;
    }

    /**
     * Generate access token
     */
    public function generateAccessToken(array $payload): string
    {
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $this->accessTokenExpiry;
        $payload['type'] = 'access';

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken(array $payload): string
    {
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $this->refreshTokenExpiry;
        $payload['type'] = 'refresh';

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate and decode token
     */
    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            // JWT::decode returns stdClass, convert to array
            $data = json_decode(json_encode($decoded), true);
            
            if (!is_array($data)) {
                throw new \RuntimeException('Invalid token payload format');
            }
            
            return $data;
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $token): bool
    {
        try {
            $decoded = $this->validateToken($token);
            return isset($decoded['exp']) && $decoded['exp'] < time();
        } catch (\Exception $e) {
            return true;
        }
    }
}
