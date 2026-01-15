<?php

declare(strict_types=1);

namespace RestApi\Exceptions;

/**
 * HTTP exception for client errors (4xx)
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
