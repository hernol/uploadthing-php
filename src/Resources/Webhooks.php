<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Exceptions\ApiException;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Models\CreateWebhookRequest;
use UploadThing\Models\UpdateWebhookRequest;
use UploadThing\Models\Webhook;
use UploadThing\Models\WebhookEvent;
use UploadThing\Models\WebhookListResponse;
use UploadThing\Utils\Serializer;
use UploadThing\Utils\WebhookVerifier;

/**
 * Webhooks resource for managing webhook configurations.
 */
final class Webhooks
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ApiKeyAuthenticator $authenticator,
        private string $baseUrl,
        private Serializer $serializer = new Serializer(),
    ) {
    }

    /**
     * List all webhooks.
     */
    public function listWebhooks(int $limit = 50, ?string $cursor = null): WebhookListResponse
    {
        $queryParams = ['limit' => $limit];
        if ($cursor !== null) {
            $queryParams['cursor'] = $cursor;
        }

        $request = $this->createRequest('GET', '/webhooks', queryParams: $queryParams);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), WebhookListResponse::class);
    }

    /**
     * Create a new webhook.
     */
    public function createWebhook(string $url, array $events = []): Webhook
    {
        $requestBody = new CreateWebhookRequest($url, $events);

        $request = $this->createRequest('POST', '/webhooks', body: $requestBody);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), Webhook::class);
    }

    /**
     * Get a specific webhook by ID.
     */
    public function getWebhook(string $webhookId): Webhook
    {
        $request = $this->createRequest('GET', "/webhooks/{$webhookId}");
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), Webhook::class);
    }

    /**
     * Update a webhook.
     */
    public function updateWebhook(string $webhookId, ?string $url = null, ?array $events = null): Webhook
    {
        $requestBody = new UpdateWebhookRequest($url, $events);

        $request = $this->createRequest('PUT', "/webhooks/{$webhookId}", body: $requestBody);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), Webhook::class);
    }

    /**
     * Delete a webhook.
     */
    public function deleteWebhook(string $webhookId): void
    {
        $request = $this->createRequest('DELETE', "/webhooks/{$webhookId}");
        $this->sendRequest($request);
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $verifier = new WebhookVerifier($secret);
        
        // Extract headers from signature (assuming signature is in format "t=timestamp,v1=signature")
        $headers = [];
        if (str_contains($signature, ',')) {
            $parts = explode(',', $signature);
            foreach ($parts as $part) {
                if (str_contains($part, '=')) {
                    [$key, $value] = explode('=', $part, 2);
                    $headers['X-UploadThing-' . ucfirst($key)] = $value;
                }
            }
        } else {
            // Simple signature format
            $headers['X-UploadThing-Signature'] = $signature;
        }

        return $verifier->verify($payload, $headers);
    }

    /**
     * Verify webhook signature and parse payload.
     */
    public function verifyAndParse(string $payload, string $signature, string $secret): WebhookEvent
    {
        $verifier = new WebhookVerifier($secret);
        
        // Extract headers from signature
        $headers = [];
        if (str_contains($signature, ',')) {
            $parts = explode(',', $signature);
            foreach ($parts as $part) {
                if (str_contains($part, '=')) {
                    [$key, $value] = explode('=', $part, 2);
                    $headers['X-UploadThing-' . ucfirst($key)] = $value;
                }
            }
        } else {
            // Simple signature format
            $headers['X-UploadThing-Signature'] = $signature;
        }

        return $verifier->verifyAndParse($payload, $headers);
    }

    /**
     * Create a webhook verifier instance.
     */
    public function createVerifier(string $secret, int $toleranceSeconds = 300): WebhookVerifier
    {
        return new WebhookVerifier($secret, $toleranceSeconds);
    }

    /**
     * Parse webhook payload without verification.
     */
    public function parsePayload(string $payload): WebhookEvent
    {
        $verifier = new WebhookVerifier('dummy-secret');
        return $verifier->parsePayload($payload);
    }

    /**
     * Create a PSR-7 request.
     */
    private function createRequest(
        string $method,
        string $path,
        array $queryParams = [],
        ?object $body = null,
    ): RequestInterface {
        $uri = $this->baseUrl . $path;

        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        $request = new \GuzzleHttp\Psr7\Request($method, $uri);

        if ($body !== null) {
            $request = $request->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor($this->serializer->serialize($body))
            );
        }

        return $this->authenticator->authenticate($request);
    }

    /**
     * Send a request and handle the response.
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }
}
