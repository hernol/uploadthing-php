<?php

declare(strict_types=1);

namespace UploadThing;

use Psr\Log\LoggerInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Http\GuzzleHttpClient;
use UploadThing\Http\Middleware\LoggingMiddleware;
use UploadThing\Http\Middleware\RateLimitMiddleware;
use UploadThing\Http\Middleware\RetryMiddleware;

/**
 * Configuration for the UploadThing client.
 */
final readonly class Config
{
    public function __construct(
        public string $apiKey,
        public string $baseUrl = 'https://api.uploadthing.com',
        public string $apiVersion = 'v6',
        public int $timeout = 30,
        public int $maxRetries = 3,
        public float $retryDelay = 1.0,
        public string $userAgent = 'uploadthing-php/1.0.0',
        public ?LoggerInterface $logger = null,
        public ?HttpClientInterface $httpClient = null,
        public ?string $callbackUrl = null,
        public ?string $callbackSlug = null,
    ) {
    }

    /**
     * Create a new configuration instance.
     */
    public static function create(): self
    {
        return new self(apiKey: '');
    }

    /**
     * Set the API key.
     */
    public function withApiKey(string $apiKey): self
    {
        return new self(
            apiKey: $apiKey,
            baseUrl: $this->baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            userAgent: $this->userAgent,
            logger: $this->logger,
            httpClient: $this->httpClient,
            callbackUrl: $this->callbackUrl,
            callbackSlug: $this->callbackSlug,
        );
    }

    /**
     * Set the API key from an environment variable.
     */
    public function withApiKeyFromEnv(string $envVar = 'UPLOADTHING_API_KEY'): self
    {
        $apiKey = $_ENV[$envVar] ?? getenv($envVar) ?: '';

        if (empty($apiKey)) {
            throw new \InvalidArgumentException("Environment variable '{$envVar}' is not set or empty");
        }

        return $this->withApiKey($apiKey);
    }

    /**
     * Set the base URL.
     */
    public function withBaseUrl(string $baseUrl): self
    {
        return new self(
            apiKey: $this->apiKey,
            baseUrl: $baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            userAgent: $this->userAgent,
            logger: $this->logger,
            httpClient: $this->httpClient,
            callbackUrl: $this->callbackUrl,
            callbackSlug: $this->callbackSlug,
        );
    }

    /**
     * Set the timeout in seconds.
     */
    public function withTimeout(int $timeout): self
    {
        return new self(
            apiKey: $this->apiKey,
            baseUrl: $this->baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            userAgent: $this->userAgent,
            logger: $this->logger,
            httpClient: $this->httpClient,
            callbackUrl: $this->callbackUrl,
            callbackSlug: $this->callbackSlug,
        );
    }

    /**
     * Set the retry policy.
     */
    public function withRetryPolicy(int $maxRetries, float $retryDelay = 1.0): self
    {
        return new self(
            apiKey: $this->apiKey,
            baseUrl: $this->baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $this->timeout,
            maxRetries: $maxRetries,
            retryDelay: $retryDelay,
            userAgent: $this->userAgent,
            logger: $this->logger,
            httpClient: $this->httpClient,
            callbackUrl: $this->callbackUrl,
            callbackSlug: $this->callbackSlug,
        );
    }

    /**
     * Set the user agent.
     */
    public function withUserAgent(string $userAgent): self
    {
        return new self(
            apiKey: $this->apiKey,
            baseUrl: $this->baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            userAgent: $userAgent,
            logger: $this->logger,
            httpClient: $this->httpClient,
            callbackUrl: $this->callbackUrl,
            callbackSlug: $this->callbackSlug,
        );
    }

    /**
     * Set the logger.
     */
    public function withLogger(LoggerInterface $logger): self
    {
        return new self(
            apiKey: $this->apiKey,
            baseUrl: $this->baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            userAgent: $this->userAgent,
            logger: $logger,
            httpClient: $this->httpClient,
            callbackUrl: $this->callbackUrl,
            callbackSlug: $this->callbackSlug,
        );
    }

    /**
     * Set a custom HTTP client.
     */
    public function withHttpClient(HttpClientInterface $httpClient): self
    {
        return new self(
            apiKey: $this->apiKey,
            baseUrl: $this->baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            userAgent: $this->userAgent,
            logger: $this->logger,
            httpClient: $httpClient,
            callbackUrl: $this->callbackUrl,
            callbackSlug: $this->callbackSlug,
        );
    }

    /**
     * Set the server callback configuration used for presigned uploads.
     */
    public function withServerCallback(string $callbackUrl, string $callbackSlug): self
    {
        return new self(
            apiKey: $this->apiKey,
            baseUrl: $this->baseUrl,
            apiVersion: $this->apiVersion,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            userAgent: $this->userAgent,
            logger: $this->logger,
            httpClient: $this->httpClient,
            callbackUrl: $callbackUrl,
            callbackSlug: $callbackSlug,
        );
    }

    /**
     * Get the authenticator instance.
     */
    public function getAuthenticator(): ApiKeyAuthenticator
    {
        return new ApiKeyAuthenticator($this->apiKey);
    }

    /**
     * Get the API version.
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * Get the HTTP client instance.
     */
    public function getHttpClient(): HttpClientInterface
    {
        if ($this->httpClient !== null) {
            return $this->httpClient;
        }

        $client = GuzzleHttpClient::create($this->timeout, $this->userAgent);

        // Add middleware
        $client = new RetryMiddleware($client, $this->maxRetries, $this->retryDelay);
        $client = new RateLimitMiddleware($client);

        if ($this->logger !== null) {
            $client = new LoggingMiddleware($client, $this->logger);
        }

        return $client;
    }
}
