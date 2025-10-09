<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * File model representing an uploaded file.
 */
final readonly class File
{
    public function __construct(
        public string $id,
        public string $name,
        public int $size,
        public string $mimeType,
        public string $url,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?string $description = null,
        public ?array $metadata = null,
    ) {
    }
}
