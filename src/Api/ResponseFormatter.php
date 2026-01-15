<?php

declare(strict_types=1);

namespace RestApi\Api;

use RestApi\Exceptions\ErrorCodes;

/**
 * Standardized API Response Formatter
 * 
 * Ensures consistent API contract across all endpoints
 */
class ResponseFormatter
{
    /**
     * Format success response
     */
    public static function success(mixed $data, int $statusCode = ErrorCodes::OK): array
    {
        return [
            'success' => true,
            'data' => $data,
            'status' => $statusCode
        ];
    }

    /**
     * Format error response
     */
    public static function error(string $message, int $statusCode = ErrorCodes::INTERNAL_SERVER_ERROR, ?array $errors = null): array
    {
        $response = [
            'success' => false,
            'error' => $message,
            'status' => $statusCode
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Format paginated response
     */
    public static function paginated(array $data, int $page, int $perPage, int $total): array
    {
        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int)ceil($total / $perPage)
            ],
            'status' => ErrorCodes::OK
        ];
    }
}
