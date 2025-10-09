<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Request to create an upload session.
 */
final readonly class CreateUploadRequest
{
    public function __construct(
        public string $fileName,
        public int $fileSize,
        public ?string $mimeType = null,
    ) {
    }
}
