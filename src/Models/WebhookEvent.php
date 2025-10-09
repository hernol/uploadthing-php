<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Base webhook event model.
 */
abstract class WebhookEvent
{
    public function __construct(
        public string $id,
        public string $type,
        public \DateTimeImmutable $timestamp,
        public array $data,
    ) {
    }

    /**
     * Get the event type.
     */
    abstract public function getEventType(): string;

    /**
     * Create a webhook event from array data.
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] ?? '';
        
        return match ($type) {
            'file.uploaded' => FileUploadedEvent::fromArray($data),
            'file.deleted' => FileDeletedEvent::fromArray($data),
            'file.updated' => FileUpdatedEvent::fromArray($data),
            'upload.started' => UploadStartedEvent::fromArray($data),
            'upload.completed' => UploadCompletedEvent::fromArray($data),
            'upload.failed' => UploadFailedEvent::fromArray($data),
            'webhook.created' => WebhookCreatedEvent::fromArray($data),
            'webhook.updated' => WebhookUpdatedEvent::fromArray($data),
            'webhook.deleted' => WebhookDeletedEvent::fromArray($data),
            default => new GenericWebhookEvent($data['id'] ?? '', $type, new \DateTimeImmutable(), $data),
        };
    }
}
