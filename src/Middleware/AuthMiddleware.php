<?php

declare(strict_types=1);

namespace RestApi\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RestApi\Exceptions\HttpException;
use RestApi\Http\ResponseFactory;

/**
 * JWT Authentication Middleware
 * 
 * Validates JWT tokens from Authorization header
 */
class AuthMiddleware
{
    private string $secretKey;
    private string $algorithm;
    private ResponseFactory $responseFactory;

    public function __construct(
        string $secretKey,
        string $algorithm = 'HS256',
        ?ResponseFactory $responseFactory = null
    ) {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
        $this->responseFactory = $responseFactory ?? new ResponseFactory();
    }

    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            throw new HttpException('Missing or invalid authorization token', 401);
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            // JWT::decode returns stdClass, convert to array
            $userData = json_decode(json_encode($decoded), true);
            
            if (!is_array($userData)) {
                throw new HttpException('Invalid token payload', 401);
            }
            
            // Add user data to request attributes
            $request = $request->withAttribute('user', $userData);
            
            return $next($request);
        } catch (\Exception $e) {
            throw new HttpException('Invalid or expired token', 401);
        }
    }

    /**
     * Extract JWT token from Authorization header
     */
    private function extractToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            return null;
        }

        // Support "Bearer <token>" format
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
