<?php

declare(strict_types=1);

namespace UploadThing\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Http\HttpClientInterface;

/**
 * Retry middleware with exponential backoff.
 */
final class RetryMiddleware implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private int $maxRetries = 3,
        private float $baseDelay = 1.0,
    ) {
    }

    /**
     * Send a request with retry logic.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $this->httpClient->sendRequest($request);

                // Don't retry on success or client errors (4xx)
                if ($response->getStatusCode() < 500) {
                    return $response;
                }

                // Retry on server errors (5xx) if we have attempts left
                if ($attempt < $this->maxRetries) {
                    $delay = $this->baseDelay * (2 ** $attempt);
                    usleep((int) ($delay * 1_000_000));
                    continue;
                }

                return $response;
            } catch (\Exception $e) {
                $lastException = $e;

                // Don't retry on client errors (4xx)
                if ($e instanceof \HttpException && $e->getCode() < 500) {
                    throw $e;
                }

                // Retry on server errors (5xx) or network errors
                if ($attempt < $this->maxRetries) {
                    $delay = $this->baseDelay * (2 ** $attempt);
                    usleep((int) ($delay * 1_000_000));
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException ?? new \RuntimeException('Request failed after all retries');
    }
}
