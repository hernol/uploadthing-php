<?php

declare(strict_types=1);

namespace UploadThing\Auth;

use Psr\Http\Message\RequestInterface;

/**
 * API key authenticator for UploadThing API.
 */
final readonly class ApiKeyAuthenticator
{
    public function __construct(
        private string $apiKey,
    ) {
    }

    /**
     * Authenticate a request by adding the API key header.
     */
    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->apiKey);
    }

    /**
     * Get the API key.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
