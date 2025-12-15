<?php

declare(strict_types=1);

namespace UploadThing;

use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Resources\Files;
use UploadThing\Resources\Uploads;
use UploadThing\Resources\Webhooks;
use UploadThing\Utils\UploadHelper;
use UploadThing\Utils\WebhookHandler;
use UploadThing\Utils\WebhookVerifier;

/**
 * Main client for interacting with the UploadThing API.
 */
final readonly class Client
{
    public function __construct(
        private Config $config,
        private HttpClientInterface $httpClient,
        private ApiKeyAuthenticator $authenticator,
    ) {
    }

    /**
     * Create a new client instance.
     */
    public static function create(Config $config): self
    {
        $httpClient = $config->getHttpClient();
        $authenticator = $config->getAuthenticator();

        return new self($config, $httpClient, $authenticator);
    }

    /**
     * Get the files resource.
     */
    public function files(): Files
    {
        return new Files($this->httpClient, $this->authenticator, $this->config->baseUrl, $this->config->apiVersion);
    }

    /**
     * Get the uploads resource.
     */
    public function uploads(): Uploads
    {
        return new Uploads(
            $this->httpClient,
            $this->authenticator,
            $this->config->baseUrl,
            $this->config->apiVersion,
            callbackUrl: $this->config->callbackUrl,
            callbackSlug: $this->config->callbackSlug,
        );
    }

    /**
     * Get the webhooks resource.
     */
    public function webhooks(): Webhooks
    {
        return new Webhooks($this->httpClient, $this->authenticator, $this->config->baseUrl, $this->config->apiVersion);
    }

    /**
     * Get the upload helper utility.
     */
    public function uploadHelper(): UploadHelper
    {
        return new UploadHelper(
            $this->files(),
            $this->uploads()
        );
    }

    /**
     * Create a webhook verifier.
     */
    public function createWebhookVerifier(string $secret, int $toleranceSeconds = 300): WebhookVerifier
    {
        return new WebhookVerifier($secret, $toleranceSeconds);
    }

    /**
     * Create a webhook handler.
     */
    public function createWebhookHandler(string $secret, int $toleranceSeconds = 300): WebhookHandler
    {
        return WebhookHandler::create($secret, $toleranceSeconds);
    }

    /**
     * Get the configuration.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get the HTTP client.
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Get the authenticator.
     */
    public function getAuthenticator(): ApiKeyAuthenticator
    {
        return $this->authenticator;
    }
}
