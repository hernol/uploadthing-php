# UploadThing PHP Client Documentation - v6 API

## Overview

The UploadThing PHP Client is a high-quality, type-safe PHP library for interacting with the UploadThing v6 REST API. It provides a clean, intuitive interface for managing file uploads, webhooks, and other UploadThing services.

## Features

- **Type Safety**: Full PHP 8.1+ type declarations and strict typing
- **PSR Compliance**: Uses PSR-18 HTTP client interfaces and PSR-3 logging
- **V6 API Compatible**: Uses correct UploadThing v6 endpoints
- **Error Handling**: Comprehensive exception handling with detailed error information
- **Retry Logic**: Automatic retries with exponential backoff
- **Rate Limiting**: Built-in rate limit handling
- **File Uploads**: Multiple upload methods (direct, presigned URL, chunked)
- **Webhook Support**: Secure webhook signature verification with HMAC-SHA256
- **Framework Integration**: Ready-to-use Laravel and Symfony integrations
- **Progress Tracking**: Real-time upload progress callbacks

## Installation

```bash
composer require uploadthing/uploadthing-php
```

## Quick Start

```php
<?php

use UploadThing\Client;
use UploadThing\Config;

// Create configuration
$config = Config::create()
    ->withApiKeyFromEnv('UPLOADTHING_API_KEY'); // Set your API key

// Create client
$client = Client::create($config);

// Upload a file
$file = $client->files()->uploadFile('/path/to/file.jpg', 'my-image.jpg');
echo "File uploaded: {$file->url}\n";

// List files
$fileList = $client->files()->listFiles();
foreach ($fileList->files as $file) {
    echo "- {$file->name} ({$file->size} bytes)\n";
}
```

## Configuration

The client is configured using the `Config` class, which provides a fluent interface for setting up the client:

```php
use UploadThing\Config;

$config = Config::create()
    ->withApiKey('your-api-key')
    ->withBaseUrl('https://api.uploadthing.com') // Default
    ->withApiVersion('v6') // Default
    ->withTimeout(30)
    ->withRetryPolicy(3, 1.0)
    ->withUserAgent('my-app/1.0.0');
```

### Configuration Options

- `apiKey`: Your UploadThing API key
- `baseUrl`: The base URL for the API (default: `https://api.uploadthing.com`)
- `apiVersion`: API version (default: `v6`)
- `timeout`: Request timeout in seconds (default: 30)
- `maxRetries`: Maximum number of retries (default: 3)
- `retryDelay`: Base delay for retries in seconds (default: 1.0)
- `userAgent`: User agent string for requests
- `logger`: PSR-3 logger instance for request/response logging
- `httpClient`: Custom HTTP client implementation

## Authentication

The client supports API key authentication. You can set the API key in several ways:

```php
// Direct API key
$config = Config::create()->withApiKey('ut_sk_...');

// From environment variable
$config = Config::create()->withApiKeyFromEnv('UPLOADTHING_API_KEY');

// Custom environment variable name
$config = Config::create()->withApiKeyFromEnv('MY_API_KEY');
```

## V6 API Endpoints

The client uses the following UploadThing v6 API endpoints:

| **Endpoint** | **Method** | **Purpose** |
|--------------|------------|-------------|
| `/v6/prepareUpload` | POST | Prepare file upload, get presigned URL |
| `/v6/uploadFiles` | POST | Upload files directly |
| `/v6/serverCallback` | POST | Complete upload process |
| `/v6/listFiles` | GET | List files with pagination |
| `/v6/getFile` | GET | Get file details |
| `/v6/deleteFile` | POST | Delete file |
| `/v6/renameFile` | POST | Rename file |

## Error Handling

The client provides comprehensive error handling with typed exceptions:

```php
use UploadThing\Exceptions\ApiException;
use UploadThing\Exceptions\AuthenticationException;
use UploadThing\Exceptions\RateLimitException;

try {
    $file = $client->files()->uploadFile('/path/to/file.jpg');
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Invalid API key: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Handle rate limiting
    echo "Rate limited. Retry after: " . $e->getRetryAfter();
} catch (ApiException $e) {
    // Handle other API errors
    echo "API Error: " . $e->getMessage();
    echo "Error Code: " . $e->getErrorCode();
}
```

## Resources

The client is organized into resource classes that correspond to different parts of the UploadThing v6 API:

- **Files**: Manage uploaded files using v6 endpoints
- **Uploads**: Handle file upload sessions using v6 endpoints
- **Webhooks**: Handle webhook events and verification

Each resource provides methods for common operations like listing, creating, updating, and deleting.

## Upload Methods

### Direct Upload (Small Files)
```php
$file = $client->files()->uploadContent($content, 'file.txt');
```

### Presigned URL Upload (Large Files)
```php
// Prepare upload
$prepareData = $client->uploads()->prepareUpload('file.jpg', 1024 * 1024, 'image/jpeg');

// Upload to presigned URL (client-side)
// Then complete the upload
$client->uploads()->serverCallback($prepareData['data'][0]['fileId']);
```

### Chunked Upload (Very Large Files)
```php
$file = $client->files()->uploadFileChunked('/path/to/large-file.zip', 'large-file.zip');
```

## Framework Integration

The client can be easily integrated with popular PHP frameworks:

- [Laravel Integration](LARAVEL.md)
- [Symfony Integration](SYMFONY.md)

## Testing

The client includes comprehensive test coverage and supports both unit and integration testing.

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.