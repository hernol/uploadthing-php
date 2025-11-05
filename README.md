# UploadThing PHP Client - v6 API Compatible

A high-quality, type-safe PHP client for the UploadThing v6 REST API.

[![CI](https://github.com/hernol/uploadthing-php/workflows/CI/badge.svg)](https://github.com/hernol/uploadthing-php/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/hernol/uploadthing-php)](https://packagist.org/packages/hernol/uploadthing-php)
[![Latest Version](https://img.shields.io/packagist/v/hernol/uploadthing-php)](https://packagist.org/packages/hernol/uploadthing-php)
[![License](https://img.shields.io/packagist/l/hernol/uploadthing-php)](https://packagist.org/packages/hernol/uploadthing-php)

## Features

- ✅ **V6 API Compatible**: Uses correct UploadThing v6 endpoints
- ✅ **Type-safe**: Full PHP 8.1+ type declarations and strict typing
- ✅ **PSR-18 compliant**: Uses standard HTTP client interfaces
- ✅ **Comprehensive error handling**: Typed exceptions with detailed error information
- ✅ **Automatic retries**: Exponential backoff with configurable retry policies
- ✅ **Rate limiting**: Built-in rate limit handling and backoff
- ✅ **Multiple upload methods**: Direct upload, presigned URL, chunked uploads
- ✅ **Progress tracking**: Real-time upload progress callbacks
- ✅ **Webhook verification**: HMAC-SHA256 signature validation with timestamp tolerance
- ✅ **Framework integrations**: Ready-to-use Laravel and Symfony integrations
- ✅ **Comprehensive testing**: 100% test coverage with unit and integration tests

## Quick Start

### Installation

```bash
composer require hernol/uploadthing-php
```

### Basic Usage

```php
<?php

use UploadThing\Client;
use UploadThing\Config;

// Create configuration
$config = Config::create()
    ->withApiKeyFromEnv('UPLOADTHING_API_KEY'); // Set your API key

// Create client
$client = Client::create($config);

// Upload a file (automatic method selection)
$file = $client->uploadHelper()->uploadFile('/path/to/file.jpg');

// Upload with progress tracking
$file = $client->files()->uploadFileWithProgress(
    '/path/to/large-file.zip',
    'archive.zip',
    function ($uploaded, $total) {
        echo "Progress: " . round(($uploaded / $total) * 100, 2) . "%\n";
    }
);

// Upload using presigned URL for very large files
$file = $client->uploads()->uploadWithPresignedUrl('/path/to/huge-file.zip');

// Handle webhooks with signature verification
$webhookEvent = $client->webhooks()->handleWebhookFromGlobals('your-webhook-secret');
echo "Event type: {$webhookEvent->type}\n";

// List files
$files = $client->files()->listFiles();

// Get file details
$fileDetails = $client->files()->getFile($file->id);
```

### V6 API Endpoints

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

### Authentication

```php
<?php

use UploadThing\Config;

// Using API key
$config = Config::create()
    ->withApiKey('ut_sk_...');

// Using environment variable
$config = Config::create()
    ->withApiKeyFromEnv('UPLOADTHING_API_KEY');

// With v6 API version (default)
$config = Config::create()
    ->withApiKeyFromEnv()
    ->withApiVersion('v6');
```

### Error Handling

```php
<?php

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

### Upload Methods

#### Direct Upload (Small Files)
```php
$file = $client->files()->uploadContent($content, 'file.txt');
```

#### Presigned URL Upload (Large Files)
```php
// Prepare upload
$prepareData = $client->uploads()->prepareUpload('file.jpg', 1024 * 1024, 'image/jpeg');

// Upload to presigned URL (client-side)
// Then complete the upload
$client->uploads()->serverCallback($prepareData['data'][0]['fileId']);
```

#### Chunked Upload (Very Large Files)
```php
$file = $client->files()->uploadFileChunked('/path/to/large-file.zip', 'large-file.zip');
```

#### Multiple File Upload
```php
$results = $client->uploads()->uploadMultipleFiles([
    '/path/to/file1.jpg',
    '/path/to/file2.jpg',
    '/path/to/file3.jpg'
]);
```

## Documentation

- [📖 Full Documentation](docs/OVERVIEW.md)
- [🔐 Authentication Guide](docs/AUTH.md)
- [💡 Usage Examples](docs/USAGE.md)
- [⚡ Laravel Integration](docs/LARAVEL.md)
- [🔧 Symfony Integration](docs/SYMFONY.md)
- [📋 API Inventory](INVENTORY.md)
- [📝 V6 API Examples](README_V6.md)

## Requirements

- PHP 8.1 or higher
- Composer
- UploadThing API key

## Supported PHP Versions

| PHP Version | Support |
|-------------|---------|
| 8.1         | ✅ Full support |
| 8.2         | ✅ Full support |
| 8.3         | ✅ Full support |

## Framework Integration

### Laravel

```php
// In your controller
use App\Facades\UploadThing;

$file = UploadThing::files()->uploadFile('/path/to/file.jpg');
```

### Symfony

```php
// In your controller
public function __construct(private Client $uploadThingClient) {}

public function upload(Request $request): JsonResponse
{
    $file = $this->uploadThingClient->files()->uploadFile(
        $request->files->get('file')->getPathname(),
        $request->files->get('file')->getClientOriginalName()
    );
    
    return new JsonResponse(['file' => $file]);
}
```

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please see our [Security Policy](SECURITY.md).

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.

---

