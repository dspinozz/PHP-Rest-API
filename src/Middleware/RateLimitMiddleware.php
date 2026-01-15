<?php

declare(strict_types=1);

namespace RestApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RestApi\Exceptions\HttpException;

/**
 * Simple Rate Limiting Middleware
 * 
 * Uses in-memory storage (for single server)
 * For production with multiple servers, use Redis or database
 */
class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowSeconds;
    private array $requestCounts = [];

    public function __construct(int $maxRequests = 100, int $windowSeconds = 3600)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $identifier = $this->getIdentifier($request);
        $now = time();

        // Clean old entries
        $this->cleanOldEntries($now);

        // Check rate limit
        if (!isset($this->requestCounts[$identifier])) {
            $this->requestCounts[$identifier] = [];
        }

        // Count requests in current window
        $recentRequests = array_filter(
            $this->requestCounts[$identifier],
            fn($timestamp) => $timestamp > ($now - $this->windowSeconds)
        );

        if (count($recentRequests) >= $this->maxRequests) {
            throw new HttpException(
                'Rate limit exceeded. Maximum ' . $this->maxRequests . ' requests per ' . $this->windowSeconds . ' seconds',
                429
            );
        }

        // Record this request
        $this->requestCounts[$identifier][] = $now;

        return $next($request);
    }

    /**
     * Get identifier for rate limiting (IP address or user ID)
     */
    private function getIdentifier(ServerRequestInterface $request): string
    {
        // Try to get user ID from request attributes (if authenticated)
        $user = $request->getAttribute('user');
        if (is_array($user) && isset($user['sub'])) {
            $userId = $user['sub'];
            return 'user:' . (is_string($userId) || is_int($userId) ? (string)$userId : 'unknown');
        }

        // Fall back to IP address
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        
        // Handle forwarded IP (from proxy/load balancer)
        if (isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwarded[0]);
        }

        return 'ip:' . $ip;
    }

    /**
     * Clean old entries to prevent memory growth
     */
    private function cleanOldEntries(int $now): void
    {
        $cutoff = $now - $this->windowSeconds;
        
        foreach ($this->requestCounts as $identifier => $timestamps) {
            $this->requestCounts[$identifier] = array_filter(
                $timestamps,
                fn($timestamp) => $timestamp > $cutoff
            );

            // Remove empty entries
            if (empty($this->requestCounts[$identifier])) {
                unset($this->requestCounts[$identifier]);
            }
        }
    }
}
