<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Exceptions\ApiException;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Models\WebhookEvent;
use UploadThing\Utils\Serializer;
use UploadThing\Utils\WebhookVerifier;

/**
 * Webhooks resource for handling webhook events using UploadThing v6 API.
 * 
 * Note: UploadThing v6 API doesn't have traditional webhook management endpoints.
 * This resource focuses on webhook event handling and verification.
 */
final class Webhooks extends AbstractResource
{
    public function __construct(
        HttpClientInterface $httpClient,
        ApiKeyAuthenticator $authenticator,
        string $baseUrl,
        string $apiVersion,
        Serializer $serializer = new Serializer(),
    ) {
        parent::__construct($httpClient, $authenticator, $baseUrl, $apiVersion, $serializer);
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
     * Handle incoming webhook request from UploadThing.
     * This method processes the webhook payload and returns the parsed event.
     */
    public function handleWebhook(string $payload, array $headers, string $secret): WebhookEvent
    {
        // Extract signature from headers
        $signature = $this->extractSignature($headers);
        
        if (empty($signature)) {
            throw new \InvalidArgumentException('No signature found in headers');
        }

        return $this->verifyAndParse($payload, $signature, $secret);
    }

    /**
     * Handle webhook from PHP superglobals (useful for webhook endpoints).
     */
    public function handleWebhookFromGlobals(string $secret): WebhookEvent
    {
        $payload = file_get_contents('php://input');
        if ($payload === false) {
            throw new \RuntimeException('Failed to read request body');
        }

        $headers = getallheaders() ?: [];
        
        return $this->handleWebhook($payload, $headers, $secret);
    }

    /**
     * Extract signature from headers.
     */
    private function extractSignature(array $headers): string
    {
        // Look for UploadThing signature in various header formats
        $signatureHeaders = [
            'X-UploadThing-Signature',
            'x-uploadthing-signature',
            'X-UploadThing-Signature-256',
            'x-uploadthing-signature-256',
            'UploadThing-Signature',
            'uploadthing-signature'
        ];

        foreach ($signatureHeaders as $header) {
            if (isset($headers[$header])) {
                return $headers[$header];
            }
        }

        return '';
    }

    /**
     * Process file upload completion webhook.
     * This is typically called when a file upload is completed.
     */
    public function processUploadCompletion(string $fileId, array $metadata = []): void
    {
        // This would typically be handled by the serverCallback endpoint
        // but we can provide a helper method for processing completion events
        $event = new WebhookEvent(
            type: 'upload.completed',
            data: [
                'fileId' => $fileId,
                'metadata' => $metadata
            ]
        );

        // You can add custom processing logic here
        $this->onUploadCompleted($event);
    }

    /**
     * Process file deletion webhook.
     */
    public function processFileDeletion(string $fileId): void
    {
        $event = new WebhookEvent(
            type: 'file.deleted',
            data: [
                'fileId' => $fileId
            ]
        );

        $this->onFileDeleted($event);
    }

    /**
     * Process file update webhook.
     */
    public function processFileUpdate(string $fileId, array $updates): void
    {
        $event = new WebhookEvent(
            type: 'file.updated',
            data: [
                'fileId' => $fileId,
                'updates' => $updates
            ]
        );

        $this->onFileUpdated($event);
    }

    /**
     * Callback for upload completion events.
     * Override this method to handle upload completion.
     */
    protected function onUploadCompleted(WebhookEvent $event): void
    {
        // Default implementation - override in subclasses
    }

    /**
     * Callback for file deletion events.
     * Override this method to handle file deletion.
     */
    protected function onFileDeleted(WebhookEvent $event): void
    {
        // Default implementation - override in subclasses
    }

    /**
     * Callback for file update events.
     * Override this method to handle file updates.
     */
    protected function onFileUpdated(WebhookEvent $event): void
    {
        // Default implementation - override in subclasses
    }

}