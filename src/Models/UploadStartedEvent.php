<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Upload started webhook event.
 */
final class UploadStartedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public string $uploadId,
        public string $fileName,
        public int $fileSize,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'upload.started', $timestamp, [
            'uploadId' => $uploadId,
            'fileName' => $fileName,
            'fileSize' => $fileSize,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'upload.started';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            new \DateTimeImmutable($data['timestamp'] ?? 'now'),
            $data['data']['uploadId'] ?? '',
            $data['data']['fileName'] ?? '',
            $data['data']['fileSize'] ?? 0,
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
