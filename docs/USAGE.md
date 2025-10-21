# Usage Guide - UploadThing v6 API

## Basic Usage

### Creating a Client

```php
<?php

use UploadThing\Client;
use UploadThing\Config;

// Create configuration
$config = Config::create()
    ->withApiKeyFromEnv('UPLOADTHING_API_KEY'); // Set your API key

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

### Working with Uploads (v6 API)

#### Prepare Upload

```php
// Prepare upload using v6 API
$prepareData = $client->uploads()->prepareUpload(
    fileName: 'large-file.zip',
    fileSize: 1024 * 1024 * 100, // 100MB
    mimeType: 'application/zip'
);

echo "Upload URL: {$prepareData['data'][0]['uploadUrl']}\n";
echo "File ID: {$prepareData['data'][0]['fileId']}\n";
```

#### Upload Files Directly

```php
// Upload files using v6 uploadFiles endpoint
$files = [
    [
        'name' => 'file1.jpg',
        'size' => 1024 * 1024,
        'type' => 'image/jpeg',
        'content' => base64_encode($content)
    ]
];

$result = $client->uploads()->uploadFiles($files);
```

#### Server Callback

```php
// Complete upload using v6 serverCallback endpoint
$client->uploads()->serverCallback('file-id', 'completed');
```

#### Upload with Presigned URL

```php
// Upload using presigned URL flow
$file = $client->uploads()->uploadWithPresignedUrl(
    '/path/to/large-file.zip',
    'archive.zip',
    'application/zip'
);
```

#### Upload Multiple Files

```php
// Upload multiple files in parallel
$filePaths = ['/path/to/file1.jpg', '/path/to/file2.png', '/path/to/file3.pdf'];
$results = $client->uploads()->uploadMultipleFiles($filePaths, function ($uploaded, $total) {
    echo "Overall progress: {$uploaded}/{$total}\n";
});

foreach ($results as $result) {
    if ($result['success']) {
        echo "✓ {$result['file']->name} uploaded\n";
    } else {
        echo "✗ Failed: {$result['error']}\n";
    }
}
```

### Working with Webhooks (v6 API)

**Note**: UploadThing v6 API does not have traditional webhook management endpoints. This section covers webhook event handling and verification.

#### Webhook Signature Verification

```php
use UploadThing\Exceptions\WebhookVerificationException;

// Verify webhook signature
try {
    $isValid = $client->webhooks()->verifySignature($payload, $signature, $secret);
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
    $event = $client->webhooks()->verifyAndParse($payload, $signature, $secret);
    echo "Event type: " . $event->type . "\n";
} catch (WebhookVerificationException $e) {
    echo "Verification failed: " . $e->getMessage() . "\n";
}
```

#### Webhook Event Handling

```php
// Handle incoming webhook request
$webhookEvent = $client->webhooks()->handleWebhook($payload, $headers, $secret);
echo "Event type: {$webhookEvent->type}\n";
echo "Data: " . json_encode($webhookEvent->data) . "\n";

// Handle webhook from PHP superglobals
$webhookEvent = $client->webhooks()->handleWebhookFromGlobals($secret);
```

#### Custom Webhook Handler

```php
class MyWebhookHandler extends \UploadThing\Resources\Webhooks
{
    protected function onUploadCompleted(\UploadThing\Models\WebhookEvent $event): void
    {
        echo "Upload completed: " . json_encode($event->data) . "\n";
    }
    
    protected function onFileDeleted(\UploadThing\Models\WebhookEvent $event): void
    {
        echo "File deleted: " . json_encode($event->data) . "\n";
    }
}

$handler = new MyWebhookHandler($httpClient, $authenticator, $baseUrl, $apiVersion);
$handler->processUploadCompletion('file-id', ['metadata' => 'value']);
```

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
            $webhookEvent = $this->uploadThingClient->webhooks()->handleWebhook(
                $payload, 
                $headers, 
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
        }
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
            $webhookEvent = $this->uploadThingClient->webhooks()->handleWebhook(
                $payload, 
                $headers, 
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
                $this->logger->info('File uploaded', ['file_id' => $event->data['fileId']]);
                break;
            case 'file.deleted':
                $this->logger->info('File deleted', ['file_id' => $event->data['fileId']]);
                break;
        }
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
    ->withBaseUrl('https://api.uploadthing.com')
    ->withApiVersion('v6')
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

### 6. Use V6 API Endpoints

Make sure you're using the correct v6 API endpoints:

```php
// ✅ Correct - uses v6 API
$file = $client->files()->uploadFile('/path/to/file.jpg');

// ✅ Correct - uses v6 prepareUpload endpoint
$prepareData = $client->uploads()->prepareUpload('file.jpg', 1024 * 1024, 'image/jpeg');

// ✅ Correct - uses v6 serverCallback endpoint
$client->uploads()->serverCallback('file-id', 'completed');
```