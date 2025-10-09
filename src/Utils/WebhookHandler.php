<?php

declare(strict_types=1);

namespace UploadThing\Utils;

use UploadThing\Exceptions\WebhookVerificationException;
use UploadThing\Models\FileDeletedEvent;
use UploadThing\Models\FileUpdatedEvent;
use UploadThing\Models\FileUploadedEvent;
use UploadThing\Models\GenericWebhookEvent;
use UploadThing\Models\UploadCompletedEvent;
use UploadThing\Models\UploadFailedEvent;
use UploadThing\Models\UploadStartedEvent;
use UploadThing\Models\WebhookCreatedEvent;
use UploadThing\Models\WebhookDeletedEvent;
use UploadThing\Models\WebhookEvent;
use UploadThing\Models\WebhookUpdatedEvent;

/**
 * Webhook handler utility for processing webhook events.
 */
final class WebhookHandler
{
    /**
     * @var array<string, array>
     */
    private array $eventHandlers = [];

    public function __construct(
        private WebhookVerifier $verifier,
    ) {
    }

    /**
     * Register an event handler for a specific event type.
     */
    public function on(string $eventType, callable $handler): self
    {
        $this->eventHandlers[$eventType][] = $handler;
        return $this;
    }

    /**
     * Register handlers for multiple event types.
     */
    public function onEvents(array $eventTypes, callable $handler): self
    {
        foreach ($eventTypes as $eventType) {
            $this->on($eventType, $handler);
        }
        return $this;
    }

    /**
     * Handle a webhook payload with signature verification.
     */
    public function handle(
        string $payload,
        array $headers,
        ?int $timestamp = null
    ): WebhookEvent {
        // Verify webhook signature
        $event = $this->verifier->verifyAndParse($payload, $headers, $timestamp);
        
        // Process the event
        $this->processEvent($event);
        
        return $event;
    }

    /**
     * Handle a webhook payload without signature verification.
     */
    public function handleUnverified(string $payload): WebhookEvent
    {
        $event = $this->verifier->parsePayload($payload);
        $this->processEvent($event);
        return $event;
    }

    /**
     * Process a webhook event by calling registered handlers.
     */
    private function processEvent(WebhookEvent $event): void
    {
        $eventType = $event->getEventType();
        
        // Call specific event handlers
        if (isset($this->eventHandlers[$eventType])) {
            foreach ($this->eventHandlers[$eventType] as $handler) {
                $handler($event);
            }
        }
        
        // Call generic handlers
        if (isset($this->eventHandlers['*'])) {
            foreach ($this->eventHandlers['*'] as $handler) {
                $handler($event);
            }
        }
    }

    /**
     * Create a webhook handler with common event handlers.
     */
    public static function create(
        string $secret,
        int $toleranceSeconds = 300
    ): self {
        $verifier = new WebhookVerifier($secret, $toleranceSeconds);
        return new self($verifier);
    }

    /**
     * Create a webhook handler with a custom verifier.
     */
    public static function withVerifier(WebhookVerifier $verifier): self
    {
        return new self($verifier);
    }
}
