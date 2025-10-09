<?php

declare(strict_types=1);

namespace UploadThing\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown when rate limit is exceeded.
 */
class RateLimitException extends ApiException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
        private ?int $retryAfter = null,
    ) {
        parent::__construct($message, $code, $previous, $response);
    }

    /**
     * Get the number of seconds to wait before retrying.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
