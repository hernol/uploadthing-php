<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Request to create a webhook.
 */
final readonly class CreateWebhookRequest
{
    public function __construct(
        public string $url,
        public array $events = [],
    ) {
    }
}
