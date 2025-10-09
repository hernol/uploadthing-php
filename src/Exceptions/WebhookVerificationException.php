<?php

declare(strict_types=1);

namespace UploadThing\Exceptions;

/**
 * Exception thrown when webhook verification fails.
 */
class WebhookVerificationException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
