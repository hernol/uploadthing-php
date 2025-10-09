<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Webhook deleted event.
 */
final class WebhookDeletedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public string $webhookId,
        public string $webhookUrl,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'webhook.deleted', $timestamp, [
            'webhookId' => $webhookId,
            'webhookUrl' => $webhookUrl,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'webhook.deleted';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            new \DateTimeImmutable($data['timestamp'] ?? 'now'),
            $data['data']['webhookId'] ?? '',
            $data['data']['webhookUrl'] ?? '',
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
