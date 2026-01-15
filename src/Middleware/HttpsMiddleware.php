<?php

declare(strict_types=1);

namespace RestApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RestApi\Exceptions\HttpException;
use RestApi\Exceptions\ErrorCodes;

/**
 * HTTPS Enforcement Middleware
 * 
 * Ensures all requests use HTTPS (except in development)
 */
class HttpsMiddleware
{
    private bool $enforceHttps;
    private array $allowedHosts; // For development

    public function __construct(bool $enforceHttps = true, array $allowedHosts = ['localhost', '127.0.0.1'])
    {
        $this->enforceHttps = $enforceHttps;
        $this->allowedHosts = $allowedHosts;
    }

    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        if (!$this->enforceHttps) {
            return $next($request);
        }

        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();

        // Allow localhost/127.0.0.1 in development
        if (in_array($host, $this->allowedHosts, true)) {
            return $next($request);
        }

        // Require HTTPS
        if ($scheme !== 'https') {
            throw new HttpException(
                'HTTPS is required',
                ErrorCodes::FORBIDDEN
            );
        }

        return $next($request);
    }
}
