<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Upload failed webhook event.
 */
final class UploadFailedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public string $uploadId,
        public string $errorMessage,
        public ?string $errorCode = null,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'upload.failed', $timestamp, [
            'uploadId' => $uploadId,
            'errorMessage' => $errorMessage,
            'errorCode' => $errorCode,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'upload.failed';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            new \DateTimeImmutable($data['timestamp'] ?? 'now'),
            $data['data']['uploadId'] ?? '',
            $data['data']['errorMessage'] ?? '',
            $data['data']['errorCode'] ?? null,
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
