<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Request to rename a file.
 */
final readonly class RenameFileRequest
{
    public function __construct(
        public string $name,
    ) {
    }
}
