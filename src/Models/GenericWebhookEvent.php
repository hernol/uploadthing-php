<?php

declare(strict_types=1);

namespace UploadThing\Models;

/**
 * Generic webhook event for unknown event types.
 */
final class GenericWebhookEvent extends WebhookEvent
{
    public function __construct(
        string $id,
        string $type,
        \DateTimeImmutable $timestamp,
        array $data,
    ) {
        parent::__construct($id, $type, $timestamp, $data);
    }

    public function getEventType(): string
    {
        return $this->type;
    }
}
