<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * File deleted webhook event.
 */
final class FileDeletedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public string $fileId,
        public string $fileName,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'file.deleted', $timestamp, [
            'fileId' => $fileId,
            'fileName' => $fileName,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'file.deleted';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            new \DateTimeImmutable($data['timestamp'] ?? 'now'),
            $data['data']['fileId'] ?? '',
            $data['data']['fileName'] ?? '',
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
