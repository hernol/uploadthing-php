<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Base webhook event model.
 */
class WebhookEvent
{
    public function __construct(
        public string $type,
        public array $data,
        public ?string $id = null,
        public ?\DateTimeImmutable $timestamp = null,
    ) {
        if ($this->timestamp === null) {
            $this->timestamp = new \DateTimeImmutable();
        }
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return $this->type;
    }

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
