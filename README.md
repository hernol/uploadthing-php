# UploadThing PHP Client - Laravel Package

A simplified, Laravel-focused PHP client for the UploadThing v6 REST API.

[![CI](https://github.com/hernol/uploadthing-php/workflows/CI/badge.svg)](https://github.com/hernol/uploadthing-php/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/hernol/uploadthing-php)](https://packagist.org/packages/hernol/uploadthing-php)
[![Latest Version](https://img.shields.io/packagist/v/hernol/uploadthing-php)](https://packagist.org/packages/hernol/uploadthing-php)
[![License](https://img.shields.io/packagist/l/hernol/uploadthing-php)](https://packagist.org/packages/hernol/uploadthing-php)

## Features

- ✅ **V6 API Compatible**: Uses UploadThing v6 `/uploadFiles` endpoint
- ✅ **Type-safe**: Full PHP 8.1+ type declarations and strict typing
- ✅ **Laravel-focused**: Designed specifically for Laravel applications
- ✅ **Environment-based configuration**: Simple configuration via environment variables
- ✅ **File uploads**: Upload files using presigned S3 URLs
- ✅ **Webhook verification**: HMAC-SHA256 signature validation with timestamp tolerance
- ✅ **Simple API**: Clean, straightforward interface

## Quick Start

### Installation

```bash
composer require hernol/uploadthing-php
```

### Configuration

Set your environment variables in your `.env` file:

```env
UPLOADTHING_API_KEY=ut_sk_your_api_key_here
UPLOADTHING_BASE_URL=https://api.uploadthing.com
UPLOADTHING_API_VERSION=v6
UPLOADTHING_TIMEOUT=30
UPLOADTHING_CALLBACK_URL=https://your-app.com/webhook
UPLOADTHING_CALLBACK_SLUG=your-slug
```

### Basic Usage

#### Upload a File

```php
<?php

use UploadThing\Resources\Uploads;

$uploads = new Uploads();
$file = $uploads->uploadFile('/path/to/file.jpg');

if ($file) {
    echo "File uploaded: {$file->name}\n";
    echo "File URL: {$file->url}\n";
    echo "File ID: {$file->id}\n";
}
```

#### Upload with Custom Name and MIME Type

```php
<?php

use UploadThing\Resources\Uploads;

$uploads = new Uploads();
$file = $uploads->uploadFile(
    '/path/to/image.jpg',
    'my-custom-name.jpg',
    'image/jpeg'
);
```

#### Handle Webhooks

```php
<?php

use UploadThing\Resources\Webhooks;

$webhooks = new Webhooks();

// Handle webhook from Laravel request
$event = $webhooks->handleWebhook(
    $request->getContent(),
    $request->headers->all(),
    env('UPLOADTHING_WEBHOOK_SECRET')
);

echo "Event type: {$event->type}\n";
echo "Event data: " . json_encode($event->data) . "\n";
```

#### Handle Webhook from PHP Globals

```php
<?php

use UploadThing\Resources\Webhooks;

$webhooks = new Webhooks();
$event = $webhooks->handleWebhookFromGlobals(
    env('UPLOADTHING_WEBHOOK_SECRET')
);
```

### V6 API Endpoint

The client uses the UploadThing v6 `/uploadFiles` endpoint which:
1. Prepares the upload and returns S3 presigned URL data
2. Uploads the file to S3 using multipart form data
3. Finalizes the upload via polling

### Error Handling

```php
<?php

use UploadThing\Exceptions\ApiException;
use UploadThing\Exceptions\AuthenticationException;
use UploadThing\Exceptions\RateLimitException;
use UploadThing\Exceptions\ValidationException;

try {
    $file = $uploads->uploadFile('/path/to/file.jpg');
} catch (AuthenticationException $e) {
    echo "Invalid API key: " . $e->getMessage();
} catch (RateLimitException $e) {
    echo "Rate limited, retry after: " . $e->getRetryAfter() . "s";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage();
    echo "Error Code: " . $e->getErrorCode();
}
```

## Examples

See the [examples](examples/) folder for complete usage examples:

- [Basic File Upload](examples/01-basic-upload.php)
- [Upload with Custom Options](examples/02-upload-custom.php)
- [Webhook Handling](examples/03-webhook-handling.php)
- [Webhook Handler Utility](examples/04-webhook-handler.php)
- [Webhook Verifier](examples/05-webhook-verifier.php)
- [Laravel Controller Example](examples/06-laravel-controller.php)
- [Error Handling](examples/07-error-handling.php)

## Documentation

- [📖 Usage Guide](docs/USAGE.md)
- [⚡ Laravel Integration](docs/LARAVEL.md)

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

## Laravel Integration

### Service Provider (Optional)

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

### Usage in Controllers

```php
<?php

namespace App\Http\Controllers;

use UploadThing\Resources\Uploads;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function upload(Request $request, Uploads $uploads)
    {
        $file = $request->file('file');
        $uploaded = $uploads->uploadFile(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $file->getMimeType()
        );

        return response()->json(['file' => $uploaded]);
    }
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

