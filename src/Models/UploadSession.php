<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Upload session model.
 */
final readonly class UploadSession
{
    public function __construct(
        public string $id,
        public string $fileName,
        public int $fileSize,
        public string $mimeType,
        public string $status,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?string $uploadUrl = null,
        public ?string $fileId = null,
        public ?int $uploadedBytes = null,
    ) {
    }
}
