<?php

declare(strict_types=1);

namespace UploadThing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UploadThing\Models\FileUploadedEvent;
use UploadThing\Models\FileDeletedEvent;
use UploadThing\Models\WebhookEvent;
use UploadThing\Utils\WebhookHandler;
use UploadThing\Utils\WebhookVerifier;

class WebhookHandlerTest extends TestCase
{
    private WebhookHandler $handler;
    private WebhookVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new WebhookVerifier('test-secret');
        $this->handler = new WebhookHandler($this->verifier);
    }

    public function testRegisterSingleEventHandler(): void
    {
        $called = false;
        $handler = function (FileUploadedEvent $event) use (&$called) {
            $called = true;
            $this->assertEquals('file.uploaded', $event->getEventType());
        };

        $this->handler->on('file.uploaded', $handler);

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

        $this->handler->handleUnverified($payload);
        
        $this->assertTrue($called);
    }

    public function testRegisterMultipleEventHandlers(): void
    {
        $fileUploadedCalled = false;
        $fileDeletedCalled = false;

        $uploadHandler = function (FileUploadedEvent $event) use (&$fileUploadedCalled) {
            $fileUploadedCalled = true;
        };

        $deleteHandler = function (FileDeletedEvent $event) use (&$fileDeletedCalled) {
            $fileDeletedCalled = true;
        };

        $this->handler
            ->on('file.uploaded', $uploadHandler)
            ->on('file.deleted', $deleteHandler);

        // Test file uploaded event
        $uploadPayload = json_encode([
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

        $this->handler->handleUnverified($uploadPayload);
        $this->assertTrue($fileUploadedCalled);
        $this->assertFalse($fileDeletedCalled);

        // Test file deleted event
        $deletePayload = json_encode([
            'id' => 'event-456',
            'type' => 'file.deleted',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'fileId' => 'file-456',
                'fileName' => 'deleted.jpg',
            ],
        ]);

        $this->handler->handleUnverified($deletePayload);
        $this->assertTrue($fileDeletedCalled);
    }

    public function testRegisterMultipleEventTypes(): void
    {
        $calledCount = 0;
        $handler = function (WebhookEvent $event) use (&$calledCount) {
            $calledCount++;
        };

        $this->handler->onEvents(['file.uploaded', 'file.deleted'], $handler);

        // Test file uploaded event
        $uploadPayload = json_encode([
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

        $this->handler->handleUnverified($uploadPayload);

        // Test file deleted event
        $deletePayload = json_encode([
            'id' => 'event-456',
            'type' => 'file.deleted',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'fileId' => 'file-456',
                'fileName' => 'deleted.jpg',
            ],
        ]);

        $this->handler->handleUnverified($deletePayload);

        $this->assertEquals(2, $calledCount);
    }

    public function testGenericEventHandler(): void
    {
        $calledCount = 0;
        $genericHandler = function (WebhookEvent $event) use (&$calledCount) {
            $calledCount++;
        };

        $this->handler->on('*', $genericHandler);

        // Test multiple events
        $events = [
            'file.uploaded',
            'file.deleted',
            'upload.completed',
        ];

        foreach ($events as $eventType) {
            $payload = json_encode([
                'id' => "event-{$eventType}",
                'type' => $eventType,
                'timestamp' => '2023-01-01T00:00:00Z',
                'data' => [],
            ]);

            $this->handler->handleUnverified($payload);
        }

        $this->assertEquals(3, $calledCount);
    }

    public function testCreateWithSecret(): void
    {
        $handler = WebhookHandler::create('test-secret', 600);
        
        $this->assertInstanceOf(WebhookHandler::class, $handler);
    }

    public function testCreateWithVerifier(): void
    {
        $verifier = new WebhookVerifier('test-secret', 300);
        $handler = WebhookHandler::withVerifier($verifier);
        
        $this->assertInstanceOf(WebhookHandler::class, $handler);
    }
}
