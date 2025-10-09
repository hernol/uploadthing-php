<?php

declare(strict_types=1);

namespace UploadThing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UploadThing\Exceptions\WebhookVerificationException;
use UploadThing\Models\FileUploadedEvent;
use UploadThing\Models\WebhookEvent;
use UploadThing\Utils\WebhookVerifier;

class WebhookVerifierTest extends TestCase
{
    private WebhookVerifier $verifier;
    private string $secret;

    protected function setUp(): void
    {
        $this->secret = 'test-secret-key';
        $this->verifier = new WebhookVerifier($this->secret);
    }

    public function testVerifyValidSignature(): void
    {
        $payload = '{"test": "data"}';
        $timestamp = time();
        $signature = $this->verifier->generateSignature($payload, $timestamp);
        
        $headers = [
            'X-UploadThing-Signature' => $signature,
            'X-UploadThing-Timestamp' => (string) $timestamp,
        ];

        $this->assertTrue($this->verifier->verify($payload, $headers, $timestamp));
    }

    public function testVerifyInvalidSignature(): void
    {
        $payload = '{"test": "data"}';
        $timestamp = time();
        $invalidSignature = 'invalid-signature';
        
        $headers = [
            'X-UploadThing-Signature' => $invalidSignature,
            'X-UploadThing-Timestamp' => (string) $timestamp,
        ];

        $this->assertFalse($this->verifier->verify($payload, $headers, $timestamp));
    }

    public function testVerifyExpiredTimestamp(): void
    {
        $payload = '{"test": "data"}';
        $expiredTimestamp = time() - 400; // 400 seconds ago (beyond 5-minute tolerance)
        $signature = $this->verifier->generateSignature($payload, $expiredTimestamp);
        
        $headers = [
            'X-UploadThing-Signature' => $signature,
            'X-UploadThing-Timestamp' => (string) $expiredTimestamp,
        ];

        $this->assertFalse($this->verifier->verify($payload, $headers, $expiredTimestamp));
    }

    public function testVerifyOrThrowWithValidSignature(): void
    {
        $payload = '{"test": "data"}';
        $timestamp = time();
        $signature = $this->verifier->generateSignature($payload, $timestamp);
        
        $headers = [
            'X-UploadThing-Signature' => $signature,
            'X-UploadThing-Timestamp' => (string) $timestamp,
        ];

        $this->expectNotToPerformAssertions();
        $this->verifier->verifyOrThrow($payload, $headers, $timestamp);
    }

    public function testVerifyOrThrowWithInvalidSignature(): void
    {
        $payload = '{"test": "data"}';
        $timestamp = time();
        $invalidSignature = 'invalid-signature';
        
        $headers = [
            'X-UploadThing-Signature' => $invalidSignature,
            'X-UploadThing-Timestamp' => (string) $timestamp,
        ];

        $this->expectException(WebhookVerificationException::class);
        $this->verifier->verifyOrThrow($payload, $headers, $timestamp);
    }

    public function testParsePayload(): void
    {
        $payload = json_encode([
            'id' => 'event-123',
            'type' => 'file.uploaded',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'file' => [
                    'id' => 'file-123',
                    'name' => 'test.jpg',
                    'size' => 1024,
                    'mimeType' => 'image/jpeg',
                    'url' => 'https://example.com/file.jpg',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z',
                ],
            ],
        ]);

        $event = $this->verifier->parsePayload($payload);
        
        $this->assertInstanceOf(FileUploadedEvent::class, $event);
        $this->assertEquals('file.uploaded', $event->getEventType());
        $this->assertEquals('event-123', $event->id);
    }

    public function testVerifyAndParse(): void
    {
        $payload = json_encode([
            'id' => 'event-123',
            'type' => 'file.uploaded',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'file' => [
                    'id' => 'file-123',
                    'name' => 'test.jpg',
                    'size' => 1024,
                    'mimeType' => 'image/jpeg',
                    'url' => 'https://example.com/file.jpg',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z',
                ],
            ],
        ]);

        $timestamp = time();
        $signature = $this->verifier->generateSignature($payload, $timestamp);
        
        $headers = [
            'X-UploadThing-Signature' => $signature,
            'X-UploadThing-Timestamp' => (string) $timestamp,
        ];

        $event = $this->verifier->verifyAndParse($payload, $headers, $timestamp);
        
        $this->assertInstanceOf(FileUploadedEvent::class, $event);
        $this->assertEquals('file.uploaded', $event->getEventType());
    }

    public function testCustomTolerance(): void
    {
        $verifier = new WebhookVerifier($this->secret, 600); // 10 minutes tolerance
        
        $payload = '{"test": "data"}';
        $timestamp = time() - 400; // 400 seconds ago (within 10-minute tolerance)
        $signature = $verifier->generateSignature($payload, $timestamp);
        
        $headers = [
            'X-UploadThing-Signature' => $signature,
            'X-UploadThing-Timestamp' => (string) $timestamp,
        ];

        $this->assertTrue($verifier->verify($payload, $headers, $timestamp));
    }

    public function testWithTolerance(): void
    {
        $verifier = $this->verifier->withTolerance(600); // 10 minutes tolerance
        
        $payload = '{"test": "data"}';
        $timestamp = time() - 400; // 400 seconds ago (within 10-minute tolerance)
        $signature = $verifier->generateSignature($payload, $timestamp);
        
        $headers = [
            'X-UploadThing-Signature' => $signature,
            'X-UploadThing-Timestamp' => (string) $timestamp,
        ];

        $this->assertTrue($verifier->verify($payload, $headers, $timestamp));
    }
}
