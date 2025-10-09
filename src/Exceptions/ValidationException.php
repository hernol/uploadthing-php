<?php

declare(strict_types=1);

namespace UploadThing\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown when request validation fails.
 */
class ValidationException extends ApiException
{
    public function __construct(
        string $message = 'Request validation failed',
        int $code = 400,
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
        private ?array $validationErrors = null,
    ) {
        parent::__construct($message, $code, $previous, $response);
    }

    /**
     * Get the validation errors.
     * @return array|null
     */
    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }
}
