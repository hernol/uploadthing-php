# Usage Guide

## Basic Usage

### Creating a Client

```php
<?php

use UploadThing\Client;
use UploadThing\Config;

// Create configuration
$config = Config::create()
    ->withApiKey('your-api-key')
    ->withBaseUrl('https://api.uploadthing.com');

// Create client
$client = Client::create($config);
```

### Working with Files

#### Upload a File

```php
// Upload from file path
$file = $client->files()->uploadFile('/path/to/image.jpg');

// Upload with custom name
$file = $client->files()->uploadFile('/path/to/image.jpg', 'my-image.jpg');

// Upload from string content
$file = $client->files()->uploadContent($content, 'document.pdf');

// Upload from stream resource
$stream = fopen('/path/to/file.pdf', 'rb');
$file = $client->files()->uploadStream($stream, 'document.pdf');
fclose($stream);
```

#### Advanced Upload Methods

```php
// Upload with progress tracking
$file = $client->files()->uploadFileWithProgress(
    '/path/to/large-file.zip',
    'archive.zip',
    function ($uploaded, $total) {
        $percentage = ($uploaded / $total) * 100;
        echo "Upload progress: {$percentage}%\n";
    }
);

// Upload large files using chunked upload
$file = $client->files()->uploadFileChunked(
    '/path/to/very-large-file.zip',
    'archive.zip',
    function ($uploaded, $total) {
        echo "Chunk uploaded: {$uploaded}/{$total} bytes\n";
    }
);

// Upload using presigned URL (for very large files)
$file = $client->uploads()->uploadWithPresignedUrl(
    '/path/to/huge-file.zip',
    'archive.zip'
);
```

#### Using the Upload Helper

```php
// Get the upload helper for unified upload methods
$uploadHelper = $client->uploadHelper();

// Upload with automatic method selection
$file = $uploadHelper->uploadFile('/path/to/file.jpg');

// Upload with custom options
$file = $uploadHelper->uploadFile(
    '/path/to/file.jpg',
    'my-image.jpg',
    'image/jpeg',
    function ($uploaded, $total) {
        echo "Progress: {$uploaded}/{$total}\n";
    },
    false // Don't use presigned URL
);

// Upload multiple files in parallel
$results = $uploadHelper->uploadMultipleFiles([
    '/path/to/file1.jpg',
    '/path/to/file2.jpg',
    '/path/to/file3.jpg'
], function ($progress, $total) {
    echo "Overall progress: {$progress}/{$total}\n";
});

// Validate file before upload
$uploadHelper->validateFile('/path/to/file.jpg', [
    'maxSize' => 10 * 1024 * 1024, // 10MB
    'allowedTypes' => ['jpg', 'png', 'gif'],
    'allowedMimeTypes' => ['image/jpeg', 'image/png', 'image/gif']
]);
```

#### List Files

```php
// List all files
$files = $client->files()->listFiles();

// List with pagination
$files = $client->files()->listFiles(limit: 20, cursor: 'next-cursor');

// Access file data
foreach ($files->files as $file) {
    echo "File: {$file->name} ({$file->size} bytes)\n";
    echo "URL: {$file->url}\n";
}
```

#### Get File Details

```php
$file = $client->files()->getFile('file-id');
echo "Name: {$file->name}\n";
echo "Size: {$file->size} bytes\n";
echo "MIME Type: {$file->mimeType}\n";
echo "Created: {$file->createdAt->format('Y-m-d H:i:s')}\n";
```

#### Delete a File

```php
$client->files()->deleteFile('file-id');
```

#### Rename a File

```php
$file = $client->files()->renameFile('file-id', 'new-name.jpg');
```

### Working with Uploads

#### Create Upload Session

```php
$session = $client->uploads()->createUploadSession(
    fileName: 'large-file.zip',
    fileSize: 1024 * 1024 * 100, // 100MB
    mimeType: 'application/zip'
);

echo "Upload ID: {$session->id}\n";
echo "Upload URL: {$session->uploadUrl}\n";
```

#### Check Upload Status

```php
$session = $client->uploads()->getUploadStatus('upload-id');
echo "Status: {$session->status}\n";
echo "Uploaded: {$session->uploadedBytes} bytes\n";
```

#### Complete Upload

```php
$session = $client->uploads()->completeUpload('upload-id');
echo "File ID: {$session->fileId}\n";
```

#### Cancel Upload

```php
$client->uploads()->cancelUpload('upload-id');
```

#### Presigned URLs

```php
// Get a presigned URL for client-side uploads
$presignedData = $client->uploads()->getPresignedUrl(
    fileName: 'large-file.zip',
    fileSize: 100 * 1024 * 1024, // 100MB
    mimeType: 'application/zip'
);

// The response contains:
// - uploadUrl: URL to upload the file to
// - fileId: ID of the file after upload
// - fields: Additional form fields required for upload

// Upload file using presigned URL
$file = $client->uploads()->uploadWithPresignedUrl(
    '/path/to/large-file.zip',
    'archive.zip',
    'application/zip'
);
```

### Working with Webhooks

#### List Webhooks

```php
$webhooks = $client->webhooks()->listWebhooks();
foreach ($webhooks->webhooks as $webhook) {
    echo "Webhook: {$webhook->url}\n";
    echo "Events: " . implode(', ', $webhook->events) . "\n";
}
```

#### Create Webhook

```php
$webhook = $client->webhooks()->createWebhook(
    url: 'https://example.com/webhook',
    events: ['file.uploaded', 'file.deleted']
);
```

#### Update Webhook

```php
$webhook = $client->webhooks()->updateWebhook(
    webhookId: 'webhook-id',
    url: 'https://example.com/new-webhook',
    events: ['file.uploaded']
);
```

#### Delete Webhook

```php
$client->webhooks()->deleteWebhook('webhook-id');
```

#### Webhook Signature Verification

```php
use UploadThing\Utils\WebhookVerifier;
use UploadThing\Exceptions\WebhookVerificationException;

// Create a webhook verifier
$verifier = $client->createWebhookVerifier('your-webhook-secret');

// Verify webhook signature
try {
    $isValid = $verifier->verify($payload, $headers);
    if ($isValid) {
        echo "Webhook signature is valid\n";
    } else {
        echo "Webhook signature is invalid\n";
    }
} catch (WebhookVerificationException $e) {
    echo "Verification failed: " . $e->getMessage() . "\n";
}

// Verify and parse webhook payload
try {
    $event = $verifier->verifyAndParse($payload, $headers);
    echo "Event type: " . $event->getEventType() . "\n";
} catch (WebhookVerificationException $e) {
    echo "Verification failed: " . $e->getMessage() . "\n";
}
```

#### Webhook Event Handling

```php
use UploadThing\Utils\WebhookHandler;
use UploadThing\Models\FileUploadedEvent;
use UploadThing\Models\FileDeletedEvent;

// Create a webhook handler
$handler = $client->createWebhookHandler('your-webhook-secret');

// Register event handlers
$handler
    ->on('file.uploaded', function (FileUploadedEvent $event) {
        echo "File uploaded: {$event->file->name}\n";
        echo "File ID: {$event->file->id}\n";
        echo "File URL: {$event->file->url}\n";
        
        // Process the uploaded file
        $this->processUploadedFile($event->file);
    })
    ->on('file.deleted', function (FileDeletedEvent $event) {
        echo "File deleted: {$event->fileName}\n";
        echo "File ID: {$event->fileId}\n";
        
        // Clean up related data
        $this->cleanupFileData($event->fileId);
    })
    ->on('upload.completed', function (UploadCompletedEvent $event) {
        echo "Upload completed: {$event->uploadId}\n";
        echo "File ID: {$event->fileId}\n";
    })
    ->on('upload.failed', function (UploadFailedEvent $event) {
        echo "Upload failed: {$event->uploadId}\n";
        echo "Error: {$event->errorMessage}\n";
        
        // Log the error
        error_log("Upload failed: " . $event->errorMessage);
    });

// Handle webhook payload
try {
    $event = $handler->handle($payload, $headers);
    echo "Processed event: " . $event->getEventType() . "\n";
} catch (WebhookVerificationException $e) {
    echo "Webhook verification failed: " . $e->getMessage() . "\n";
}
```

#### Advanced Webhook Handling

```php
// Handle multiple event types with the same handler
$handler->onEvents(['file.uploaded', 'file.updated'], function ($event) {
    echo "File event: " . $event->getEventType() . "\n";
    
    if ($event instanceof FileUploadedEvent) {
        echo "New file: {$event->file->name}\n";
    } elseif ($event instanceof FileUpdatedEvent) {
        echo "Updated file: {$event->file->name}\n";
        echo "Changes: " . json_encode($event->changes) . "\n";
    }
});

// Handle all events with a generic handler
$handler->on('*', function (WebhookEvent $event) {
    echo "Received event: " . $event->getEventType() . "\n";
    echo "Event ID: " . $event->id . "\n";
    echo "Timestamp: " . $event->timestamp->format('Y-m-d H:i:s') . "\n";
});

// Custom timestamp tolerance
$handler = $client->createWebhookHandler('your-secret', 600); // 10 minutes tolerance

// Parse webhook without verification (for testing)
$event = $handler->handleUnverified($payload);
echo "Event type: " . $event->getEventType() . "\n";
```

#### Webhook Event Types

The following webhook event types are supported:

- **`file.uploaded`** - When a file is successfully uploaded
- **`file.deleted`** - When a file is deleted
- **`file.updated`** - When file metadata is updated
- **`upload.started`** - When an upload session is created
- **`upload.completed`** - When an upload is completed
- **`upload.failed`** - When an upload fails
- **`webhook.created`** - When a webhook is created
- **`webhook.updated`** - When a webhook is updated
- **`webhook.deleted`** - When a webhook is deleted

#### Framework Integration Examples

**Laravel Controller:**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UploadThing\Client;
use UploadThing\Exceptions\WebhookVerificationException;

class WebhookController extends Controller
{
    public function __construct(private Client $uploadThingClient) {}

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        
        try {
            $handler = $this->uploadThingClient->createWebhookHandler(
                config('uploadthing.webhook_secret')
            );
            
            $handler
                ->on('file.uploaded', [$this, 'handleFileUploaded'])
                ->on('file.deleted', [$this, 'handleFileDeleted']);
            
            $event = $handler->handle($payload, $headers);
            
            return response()->json(['status' => 'success']);
        } catch (WebhookVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }
    
    public function handleFileUploaded($event)
    {
        // Process uploaded file
        logger()->info('File uploaded', ['file_id' => $event->file->id]);
    }
    
    public function handleFileDeleted($event)
    {
        // Clean up related data
        logger()->info('File deleted', ['file_id' => $event->fileId]);
    }
}
```

**Symfony Controller:**

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
    public function __construct(private Client $uploadThingClient) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        
        try {
            $handler = $this->uploadThingClient->createWebhookHandler(
                $this->getParameter('uploadthing.webhook_secret')
            );
            
            $handler
                ->on('file.uploaded', [$this, 'handleFileUploaded'])
                ->on('file.deleted', [$this, 'handleFileDeleted']);
            
            $event = $handler->handle($payload, $headers);
            
            return new JsonResponse(['status' => 'success']);
        } catch (WebhookVerificationException $e) {
            return new JsonResponse(['error' => 'Invalid signature'], 401);
        }
    }
    
    public function handleFileUploaded($event): void
    {
        // Process uploaded file
        $this->logger->info('File uploaded', ['file_id' => $event->file->id]);
    }
    
    public function handleFileDeleted($event): void
    {
        // Clean up related data
        $this->logger->info('File deleted', ['file_id' => $event->fileId]);
    }
}
```

## Advanced Usage

### Custom Configuration

```php
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create logger
$logger = new Logger('uploadthing');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

// Configure client with custom settings
$config = Config::create()
    ->withApiKeyFromEnv()
    ->withBaseUrl('https://staging-api.uploadthing.com')
    ->withTimeout(60)
    ->withRetryPolicy(maxRetries: 5, retryDelay: 2.0)
    ->withUserAgent('my-app/1.0.0')
    ->withLogger($logger);

$client = Client::create($config);
```

### Error Handling

```php
use UploadThing\Exceptions\ApiException;
use UploadThing\Exceptions\AuthenticationException;
use UploadThing\Exceptions\RateLimitException;
use UploadThing\Exceptions\ValidationException;

try {
    $file = $client->files()->uploadFile('/path/to/file.jpg');
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Authentication failed: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Handle rate limiting
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation failed: " . $e->getMessage();
    $errors = $e->getValidationErrors();
    foreach ($errors as $error) {
        echo "Error: " . $error['message'] . "\n";
    }
} catch (ApiException $e) {
    // Handle other API errors
    echo "API Error: " . $e->getMessage();
    echo "Error Code: " . $e->getErrorCode();
    echo "HTTP Status: " . $e->getCode();
} catch (\Exception $e) {
    // Handle unexpected errors
    echo "Unexpected error: " . $e->getMessage();
}
```

### Custom HTTP Client

```php
use UploadThing\Http\HttpClientInterface;
use GuzzleHttp\Client as GuzzleClient;

class CustomHttpClient implements HttpClientInterface
{
    public function __construct(private GuzzleClient $guzzleClient) {}

    public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        // Add custom logic here
        return $this->guzzleClient->send($request);
    }
}

$customClient = new CustomHttpClient(new GuzzleClient());
$config = Config::create()
    ->withApiKeyFromEnv()
    ->withHttpClient($customClient);

$client = Client::create($config);
```

### Batch Operations

```php
// Upload multiple files
$files = [];
$filePaths = ['/path/to/file1.jpg', '/path/to/file2.jpg', '/path/to/file3.jpg'];

foreach ($filePaths as $filePath) {
    try {
        $file = $client->files()->uploadFile($filePath);
        $files[] = $file;
        echo "Uploaded: {$file->name}\n";
    } catch (ApiException $e) {
        echo "Failed to upload {$filePath}: " . $e->getMessage() . "\n";
    }
}
```

### Pagination

```php
// Iterate through all files using pagination
$cursor = null;
$allFiles = [];

do {
    $response = $client->files()->listFiles(limit: 50, cursor: $cursor);
    $allFiles = array_merge($allFiles, $response->files);
    $cursor = $response->meta->nextCursor;
} while ($response->meta->hasMore);

echo "Total files: " . count($allFiles) . "\n";
```

## Best Practices

### 1. Use Environment Variables

Always use environment variables for API keys:

```php
$config = Config::create()->withApiKeyFromEnv();
```

### 2. Handle Errors Gracefully

Always wrap API calls in try-catch blocks:

```php
try {
    $file = $client->files()->uploadFile($filePath);
} catch (ApiException $e) {
    // Log error and handle gracefully
    error_log("Upload failed: " . $e->getMessage());
    return false;
}
```

### 3. Use Appropriate Timeouts

Set appropriate timeouts for your use case:

```php
$config = Config::create()
    ->withApiKeyFromEnv()
    ->withTimeout(120); // 2 minutes for large uploads
```

### 4. Enable Logging in Development

Use logging to debug issues:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('uploadthing');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$config = Config::create()
    ->withApiKeyFromEnv()
    ->withLogger($logger);
```

### 5. Validate Input

Validate file paths and parameters before making API calls:

```php
if (!file_exists($filePath)) {
    throw new \InvalidArgumentException("File does not exist: {$filePath}");
}

if (!is_readable($filePath)) {
    throw new \InvalidArgumentException("File is not readable: {$filePath}");
}
```
