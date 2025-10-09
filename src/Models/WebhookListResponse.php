<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Webhook list response with pagination.
 */
final readonly class WebhookListResponse
{
    public function __construct(
        public array $webhooks,
        public PaginationMeta $meta,
    ) {
    }
}
