<?php

declare(strict_types=1);

namespace UploadThing\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Base exception for UploadThing API errors.
 */
class ApiException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private ?ResponseInterface $response = null,
        private ?string $errorCode = null,
        private ?array $errorDetails = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP response that caused this exception.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the error code from the API response.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get additional error details from the API response.
     * @return array|null
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    /**
     * Create an API exception from an HTTP response.
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if (!is_array($data)) {
            return new self(
                message: 'API request failed',
                code: $response->getStatusCode(),
                response: $response,
            );
        }
        
        $errorData = $data['error'] ?? [];
        $message = is_string($errorData['message'] ?? null) ? $errorData['message'] : 'API request failed';
        $errorCode = is_string($errorData['code'] ?? null) ? $errorData['code'] : null;
        $errorDetails = is_array($errorData['details'] ?? null) ? $errorData['details'] : null;

        return new self(
            message: $message,
            code: $response->getStatusCode(),
            response: $response,
            errorCode: $errorCode,
            errorDetails: $errorDetails,
        );
    }
}
