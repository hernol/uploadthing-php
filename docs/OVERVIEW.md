# UploadThing PHP Client Documentation

## Overview

The UploadThing PHP Client is a high-quality, type-safe PHP library for interacting with the UploadThing REST API. It provides a clean, intuitive interface for managing file uploads, webhooks, and other UploadThing services.

## Features

- **Type Safety**: Full PHP 8.1+ type declarations and strict typing
- **PSR Compliance**: Uses PSR-18 HTTP client interfaces and PSR-3 logging
- **Error Handling**: Comprehensive exception handling with detailed error information
- **Retry Logic**: Automatic retries with exponential backoff
- **Rate Limiting**: Built-in rate limit handling
- **File Uploads**: Support for multipart uploads and streaming
- **Webhook Support**: Secure webhook signature verification
- **Framework Integration**: Ready-to-use Laravel and Symfony integrations

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
    ->withApiKey('your-api-key')
    ->withBaseUrl('https://api.uploadthing.com');

// Create client
$client = new Client($config);

// Upload a file
$file = $client->files()->uploadFile('/path/to/file.jpg');

// List files
$files = $client->files()->listFiles();

// Get file details
$fileDetails = $client->files()->getFile($file->id);
```

## Configuration

The client is configured using the `Config` class, which provides a fluent interface for setting up the client:

```php
use UploadThing\Config;

$config = Config::create()
    ->withApiKey('your-api-key')
    ->withBaseUrl('https://api.uploadthing.com')
    ->withTimeout(30)
    ->withRetryPolicy(3, 1.0)
    ->withUserAgent('my-app/1.0.0');
```

### Configuration Options

- `apiKey`: Your UploadThing API key
- `baseUrl`: The base URL for the API (default: `https://api.uploadthing.com`)
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

The client is organized into resource classes that correspond to different parts of the UploadThing API:

- **Files**: Manage uploaded files
- **Uploads**: Handle file upload sessions
- **Webhooks**: Configure webhook endpoints

Each resource provides methods for common operations like listing, creating, updating, and deleting.

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
