# Usage Guide - UploadThing PHP Package

## Overview

This package provides a simple, Laravel-focused interface for uploading files to UploadThing and handling webhooks. It uses environment variables for configuration and provides a clean, straightforward API.

## Configuration

### Environment Variables

Set the following environment variables in your `.env` file:

```env
UPLOADTHING_API_KEY=ut_sk_your_api_key_here
UPLOADTHING_BASE_URL=https://api.uploadthing.com
UPLOADTHING_API_VERSION=v6
UPLOADTHING_TIMEOUT=30
UPLOADTHING_CALLBACK_URL=https://your-app.com/webhook
UPLOADTHING_CALLBACK_SLUG=your-slug
```

**Required:**
- `UPLOADTHING_API_KEY` - Your UploadThing API key

**Optional:**
- `UPLOADTHING_BASE_URL` - API base URL (default: `https://api.uploadthing.com`)
- `UPLOADTHING_API_VERSION` - API version (default: `v6`)
- `UPLOADTHING_TIMEOUT` - Request timeout in seconds (default: `30`)
- `UPLOADTHING_CALLBACK_URL` - Server callback URL for uploads
- `UPLOADTHING_CALLBACK_SLUG` - Callback slug identifier

## File Uploads

### Basic Upload

The simplest way to upload a file:

```php
<?php

use UploadThing\Resources\Uploads;

$uploads = new Uploads();
$file = $uploads->uploadFile('/path/to/file.jpg');

if ($file) {
    echo "File uploaded: {$file->name}\n";
    echo "File URL: {$file->url}\n";
    echo "File ID: {$file->id}\n";
}
```

### Upload with Custom Name and MIME Type

You can specify a custom file name and MIME type:

```php
<?php

use UploadThing\Resources\Uploads;

$uploads = new Uploads();
$file = $uploads->uploadFile(
    '/path/to/image.jpg',
    'my-custom-name.jpg',  // Custom file name
    'image/jpeg'           // Custom MIME type
);
```

### How Upload Works

The `uploadFile()` method performs three steps:

1. **Call `/v6/uploadFiles` endpoint** - Prepares the upload and returns S3 presigned URL data
2. **Upload to S3** - Uploads the file to S3 using multipart form data
3. **Finalize via polling** - Polls the UploadThing API to finalize the upload, retrying up to 5 times with 1-second delays if the server reports "still working"

The method returns a `File` object on success, or `null` if the upload couldn't be finalized after all retries.

### File Object

The returned `File` object contains:

```php
$file->id          // File ID (string)
$file->name        // File name (string)
$file->size        // File size in bytes (int)
$file->mimeType    // MIME type (string)
$file->url         // Public URL (string)
$file->createdAt   // DateTimeImmutable
$file->updatedAt   // DateTimeImmutable|null
$file->description // Description|null
$file->metadata    // Metadata array|null
```

## Webhooks

### Basic Webhook Handling

Handle webhooks from UploadThing:

```php
<?php

use UploadThing\Resources\Webhooks;

$webhooks = new Webhooks();

// In a Laravel controller
$event = $webhooks->handleWebhook(
    $request->getContent(),
    $request->headers->all(),
    env('UPLOADTHING_WEBHOOK_SECRET')
);

echo "Event type: {$event->type}\n";
echo "Event data: " . json_encode($event->data) . "\n";
```

### Handle from PHP Globals

For standalone scripts or non-framework usage:

```php
<?php

use UploadThing\Resources\Webhooks;

$webhooks = new Webhooks();
$event = $webhooks->handleWebhookFromGlobals(
    env('UPLOADTHING_WEBHOOK_SECRET')
);
```

### Verify Signature Only

If you only need to verify the signature:

```php
<?php

use UploadThing\Resources\Webhooks;

$webhooks = new Webhooks();
$isValid = $webhooks->verifySignature(
    $payload,
    $signature,
    $secret
);

if ($isValid) {
    echo "Signature is valid\n";
}
```

### Parse Without Verification

Parse a webhook payload without verification (not recommended for production):

```php
<?php

use UploadThing\Resources\Webhooks;

$webhooks = new Webhooks();
$event = $webhooks->parsePayload($payload);
```

### Webhook Event Types

The package supports the following webhook event types:

- `file.uploaded` - File was successfully uploaded
- `file.deleted` - File was deleted
- `file.updated` - File metadata was updated
- `upload.started` - Upload process started
- `upload.completed` - Upload process completed
- `upload.failed` - Upload process failed
- `webhook.created` - Webhook was created
- `webhook.updated` - Webhook was updated
- `webhook.deleted` - Webhook was deleted

## Webhook Handler Utility

For more advanced webhook handling, use the `WebhookHandler` utility:

```php
<?php

use UploadThing\Utils\WebhookHandler;
use UploadThing\Models\WebhookEvent;

$handler = WebhookHandler::create(
    env('UPLOADTHING_WEBHOOK_SECRET'),
    300 // Tolerance in seconds (default: 300)
);

// Register handler for specific event
$handler->on('file.uploaded', function (WebhookEvent $event) {
    echo "File uploaded: " . ($event->data['fileName'] ?? 'unknown') . "\n";
});

// Register handler for multiple events
$handler->onEvents(['file.deleted', 'file.updated'], function (WebhookEvent $event) {
    echo "File event: {$event->type}\n";
});

// Register catch-all handler
$handler->on('*', function (WebhookEvent $event) {
    echo "Received event: {$event->type}\n";
});

// Handle webhook
$event = $handler->handle($payload, $headers);
```

## Webhook Verifier Utility

For direct control over webhook verification:

```php
<?php

use UploadThing\Utils\WebhookVerifier;

$verifier = new WebhookVerifier(
    env('UPLOADTHING_WEBHOOK_SECRET'),
    300 // Tolerance in seconds
);

// Verify signature
$isValid = $verifier->verify($payload, $headers);

// Verify and throw exception on failure
$verifier->verifyOrThrow($payload, $headers);

// Parse payload
$event = $verifier->parsePayload($payload);

// Verify and parse in one call
$event = $verifier->verifyAndParse($payload, $headers);
```

## Error Handling

### Exception Types

The package throws the following exception types:

- `UploadThing\Exceptions\ApiException` - General API errors
- `UploadThing\Exceptions\AuthenticationException` - Authentication failures (HTTP 401)
- `UploadThing\Exceptions\ValidationException` - Validation errors (HTTP 400)
- `UploadThing\Exceptions\RateLimitException` - Rate limit exceeded (HTTP 429)
- `UploadThing\Exceptions\WebhookVerificationException` - Webhook verification failures

### Example Error Handling

```php
<?php

use UploadThing\Resources\Uploads;
use UploadThing\Exceptions\ApiException;
use UploadThing\Exceptions\AuthenticationException;
use UploadThing\Exceptions\RateLimitException;
use UploadThing\Exceptions\ValidationException;

$uploads = new Uploads();

try {
    $file = $uploads->uploadFile('/path/to/file.jpg');
} catch (AuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage() . "\n";
} catch (RateLimitException $e) {
    echo "Rate limited, retry after: " . ($e->getRetryAfter() ?? 'unknown') . "s\n";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
    if ($e->getValidationErrors()) {
        print_r($e->getValidationErrors());
    }
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage() . "\n";
    echo "Error code: " . ($e->getErrorCode() ?? 'N/A') . "\n";
    echo "HTTP status: " . $e->getCode() . "\n";
    
    if ($e->getErrorDetails()) {
        print_r($e->getErrorDetails());
    }
} catch (\Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}
```

## Examples

See the [examples](../examples/) folder for complete working examples:

- [Basic File Upload](../examples/01-basic-upload.php)
- [Upload with Custom Options](../examples/02-upload-custom.php)
- [Webhook Handling](../examples/03-webhook-handling.php)
- [Webhook Handler Utility](../examples/04-webhook-handler.php)
- [Webhook Verifier](../examples/05-webhook-verifier.php)
- [Laravel Controller Example](../examples/06-laravel-controller.php)
- [Error Handling](../examples/07-error-handling.php)

## Best Practices

1. **Always set environment variables** - Never hardcode API keys
2. **Handle exceptions properly** - Use try-catch blocks for all API calls
3. **Verify webhooks** - Always verify webhook signatures in production
4. **Use appropriate timeouts** - Set timeouts based on your file sizes
5. **Log errors** - Log API errors for debugging and monitoring

## Troubleshooting

### "UPLOADTHING_API_KEY environment variable is not set"

Make sure you've set the `UPLOADTHING_API_KEY` in your `.env` file or environment.

### "Authentication failed"

Check that your API key is correct and has the necessary permissions.

### "Upload failed to finalize"

This usually means the polling step failed after all retry attempts (up to 5 retries with 1-second delays). This can happen with large files or slow network conditions. Check your network connection and try again.

### Webhook verification fails

- Ensure the webhook secret matches what's configured in UploadThing
- Check that the timestamp is within the tolerance window (default: 5 minutes)
- Verify the signature header is being passed correctly
