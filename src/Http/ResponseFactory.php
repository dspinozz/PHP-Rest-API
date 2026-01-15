<?php

declare(strict_types=1);

namespace RestApi\Http;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * Factory for creating HTTP responses
 */
class ResponseFactory
{
    /**
     * Create a JSON response
     */
    public function json(mixed $data, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        // Ensure data is JSON serializable
        $jsonData = self::ensureJsonSerializable($data);
        $body = json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $stream = Utils::streamFor($body);
        
        $response = new Response($statusCode, array_merge([
            'Content-Type' => 'application/json',
        ], $headers), $stream);
        
        return $response;
    }

    /**
     * Ensure data is JSON serializable
     */
    private static function ensureJsonSerializable(mixed $data): mixed
    {
        if (is_object($data)) {
            // Handle objects with toArray() method
            if (method_exists($data, 'toArray')) {
                return $data->toArray();
            }
            // Handle objects with jsonSerialize() method
            if ($data instanceof \JsonSerializable) {
                return $data->jsonSerialize();
            }
            // Convert stdClass to array
            if ($data instanceof \stdClass) {
                return (array)$data;
            }
        }
        
        if (is_array($data)) {
            return array_map(fn($item) => self::ensureJsonSerializable($item), $data);
        }
        
        return $data;
    }

    /**
     * Create a 404 Not Found response
     */
    public function notFound(?string $message = null): ResponseInterface
    {
        $data = ['error' => $message ?? 'Not Found'];
        return $this->json($data, 404);
    }

    /**
     * Create an error response
     */
    public function error(string $message, int $statusCode = 500, array $headers = []): ResponseInterface
    {
        $data = ['error' => $message];
        return $this->json($data, $statusCode, $headers);
    }

    /**
     * Create a success response
     */
    public function success(mixed $data, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        return $this->json($data, $statusCode, $headers);
    }

    /**
     * Create a plain text response
     */
    public function text(string $content, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        $stream = Stream::create($content);
        $response = new Response($statusCode, array_merge([
            'Content-Type' => 'text/plain',
        ], $headers), $stream);
        
        return $response;
    }

    /**
     * Create an empty response
     */
    public function empty(int $statusCode = 204): ResponseInterface
    {
        return new Response($statusCode);
    }
}
