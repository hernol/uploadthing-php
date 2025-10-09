<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Upload completed webhook event.
 */
final class UploadCompletedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public string $uploadId,
        public string $fileId,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'upload.completed', $timestamp, [
            'uploadId' => $uploadId,
            'fileId' => $fileId,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'upload.completed';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            new \DateTimeImmutable($data['timestamp'] ?? 'now'),
            $data['data']['uploadId'] ?? '',
            $data['data']['fileId'] ?? '',
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
