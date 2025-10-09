# Laravel Integration

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
    'timeout' => env('UPLOADTHING_TIMEOUT', 30),
    'max_retries' => env('UPLOADTHING_MAX_RETRIES', 3),
    'retry_delay' => env('UPLOADTHING_RETRY_DELAY', 1.0),
];
```

## Environment Variables

Add to your `.env` file:

```env
UPLOADTHING_API_KEY=ut_sk_...
UPLOADTHING_BASE_URL=https://api.uploadthing.com
UPLOADTHING_TIMEOUT=30
UPLOADTHING_MAX_RETRIES=3
UPLOADTHING_RETRY_DELAY=1.0
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
            
        } catch (\Exception $e) {
            $this->fail($e);
        }
    }

    private function processFile($file): void
    {
        // Your file processing logic here
    }
}
```

### In Commands

```php
<?php

namespace App\Console\Commands;

use App\Facades\UploadThing;
use Illuminate\Console\Command;

class SyncFiles extends Command
{
    protected $signature = 'uploadthing:sync';
    protected $description = 'Sync files from UploadThing';

    public function handle(): int
    {
        try {
            $files = UploadThing::files()->listFiles();
            
            $this->info("Found {$files->meta->total} files");
            
            foreach ($files->files as $file) {
                $this->line("File: {$file->name} ({$file->size} bytes)");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return 1;
        }
    }
}
```

## Middleware

Create middleware for webhook verification:

```php
<?php

namespace App\Http\Middleware;

use App\Facades\UploadThing;
use Closure;
use Illuminate\Http\Request;

class VerifyUploadThingWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-UploadThing-Signature');
        $payload = $request->getContent();
        
        if (!$signature || !UploadThing::webhooks()->verifySignature($payload, $signature, config('uploadthing.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        return $next($request);
    }
}
```

## Routes

Add routes for file operations:

```php
<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/files/upload', [FileController::class, 'upload']);
    Route::get('/files', [FileController::class, 'list']);
    Route::delete('/files/{id}', [FileController::class, 'delete']);
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

    private function createTestFile()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg');
        return $file;
    }
}
```

## Best Practices

1. **Use Dependency Injection**: Inject the Client class instead of using the facade when possible
2. **Handle Errors**: Always wrap UploadThing calls in try-catch blocks
3. **Validate Input**: Validate file uploads before sending to UploadThing
4. **Use Queues**: For large file uploads, use Laravel queues to process them asynchronously
5. **Cache Results**: Cache file lists and metadata when appropriate
6. **Monitor Usage**: Monitor your UploadThing usage and set up alerts
