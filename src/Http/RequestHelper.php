<?php

declare(strict_types=1);

namespace RestApi\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Helper methods for working with requests
 */
class RequestHelper
{
    /**
     * Get JSON body from request
     */
    public static function getJsonBody(ServerRequestInterface $request): ?array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (!str_contains($contentType, 'application/json')) {
            return null;
        }

        $body = (string)$request->getBody();
        
        if (empty($body)) {
            return null;
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Get query parameter
     */
    public static function getQueryParam(ServerRequestInterface $request, string $key, mixed $default = null): mixed
    {
        $queryParams = $request->getQueryParams();
        return $queryParams[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public static function getQueryParams(ServerRequestInterface $request): array
    {
        return $request->getQueryParams();
    }

    /**
     * Get parsed body (form data)
     */
    public static function getParsedBody(ServerRequestInterface $request): ?array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : null;
    }
}
