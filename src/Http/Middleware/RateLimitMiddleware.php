<?php

declare(strict_types=1);

namespace UploadThing\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Http\HttpClientInterface;

/**
 * Rate limit middleware.
 *
 * TODO: Implement rate limiting based on API headers when spec is available.
 */
final class RateLimitMiddleware implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Send a request with rate limiting.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // TODO: Implement rate limiting based on API response headers
        // For now, just pass through to the underlying client
        return $this->httpClient->sendRequest($request);
    }
}
