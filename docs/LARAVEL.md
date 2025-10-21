# Laravel Integration - UploadThing v6 API

## Installation

Add the package to your Laravel project:

```bash
composer require uploadthing/uploadthing-php
```

## Service Provider

Create a service provider to register the UploadThing client:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use UploadThing\Client;
use UploadThing\Config;

class UploadThingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = Config::create()
                ->withApiKeyFromEnv('UPLOADTHING_API_KEY')
                ->withBaseUrl(config('uploadthing.base_url', 'https://api.uploadthing.com'))
                ->withApiVersion(config('uploadthing.api_version', 'v6'))
                ->withTimeout(config('uploadthing.timeout', 30))
                ->withRetryPolicy(
                    config('uploadthing.max_retries', 3),
                    config('uploadthing.retry_delay', 1.0)
                );

            return Client::create($config);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/uploadthing.php' => config_path('uploadthing.php'),
        ], 'config');
    }
}
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="App\Providers\UploadThingServiceProvider" --tag="config"
```

Create `config/uploadthing.php`:

```php
<?php

return [
    'api_key' => env('UPLOADTHING_API_KEY'),
    'base_url' => env('UPLOADTHING_BASE_URL', 'https://api.uploadthing.com'),
    'api_version' => env('UPLOADTHING_API_VERSION', 'v6'),
    'timeout' => env('UPLOADTHING_TIMEOUT', 30),
    'max_retries' => env('UPLOADTHING_MAX_RETRIES', 3),
    'retry_delay' => env('UPLOADTHING_RETRY_DELAY', 1.0),
    'webhook_secret' => env('UPLOADTHING_WEBHOOK_SECRET'),
];
```

## Environment Variables

Add to your `.env` file:

```env
UPLOADTHING_API_KEY=ut_sk_...
UPLOADTHING_BASE_URL=https://api.uploadthing.com
UPLOADTHING_API_VERSION=v6
UPLOADTHING_TIMEOUT=30
UPLOADTHING_MAX_RETRIES=3
UPLOADTHING_RETRY_DELAY=1.0
UPLOADTHING_WEBHOOK_SECRET=your-webhook-secret
```

## Facade

Create a facade for easy access:

```php
<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use UploadThing\Client;

/**
 * @method static \UploadThing\Resources\Files files()
 * @method static \UploadThing\Resources\Uploads uploads()
 * @method static \UploadThing\Resources\Webhooks webhooks()
 * @method static \UploadThing\Utils\UploadHelper uploadHelper()
 */
class UploadThing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
```

## Usage Examples

### In Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Facades\UploadThing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use UploadThing\Exceptions\ApiException;

class FileController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        try {
            $file = UploadThing::files()->uploadFile(
                $request->file('file')->getPathname(),
                $request->file('file')->getClientOriginalName()
            );

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => $file->url,
                    'size' => $file->size,
                ],
            ]);
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function list(): JsonResponse
    {
        try {
            $files = UploadThing::files()->listFiles();
            
            return response()->json([
                'success' => true,
                'files' => $files->files,
                'meta' => $files->meta,
            ]);
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            UploadThing::files()->deleteFile($id);
            
            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function prepareUpload(Request $request): JsonResponse
    {
        $request->validate([
            'fileName' => 'required|string',
            'fileSize' => 'required|integer|min:1',
            'mimeType' => 'nullable|string',
        ]);

        try {
            $prepareData = UploadThing::uploads()->prepareUpload(
                $request->input('fileName'),
                $request->input('fileSize'),
                $request->input('mimeType')
            );

            return response()->json([
                'success' => true,
                'data' => $prepareData,
            ]);
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

### In Jobs

```php
<?php

namespace App\Jobs;

use App\Facades\UploadThing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use UploadThing\Exceptions\ApiException;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $filePath,
        private string $fileName
    ) {}

    public function handle(): void
    {
        try {
            $file = UploadThing::files()->uploadFile($this->filePath, $this->fileName);
            
            // Process the uploaded file
            $this->processFile($file);
            
        } catch (ApiException $e) {
            $this->fail($e);
        }
    }

    private function processFile($file): void
    {
        // Your file processing logic here
        logger()->info('File uploaded successfully', [
            'file_id' => $file->id,
            'file_name' => $file->name,
            'file_url' => $file->url,
        ]);
    }
}
```

### In Commands

```php
<?php

namespace App\Console\Commands;

use App\Facades\UploadThing;
use Illuminate\Console\Command;
use UploadThing\Exceptions\ApiException;

class SyncFiles extends Command
{
    protected $signature = 'uploadthing:sync';
    protected $description = 'Sync files from UploadThing v6 API';

    public function handle(): int
    {
        try {
            $files = UploadThing::files()->listFiles();
            
            $this->info("Found {$files->meta->total} files");
            
            foreach ($files->files as $file) {
                $this->line("File: {$file->name} ({$file->size} bytes)");
            }
            
            return 0;
        } catch (ApiException $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return 1;
        }
    }
}
```

## Webhook Handling (v6 API)

### Webhook Controller

```php
<?php

namespace App\Http\Controllers;

use App\Facades\UploadThing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use UploadThing\Exceptions\WebhookVerificationException;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $webhookEvent = UploadThing::webhooks()->handleWebhookFromGlobals(
                config('uploadthing.webhook_secret')
            );
            
            // Process the webhook event
            $this->processWebhookEvent($webhookEvent);
            
            return response()->json(['status' => 'success']);
        } catch (WebhookVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }
    
    private function processWebhookEvent($event): void
    {
        switch ($event->type) {
            case 'file.uploaded':
                logger()->info('File uploaded', ['file_id' => $event->data['fileId']]);
                break;
            case 'file.deleted':
                logger()->info('File deleted', ['file_id' => $event->data['fileId']]);
                break;
            case 'upload.completed':
                logger()->info('Upload completed', ['file_id' => $event->data['fileId']]);
                break;
            case 'upload.failed':
                logger()->error('Upload failed', ['error' => $event->data['error']]);
                break;
        }
    }
}
```

### Middleware

Create middleware for webhook verification:

```php
<?php

namespace App\Http\Middleware;

use App\Facades\UploadThing;
use Closure;
use Illuminate\Http\Request;
use UploadThing\Exceptions\WebhookVerificationException;

class VerifyUploadThingWebhook
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $webhookEvent = UploadThing::webhooks()->handleWebhookFromGlobals(
                config('uploadthing.webhook_secret')
            );
            
            // Add the webhook event to the request for use in controllers
            $request->attributes->set('webhook_event', $webhookEvent);
            
            return $next($request);
        } catch (WebhookVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }
}
```

## Routes

Add routes for file operations:

```php
<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/files/upload', [FileController::class, 'upload']);
    Route::get('/files', [FileController::class, 'list']);
    Route::delete('/files/{id}', [FileController::class, 'delete']);
    Route::post('/files/prepare-upload', [FileController::class, 'prepareUpload']);
});

// Webhook route
Route::post('/webhooks/uploadthing', [WebhookController::class, 'handle'])
    ->middleware(VerifyUploadThingWebhook::class);
```

## Testing

Create tests for your UploadThing integration:

```php
<?php

namespace Tests\Feature;

use App\Facades\UploadThing;
use Tests\TestCase;
use UploadThing\Exceptions\ApiException;

class FileUploadTest extends TestCase
{
    public function test_can_upload_file(): void
    {
        // Mock the UploadThing client
        UploadThing::shouldReceive('files->uploadFile')
            ->once()
            ->andReturn((object) [
                'id' => 'file-123',
                'name' => 'test.jpg',
                'url' => 'https://example.com/test.jpg',
                'size' => 1024,
            ]);

        $response = $this->post('/files/upload', [
            'file' => $this->createTestFile(),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'file' => [
                'id' => 'file-123',
                'name' => 'test.jpg',
            ],
        ]);
    }

    public function test_can_prepare_upload(): void
    {
        UploadThing::shouldReceive('uploads->prepareUpload')
            ->once()
            ->with('test.jpg', 1024, 'image/jpeg')
            ->andReturn([
                'data' => [
                    [
                        'uploadUrl' => 'https://presigned-url.com',
                        'fileId' => 'file-123',
                    ]
                ]
            ]);

        $response = $this->post('/files/prepare-upload', [
            'fileName' => 'test.jpg',
            'fileSize' => 1024,
            'mimeType' => 'image/jpeg',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'data' => [
                    [
                        'uploadUrl' => 'https://presigned-url.com',
                        'fileId' => 'file-123',
                    ]
                ]
            ],
        ]);
    }

    private function createTestFile()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg');
        return $file;
    }
}
```

## V6 API Specific Features

### Multiple Upload Methods

```php
// Direct upload (small files)
$file = UploadThing::files()->uploadFile('/path/to/file.jpg');

// Presigned URL upload (large files)
$prepareData = UploadThing::uploads()->prepareUpload('file.jpg', 1024 * 1024, 'image/jpeg');
// Upload to presigned URL (client-side)
UploadThing::uploads()->serverCallback($prepareData['data'][0]['fileId']);

// Chunked upload (very large files)
$file = UploadThing::files()->uploadFileChunked('/path/to/large-file.zip', 'large-file.zip');

// Multiple file upload
$results = UploadThing::uploads()->uploadMultipleFiles([
    '/path/to/file1.jpg',
    '/path/to/file2.jpg',
    '/path/to/file3.jpg'
]);
```

### Progress Tracking

```php
$file = UploadThing::files()->uploadFileWithProgress(
    '/path/to/large-file.zip',
    'large-file.zip',
    function ($uploaded, $total) {
        $percentage = ($uploaded / $total) * 100;
        logger()->info("Upload progress: {$percentage}%");
    }
);
```

## Best Practices

1. **Use Dependency Injection**: Inject the Client class instead of using the facade when possible
2. **Handle Errors**: Always wrap UploadThing calls in try-catch blocks
3. **Validate Input**: Validate file uploads before sending to UploadThing
4. **Use Queues**: For large file uploads, use Laravel queues to process them asynchronously
5. **Cache Results**: Cache file lists and metadata when appropriate
6. **Monitor Usage**: Monitor your UploadThing usage and set up alerts
7. **Use V6 API**: Make sure you're using the correct v6 API endpoints
8. **Webhook Security**: Always verify webhook signatures for security
9. **Environment Variables**: Store sensitive configuration in environment variables
10. **Logging**: Enable logging for debugging and monitoring