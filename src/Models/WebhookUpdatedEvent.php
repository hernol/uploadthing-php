<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Webhook updated event.
 */
final class WebhookUpdatedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public Webhook $webhook,
        public array $changes,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'webhook.updated', $timestamp, [
            'webhook' => $webhook,
            'changes' => $changes,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'webhook.updated';
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
            $data['data']['changes'] ?? [],
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
