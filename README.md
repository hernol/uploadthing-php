# UploadThing PHP Client

A high-quality, type-safe PHP client for the UploadThing REST API.

[![CI](https://github.com/uploadthing/uploadthing-php/workflows/CI/badge.svg)](https://github.com/uploadthing/uploadthing-php/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/uploadthing/uploadthing-php)](https://packagist.org/packages/uploadthing/uploadthing-php)
[![Latest Version](https://img.shields.io/packagist/v/uploadthing/uploadthing-php)](https://packagist.org/packages/uploadthing/uploadthing-php)
[![License](https://img.shields.io/packagist/l/uploadthing/uploadthing-php)](https://packagist.org/packages/uploadthing/uploadthing-php)

## Features

- ✅ **Type-safe**: Full PHP 8.1+ type declarations and strict typing
- ✅ **PSR-18 compliant**: Uses standard HTTP client interfaces
- ✅ **Comprehensive error handling**: Typed exceptions with detailed error information
- ✅ **Automatic retries**: Exponential backoff with configurable retry policies
- ✅ **Rate limiting**: Built-in rate limit handling and backoff
- ✅ **File uploads**: Support for multipart uploads, streaming, chunked uploads, progress tracking, and presigned URLs
- ✅ **Webhook verification**: Secure webhook signature validation with timestamp tolerance
- ✅ **Framework integrations**: Ready-to-use Laravel and Symfony integrations
- ✅ **Comprehensive testing**: 100% test coverage with unit and integration tests

## Quick Start

### Installation

```bash
composer require uploadthing/uploadthing-php
```

### Basic Usage

```php
<?php

use UploadThing\Client;
use UploadThing\Config;

// Create configuration
$config = Config::create()
    ->withApiKey('your-api-key')
    ->withBaseUrl('https://api.uploadthing.com');

// Create client
$client = new Client($config);

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
$handler = $client->createWebhookHandler('your-webhook-secret');
$handler->on('file.uploaded', function ($event) {
    echo "File uploaded: {$event->file->name}\n";
});
$event = $handler->handle($payload, $headers);

// List files
$files = $client->files()->listFiles();

// Get file details
$fileDetails = $client->files()->getFile($file->id);
```

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

## Documentation

- [📖 Full Documentation](docs/OVERVIEW.md)
- [🔐 Authentication Guide](docs/AUTH.md)
- [💡 Usage Examples](docs/USAGE.md)
- [🔗 API Endpoints](docs/ENDPOINTS/)
- [⚡ Laravel Integration](docs/LARAVEL.md)
- [🔧 Symfony Integration](docs/SYMFONY.md)

## Requirements

- PHP 8.1 or higher
- Composer

## Supported PHP Versions

| PHP Version | Support |
|-------------|---------|
| 8.1         | ✅ Full support |
| 8.2         | ✅ Full support |
| 8.3         | ✅ Full support |

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please see our [Security Policy](SECURITY.md).

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.

---

Made with ❤️ by the UploadThing team
