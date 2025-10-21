# Symfony Integration - UploadThing v6 API

## Installation

Add the package to your Symfony project:

```bash
composer require uploadthing/uploadthing-php
```

## Service Configuration

Create `config/services.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    UploadThing\Client:
        factory: ['UploadThing\Client', 'create']
        arguments:
            $config: '@uploadthing.config'

    uploadthing.config:
        class: UploadThing\Config
        factory: ['UploadThing\Config', 'create']
        calls:
            - withApiKeyFromEnv: ['UPLOADTHING_API_KEY']
            - withBaseUrl: ['%uploadthing.base_url%']
            - withApiVersion: ['%uploadthing.api_version%']
            - withTimeout: ['%uploadthing.timeout%']
            - withRetryPolicy: ['%uploadthing.max_retries%', '%uploadthing.retry_delay%']

    UploadThing\Resources\Files:
        arguments:
            $httpClient: '@uploadthing.http_client'
            $authenticator: '@uploadthing.authenticator'
            $baseUrl: '%uploadthing.base_url%'
            $apiVersion: '%uploadthing.api_version%'

    UploadThing\Resources\Uploads:
        arguments:
            $httpClient: '@uploadthing.http_client'
            $authenticator: '@uploadthing.authenticator'
            $baseUrl: '%uploadthing.base_url%'
            $apiVersion: '%uploadthing.api_version%'

    UploadThing\Resources\Webhooks:
        arguments:
            $httpClient: '@uploadthing.http_client'
            $authenticator: '@uploadthing.authenticator'
            $baseUrl: '%uploadthing.base_url%'
            $apiVersion: '%uploadthing.api_version%'

    uploadthing.http_client:
        class: UploadThing\Http\GuzzleHttpClient
        factory: ['UploadThing\Http\GuzzleHttpClient', 'create']
        arguments:
            $timeout: '%uploadthing.timeout%'
            $userAgent: 'symfony-app/1.0.0'

    uploadthing.authenticator:
        class: UploadThing\Auth\ApiKeyAuthenticator
        arguments:
            $apiKey: '%uploadthing.api_key%'
```

## Configuration

Create `config/packages/uploadthing.yaml`:

```yaml
parameters:
    uploadthing.api_key: '%env(UPLOADTHING_API_KEY)%'
    uploadthing.base_url: '%env(default:UPLOADTHING_BASE_URL:https://api.uploadthing.com)%'
    uploadthing.api_version: '%env(default:UPLOADTHING_API_VERSION:v6)%'
    uploadthing.timeout: '%env(int:UPLOADTHING_TIMEOUT:30)%'
    uploadthing.max_retries: '%env(int:UPLOADTHING_MAX_RETRIES:3)%'
    uploadthing.retry_delay: '%env(float:UPLOADTHING_RETRY_DELAY:1.0)%'
    uploadthing.webhook_secret: '%env(UPLOADTHING_WEBHOOK_SECRET)%'
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

## Usage Examples

### In Controllers

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UploadThing\Client;
use UploadThing\Exceptions\ApiException;

class FileController extends AbstractController
{
    public function __construct(
        private Client $uploadThingClient
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        
        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        try {
            $uploadedFile = $this->uploadThingClient->files()->uploadFile(
                $file->getPathname(),
                $file->getClientOriginalName()
            );

            return new JsonResponse([
                'success' => true,
                'file' => [
                    'id' => $uploadedFile->id,
                    'name' => $uploadedFile->name,
                    'url' => $uploadedFile->url,
                    'size' => $uploadedFile->size,
                ],
            ]);
        } catch (ApiException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function list(): JsonResponse
    {
        try {
            $files = $this->uploadThingClient->files()->listFiles();
            
            return new JsonResponse([
                'success' => true,
                'files' => $files->files,
                'meta' => $files->meta,
            ]);
        } catch (ApiException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            $this->uploadThingClient->files()->deleteFile($id);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (ApiException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function prepareUpload(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['fileName']) || !isset($data['fileSize'])) {
            return new JsonResponse(['error' => 'fileName and fileSize are required'], 400);
        }

        try {
            $prepareData = $this->uploadThingClient->uploads()->prepareUpload(
                $data['fileName'],
                $data['fileSize'],
                $data['mimeType'] ?? null
            );

            return new JsonResponse([
                'success' => true,
                'data' => $prepareData,
            ]);
        } catch (ApiException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

### In Services

```php
<?php

namespace App\Service;

use UploadThing\Client;
use UploadThing\Exceptions\ApiException;

class FileUploadService
{
    public function __construct(
        private Client $uploadThingClient
    ) {}

    public function uploadFile(string $filePath, string $fileName): array
    {
        try {
            $file = $this->uploadThingClient->files()->uploadFile($filePath, $fileName);
            
            return [
                'success' => true,
                'file' => $file,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getFileList(int $limit = 50, ?string $cursor = null): array
    {
        try {
            $files = $this->uploadThingClient->files()->listFiles($limit, $cursor);
            
            return [
                'success' => true,
                'files' => $files->files,
                'meta' => $files->meta,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function prepareUpload(string $fileName, int $fileSize, ?string $mimeType = null): array
    {
        try {
            $prepareData = $this->uploadThingClient->uploads()->prepareUpload($fileName, $fileSize, $mimeType);
            
            return [
                'success' => true,
                'data' => $prepareData,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function uploadWithProgress(string $filePath, string $fileName, callable $progressCallback): array
    {
        try {
            $file = $this->uploadThingClient->files()->uploadFileWithProgress(
                $filePath,
                $fileName,
                $progressCallback
            );
            
            return [
                'success' => true,
                'file' => $file,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

### In Commands

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UploadThing\Client;
use UploadThing\Exceptions\ApiException;

#[AsCommand(
    name: 'app:sync-files',
    description: 'Sync files from UploadThing v6 API',
)]
class SyncFilesCommand extends Command
{
    public function __construct(
        private Client $uploadThingClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $files = $this->uploadThingClient->files()->listFiles();
            
            $output->writeln("Found {$files->meta->total} files");
            
            foreach ($files->files as $file) {
                $output->writeln("File: {$file->name} ({$file->size} bytes)");
            }
            
            return Command::SUCCESS;
        } catch (ApiException $e) {
            $output->writeln("<error>Sync failed: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
```

### In Event Listeners

```php
<?php

namespace App\EventListener;

use App\Event\FileUploadedEvent;
use UploadThing\Client;
use UploadThing\Exceptions\ApiException;

class FileUploadListener
{
    public function __construct(
        private Client $uploadThingClient
    ) {}

    public function onFileUploaded(FileUploadedEvent $event): void
    {
        try {
            // Process the uploaded file
            $file = $this->uploadThingClient->files()->getFile($event->getFileId());
            
            // Your processing logic here
            $this->processFile($file);
            
        } catch (ApiException $e) {
            // Log error and handle gracefully
            error_log("Failed to process file: " . $e->getMessage());
        }
    }

    private function processFile($file): void
    {
        // Your file processing logic here
        error_log("Processing file: {$file->name} ({$file->size} bytes)");
    }
}
```

## Webhook Handling (v6 API)

### Webhook Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UploadThing\Client;
use UploadThing\Exceptions\WebhookVerificationException;

class WebhookController extends AbstractController
{
    public function __construct(
        private Client $uploadThingClient
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $webhookEvent = $this->uploadThingClient->webhooks()->handleWebhookFromGlobals(
                $this->getParameter('uploadthing.webhook_secret')
            );
            
            // Process the webhook event
            $this->processWebhookEvent($webhookEvent);
            
            return new JsonResponse(['status' => 'success']);
        } catch (WebhookVerificationException $e) {
            return new JsonResponse(['error' => 'Invalid signature'], 401);
        }
    }

    private function processWebhookEvent($event): void
    {
        switch ($event->type) {
            case 'file.uploaded':
                error_log('File uploaded: ' . json_encode($event->data));
                break;
            case 'file.deleted':
                error_log('File deleted: ' . json_encode($event->data));
                break;
            case 'upload.completed':
                error_log('Upload completed: ' . json_encode($event->data));
                break;
            case 'upload.failed':
                error_log('Upload failed: ' . json_encode($event->data));
                break;
        }
    }
}
```

### Webhook Routes

```yaml
# config/routes.yaml
webhook_uploadthing:
    path: /webhooks/uploadthing
    controller: App\Controller\WebhookController::handle
    methods: [POST]
```

## Testing

### Unit Tests

```php
<?php

namespace App\Tests\Service;

use App\Service\FileUploadService;
use PHPUnit\Framework\TestCase;
use UploadThing\Client;
use UploadThing\Resources\Files;
use UploadThing\Resources\Uploads;
use UploadThing\Exceptions\ApiException;

class FileUploadServiceTest extends TestCase
{
    public function testUploadFile(): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockFiles = $this->createMock(Files::class);
        
        $mockClient->expects($this->once())
            ->method('files')
            ->willReturn($mockFiles);
            
        $mockFiles->expects($this->once())
            ->method('uploadFile')
            ->with('/path/to/file.jpg', 'test.jpg')
            ->willReturn((object) [
                'id' => 'file-123',
                'name' => 'test.jpg',
                'url' => 'https://example.com/test.jpg',
                'size' => 1024,
            ]);

        $service = new FileUploadService($mockClient);
        $result = $service->uploadFile('/path/to/file.jpg', 'test.jpg');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('file-123', $result['file']->id);
    }

    public function testPrepareUpload(): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockUploads = $this->createMock(Uploads::class);
        
        $mockClient->expects($this->once())
            ->method('uploads')
            ->willReturn($mockUploads);
            
        $mockUploads->expects($this->once())
            ->method('prepareUpload')
            ->with('test.jpg', 1024, 'image/jpeg')
            ->willReturn([
                'data' => [
                    [
                        'uploadUrl' => 'https://presigned-url.com',
                        'fileId' => 'file-123',
                    ]
                ]
            ]);

        $service = new FileUploadService($mockClient);
        $result = $service->prepareUpload('test.jpg', 1024, 'image/jpeg');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }
}
```

### Functional Tests

```php
<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileControllerTest extends WebTestCase
{
    public function testUploadFile(): void
    {
        $client = static::createClient();
        
        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            __DIR__ . '/../fixtures/test.jpg',
            'test.jpg',
            'image/jpeg',
            null,
            true
        );
        
        $client->request('POST', '/files/upload', [], ['file' => $file]);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('file', $response);
    }

    public function testPrepareUpload(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/files/prepare-upload', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'fileName' => 'test.jpg',
            'fileSize' => 1024,
            'mimeType' => 'image/jpeg',
        ]));
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
    }
}
```

## V6 API Specific Features

### Multiple Upload Methods

```php
// Direct upload (small files)
$file = $this->uploadThingClient->files()->uploadFile('/path/to/file.jpg');

// Presigned URL upload (large files)
$prepareData = $this->uploadThingClient->uploads()->prepareUpload('file.jpg', 1024 * 1024, 'image/jpeg');
// Upload to presigned URL (client-side)
$this->uploadThingClient->uploads()->serverCallback($prepareData['data'][0]['fileId']);

// Chunked upload (very large files)
$file = $this->uploadThingClient->files()->uploadFileChunked('/path/to/large-file.zip', 'large-file.zip');

// Multiple file upload
$results = $this->uploadThingClient->uploads()->uploadMultipleFiles([
    '/path/to/file1.jpg',
    '/path/to/file2.jpg',
    '/path/to/file3.jpg'
]);
```

### Progress Tracking

```php
$file = $this->uploadThingClient->files()->uploadFileWithProgress(
    '/path/to/large-file.zip',
    'large-file.zip',
    function ($uploaded, $total) {
        $percentage = ($uploaded / $total) * 100;
        error_log("Upload progress: {$percentage}%");
    }
);
```

## Best Practices

1. **Use Dependency Injection**: Let Symfony handle dependency injection for the UploadThing client
2. **Handle Errors**: Always wrap UploadThing calls in try-catch blocks
3. **Validate Input**: Validate file uploads before sending to UploadThing
4. **Use Services**: Create dedicated services for UploadThing operations
5. **Test Coverage**: Write comprehensive tests for your UploadThing integration
6. **Monitor Usage**: Monitor your UploadThing usage and set up alerts
7. **Use Environment Variables**: Store sensitive configuration in environment variables
8. **Log Operations**: Log UploadThing operations for debugging and monitoring
9. **Use V6 API**: Make sure you're using the correct v6 API endpoints
10. **Webhook Security**: Always verify webhook signatures for security
11. **Configuration**: Use Symfony's parameter system for configuration
12. **Error Handling**: Implement proper error handling and logging