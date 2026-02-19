# Laravel Integration Guide

This guide shows you how to integrate the UploadThing PHP package into your Laravel application.

## Installation

```bash
composer require hernol/uploadthing-php
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
UPLOADTHING_API_KEY=ut_sk_your_api_key_here
UPLOADTHING_BASE_URL=https://api.uploadthing.com
UPLOADTHING_API_VERSION=v6
UPLOADTHING_TIMEOUT=30
UPLOADTHING_CALLBACK_URL=https://your-app.com/webhook
UPLOADTHING_CALLBACK_SLUG=your-slug
UPLOADTHING_WEBHOOK_SECRET=your_webhook_secret_here
```

### Optional: Service Provider

You can create a service provider to bind the resources:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use UploadThing\Resources\Uploads;
use UploadThing\Resources\Webhooks;

class UploadThingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Uploads::class, function () {
            return new Uploads();
        });

        $this->app->singleton(Webhooks::class, function () {
            return new Webhooks();
        });
    }
}
```

Register it in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\UploadThingServiceProvider::class,
],
```

## Usage in Controllers

### File Upload Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use UploadThing\Resources\Uploads;
use UploadThing\Exceptions\ApiException;
use UploadThing\Exceptions\AuthenticationException;
use UploadThing\Exceptions\RateLimitException;

class FileController extends Controller
{
    public function upload(Request $request, Uploads $uploads): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        try {
            $file = $request->file('file');
            
            $uploaded = $uploads->uploadFile(
                $file->getPathname(),
                $file->getClientOriginalName(),
                $file->getMimeType()
            );

            if (!$uploaded) {
                return response()->json([
                    'error' => 'Upload failed to finalize'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $uploaded->id,
                    'name' => $uploaded->name,
                    'url' => $uploaded->url,
                    'size' => $uploaded->size,
                    'mimeType' => $uploaded->mimeType,
                ]
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ], 401);
        } catch (RateLimitException $e) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $e->getRetryAfter()
            ], 429);
        } catch (ApiException $e) {
            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode()
            ], $e->getCode());
        }
    }
}
```

### Webhook Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use UploadThing\Resources\Webhooks;
use UploadThing\Models\WebhookEvent;

class WebhookController extends Controller
{
    public function handle(Request $request, Webhooks $webhooks): JsonResponse
    {
        try {
            $event = $webhooks->handleWebhook(
                $request->getContent(),
                $request->headers->all(),
                config('services.uploadthing.webhook_secret', env('UPLOADTHING_WEBHOOK_SECRET'))
            );

            // Process the event based on type
            $this->processEvent($event);

            return response()->json([
                'success' => true,
                'event' => $event->type
            ]);
        } catch (\Exception $e) {
            \Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->getContent()
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function processEvent(WebhookEvent $event): void
    {
        match ($event->type) {
            'file.uploaded' => $this->handleFileUploaded($event),
            'file.deleted' => $this->handleFileDeleted($event),
            'file.updated' => $this->handleFileUpdated($event),
            default => $this->handleGenericEvent($event),
        };
    }

    private function handleFileUploaded(WebhookEvent $event): void
    {
        \Log::info('File uploaded', [
            'fileId' => $event->data['fileId'] ?? null,
            'fileName' => $event->data['fileName'] ?? null,
        ]);

        // Your business logic here
        // e.g., update database, send notifications, etc.
    }

    private function handleFileDeleted(WebhookEvent $event): void
    {
        \Log::info('File deleted', [
            'fileId' => $event->data['fileId'] ?? null,
        ]);

        // Your business logic here
    }

    private function handleFileUpdated(WebhookEvent $event): void
    {
        \Log::info('File updated', [
            'fileId' => $event->data['fileId'] ?? null,
        ]);

        // Your business logic here
    }

    private function handleGenericEvent(WebhookEvent $event): void
    {
        \Log::info('Generic webhook event', [
            'type' => $event->type,
            'data' => $event->data,
        ]);
    }
}
```

### Routes

Add routes in `routes/web.php` or `routes/api.php`:

```php
use App\Http\Controllers\FileController;
use App\Http\Controllers\WebhookController;

// File upload route
Route::post('/upload', [FileController::class, 'upload']);

// Webhook route (should be CSRF exempt)
Route::post('/webhook/uploadthing', [WebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

## Using WebhookHandler

For more advanced webhook handling, use the `WebhookHandler` utility:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UploadThing\Utils\WebhookHandler;
use UploadThing\Models\WebhookEvent;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $handler = WebhookHandler::create(
            config('services.uploadthing.webhook_secret', env('UPLOADTHING_WEBHOOK_SECRET'))
        );

        // Register event handlers
        $handler->on('file.uploaded', function (WebhookEvent $event) {
            // Handle file uploaded
            \Log::info('File uploaded', ['fileId' => $event->data['fileId'] ?? null]);
        });

        $handler->on('file.deleted', function (WebhookEvent $event) {
            // Handle file deleted
            \Log::info('File deleted', ['fileId' => $event->data['fileId'] ?? null]);
        });

        try {
            $event = $handler->handle(
                $request->getContent(),
                $request->headers->all()
            );

            return response()->json([
                'success' => true,
                'event' => $event->type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## Form Request Validation

Create a form request for file uploads:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file size must not exceed 10MB.',
            'file.mimes' => 'The file must be a jpg, png, or pdf.',
        ];
    }
}
```

Use it in your controller:

```php
public function upload(UploadFileRequest $request, Uploads $uploads): JsonResponse
{
    $file = $request->file('file');
    // ... rest of the code
}
```

## Queue Jobs

For handling webhooks asynchronously:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use UploadThing\Models\WebhookEvent;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WebhookEvent $event
    ) {}

    public function handle(): void
    {
        match ($this->event->type) {
            'file.uploaded' => $this->handleFileUploaded(),
            'file.deleted' => $this->handleFileDeleted(),
            default => $this->handleGeneric(),
        };
    }

    private function handleFileUploaded(): void
    {
        // Your async processing logic
    }

    private function handleFileDeleted(): void
    {
        // Your async processing logic
    }

    private function handleGeneric(): void
    {
        // Your async processing logic
    }
}
```

Dispatch from your webhook controller:

```php
public function handle(Request $request, Webhooks $webhooks): JsonResponse
{
    $event = $webhooks->handleWebhook(
        $request->getContent(),
        $request->headers->all(),
        env('UPLOADTHING_WEBHOOK_SECRET')
    );

    ProcessWebhookEvent::dispatch($event);

    return response()->json(['success' => true]);
}
```

## Testing

### Testing File Uploads

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use UploadThing\Resources\Uploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadTest extends TestCase
{
    public function test_file_can_be_uploaded(): void
    {
        Storage::fake('local');
        
        $file = UploadedFile::fake()->image('test.jpg');
        
        $uploads = new Uploads();
        $uploaded = $uploads->uploadFile(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $file->getMimeType()
        );

        $this->assertNotNull($uploaded);
        $this->assertEquals('test.jpg', $uploaded->name);
    }
}
```

### Testing Webhooks

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use UploadThing\Resources\Webhooks;

class WebhookTest extends TestCase
{
    public function test_webhook_can_be_handled(): void
    {
        $payload = json_encode([
            'type' => 'file.uploaded',
            'data' => ['fileId' => 'test_123']
        ]);

        $webhooks = new Webhooks();
        // Note: In real tests, you'd need valid signatures
        // $event = $webhooks->handleWebhook($payload, $headers, $secret);
    }
}
```

## Best Practices

1. **Use dependency injection** - Inject `Uploads` and `Webhooks` into your controllers
2. **Validate file uploads** - Always validate file size and type
3. **Handle errors gracefully** - Return appropriate HTTP status codes
4. **Log webhook events** - Log all webhook events for debugging
5. **Use queues for heavy processing** - Process webhooks asynchronously when needed
6. **Secure webhook endpoints** - Always verify webhook signatures
7. **Set appropriate timeouts** - Configure timeouts based on your file sizes

## Troubleshooting

### "UPLOADTHING_API_KEY environment variable is not set"

Make sure your `.env` file has the `UPLOADTHING_API_KEY` set and you've run `php artisan config:clear`.

### Webhook verification fails

- Ensure `UPLOADTHING_WEBHOOK_SECRET` matches your UploadThing configuration
- Check that CSRF protection is disabled for webhook routes
- Verify the timestamp is within tolerance (default: 5 minutes)

### Uploads fail

- Check your API key permissions
- Verify network connectivity
- Check file size limits
- Review error logs for detailed error messages