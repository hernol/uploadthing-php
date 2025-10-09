<?php

declare(strict_types=1);

namespace UploadThing\Utils;

use UploadThing\Exceptions\WebhookVerificationException;
use UploadThing\Models\WebhookEvent;

/**
 * Webhook signature verification utility.
 */
final class WebhookVerifier
{
    private const DEFAULT_TOLERANCE_SECONDS = 300; // 5 minutes
    private const SIGNATURE_HEADER = 'X-UploadThing-Signature';
    private const TIMESTAMP_HEADER = 'X-UploadThing-Timestamp';

    public function __construct(
        private string $secret,
        private int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ) {
    }

    /**
     * Verify webhook signature and timestamp.
     */
    public function verify(
        string $payload,
        array $headers,
        ?int $timestamp = null
    ): bool {
        try {
            // Extract signature from headers
            $signature = $this->extractSignature($headers);
            if ($signature === null) {
                throw new WebhookVerificationException('Missing signature header');
            }

            // Extract timestamp from headers or use provided timestamp
            $webhookTimestamp = $timestamp ?? $this->extractTimestamp($headers);
            if ($webhookTimestamp === null) {
                throw new WebhookVerificationException('Missing timestamp');
            }

            // Verify timestamp tolerance
            $this->verifyTimestamp($webhookTimestamp);

            // Verify signature
            $this->verifySignature($payload, $signature, $webhookTimestamp);

            return true;
        } catch (WebhookVerificationException $e) {
            return false;
        }
    }

    /**
     * Verify webhook signature and timestamp, throwing exceptions on failure.
     */
    public function verifyOrThrow(
        string $payload,
        array $headers,
        ?int $timestamp = null
    ): void {
        // Extract signature from headers
        $signature = $this->extractSignature($headers);
        if ($signature === null) {
            throw new WebhookVerificationException('Missing signature header');
        }

        // Extract timestamp from headers or use provided timestamp
        $webhookTimestamp = $timestamp ?? $this->extractTimestamp($headers);
        if ($webhookTimestamp === null) {
            throw new WebhookVerificationException('Missing timestamp');
        }

        // Verify timestamp tolerance
        $this->verifyTimestamp($webhookTimestamp);

        // Verify signature
        $this->verifySignature($payload, $signature, $webhookTimestamp);
    }

    /**
     * Parse webhook payload into a WebhookEvent.
     */
    public function parsePayload(string $payload): WebhookEvent
    {
        $data = json_decode($payload, true);
        
        if (!is_array($data)) {
            throw new WebhookVerificationException('Invalid JSON payload');
        }

        return WebhookEvent::fromArray($data);
    }

    /**
     * Verify webhook and parse payload in one call.
     */
    public function verifyAndParse(
        string $payload,
        array $headers,
        ?int $timestamp = null
    ): WebhookEvent {
        $this->verifyOrThrow($payload, $headers, $timestamp);
        return $this->parsePayload($payload);
    }

    /**
     * Extract signature from headers.
     */
    private function extractSignature(array $headers): ?string
    {
        // Normalize header names to lowercase
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
        
        $signatureHeader = strtolower(self::SIGNATURE_HEADER);
        
        if (!isset($normalizedHeaders[$signatureHeader])) {
            return null;
        }

        $signature = $normalizedHeaders[$signatureHeader];
        
        // Handle array of header values
        if (is_array($signature)) {
            $signature = $signature[0] ?? null;
        }

        return $signature;
    }

    /**
     * Extract timestamp from headers.
     */
    private function extractTimestamp(array $headers): ?int
    {
        // Normalize header names to lowercase
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
        
        $timestampHeader = strtolower(self::TIMESTAMP_HEADER);
        
        if (!isset($normalizedHeaders[$timestampHeader])) {
            return null;
        }

        $timestamp = $normalizedHeaders[$timestampHeader];
        
        // Handle array of header values
        if (is_array($timestamp)) {
            $timestamp = $timestamp[0] ?? null;
        }

        if ($timestamp === null) {
            return null;
        }

        return (int) $timestamp;
    }

    /**
     * Verify timestamp is within tolerance.
     */
    private function verifyTimestamp(int $timestamp): void
    {
        $currentTime = time();
        $timeDifference = abs($currentTime - $timestamp);

        if ($timeDifference > $this->toleranceSeconds) {
            throw new WebhookVerificationException(
                "Timestamp tolerance exceeded. Difference: {$timeDifference}s, tolerance: {$this->toleranceSeconds}s"
            );
        }
    }

    /**
     * Verify webhook signature.
     */
    private function verifySignature(string $payload, string $signature, int $timestamp): void
    {
        // Create the signed payload
        $signedPayload = $timestamp . '.' . $payload;
        
        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->secret);
        
        // Compare signatures using hash_equals for timing attack protection
        if (!hash_equals($expectedSignature, $signature)) {
            throw new WebhookVerificationException('Invalid signature');
        }
    }

    /**
     * Generate signature for testing purposes.
     */
    public function generateSignature(string $payload, int $timestamp): string
    {
        $signedPayload = $timestamp . '.' . $payload;
        return hash_hmac('sha256', $signedPayload, $this->secret);
    }

    /**
     * Set tolerance for timestamp verification.
     */
    public function withTolerance(int $toleranceSeconds): self
    {
        return new self($this->secret, $toleranceSeconds);
    }
}
