<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * File uploaded webhook event.
 */
final class FileUploadedEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        \DateTimeImmutable $timestamp,
        public File $file,
        public ?string $userId = null,
        public ?array $metadata = null,
    ) {
        parent::__construct($id, 'file.uploaded', $timestamp, [
            'file' => $file,
            'userId' => $userId,
            'metadata' => $metadata,
        ]);
    }

    public function getEventType(): string
    {
        return 'file.uploaded';
    }

    public static function fromArray(array $data): self
    {
        $fileData = $data['data']['file'] ?? [];
        $file = new File(
            $fileData['id'] ?? '',
            $fileData['name'] ?? '',
            $fileData['size'] ?? 0,
            $fileData['mimeType'] ?? '',
            $fileData['url'] ?? '',
            new \DateTimeImmutable($fileData['createdAt'] ?? 'now'),
            new \DateTimeImmutable($fileData['updatedAt'] ?? 'now'),
            $fileData['description'] ?? null,
            $fileData['metadata'] ?? null,
        );

        return new self(
            $data['id'] ?? '',
            new \DateTimeImmutable($data['timestamp'] ?? 'now'),
            $file,
            $data['data']['userId'] ?? null,
            $data['data']['metadata'] ?? null,
        );
    }
}
