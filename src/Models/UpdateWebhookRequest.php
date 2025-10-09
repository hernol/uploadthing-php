<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Request to update a webhook.
 */
final readonly class UpdateWebhookRequest
{
    public function __construct(
        public ?string $url = null,
        public ?array $events = null,
    ) {
    }
}
