<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Webhook model.
 */
final readonly class Webhook
{
    public function __construct(
        public string $id,
        public string $url,
        public array $events,
        public bool $isActive,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?string $secret = null,
        public ?array $metadata = null,
    ) {
    }
}
