<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * File list response with pagination.
 */
final readonly class FileListResponse
{
    public function __construct(
        public array $files,
        public PaginationMeta $meta,
    ) {
    }
}
