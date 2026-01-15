<?php

declare(strict_types=1);

namespace RestApi\Http;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Factory for creating ServerRequest from PHP globals
 */
class ServerRequestFactory
{
    /**
     * Create ServerRequest from PHP globals
     */
    public static function fromGlobals(): ServerRequestInterface
    {
        return ServerRequest::fromGlobals();
    }

    /**
     * Create ServerRequest with custom data
     */
    public static function create(
        string $method,
        string $uri,
        array $serverParams = [],
        array $headers = [],
        ?string $body = null,
        string $version = '1.1',
        array $cookies = [],
        array $queryParams = [],
        array $parsedBody = [],
        array $uploadedFiles = []
    ): ServerRequestInterface {
        $request = new ServerRequest(
            $method,
            $uri,
            $headers,
            $body ? Stream::create($body) : null,
            $version,
            $serverParams
        );

        return $request
            ->withCookieParams($cookies)
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody)
            ->withUploadedFiles($uploadedFiles);
    }
}
