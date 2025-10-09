<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Webhook created event.
 */
final class WebhookCreatedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public Webhook $webhook,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'webhook.created', $timestamp, [
            'webhook' => $webhook,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'webhook.created';
    }

    public static function fromArray(array $data): self
    {
        $webhookData = $data['data']['webhook'] ?? [];
        $webhook = new Webhook(
            $webhookData['id'] ?? '',
            $webhookData['url'] ?? '',
            $webhookData['events'] ?? [],
            $webhookData['isActive'] ?? true,
            new \DateTimeImmutable($webhookData['createdAt'] ?? 'now'),
            new \DateTimeImmutable($webhookData['updatedAt'] ?? 'now'),
            $webhookData['secret'] ?? null,
            $webhookData['metadata'] ?? null,
        );

        return new self(
            $data['id'] ?? '',
            new \DateTimeImmutable($data['timestamp'] ?? 'now'),
            $webhook,
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
