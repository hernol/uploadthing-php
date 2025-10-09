<?php

declare(strict_types=1);

namespace UploadThing\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown when authentication fails.
 */
class AuthenticationException extends ApiException
{
    public function __construct(
        string $message = 'Authentication failed',
        int $code = 401,
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $code, $previous, $response);
    }
}
