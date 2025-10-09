<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Pagination metadata.
 */
final readonly class PaginationMeta
{
    public function __construct(
        public int $total,
        public int $limit,
        public ?string $cursor = null,
        public ?string $nextCursor = null,
        public bool $hasMore = false,
    ) {
    }
}
