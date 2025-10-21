# Authentication Guide - UploadThing v6 API

## API Key Authentication

The UploadThing PHP Client uses API key authentication with the v6 API. You need to obtain an API key from your UploadThing dashboard and configure it in your client.

## Getting Your API Key

1. Log in to your UploadThing dashboard
2. Navigate to the API Keys section
3. Create a new API key or copy an existing one
4. Keep your API key secure and never commit it to version control

## Configuring Authentication

### Method 1: Direct API Key

```php
use UploadThing\Config;

$config = Config::create()->withApiKey('ut_sk_...');
```

### Method 2: Environment Variable

```php
use UploadThing\Config;

// Uses UPLOADTHING_API_KEY by default
$config = Config::create()->withApiKeyFromEnv();

// Or specify a custom environment variable name
$config = Config::create()->withApiKeyFromEnv('MY_API_KEY');
```

### Method 3: Environment File (.env)

Create a `.env` file in your project root:

```env
UPLOADTHING_API_KEY=ut_sk_...
```

Then load it in your application:

```php
// Using vlucas/phpdotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = Config::create()->withApiKeyFromEnv();
```

## V6 API Authentication Details

The UploadThing v6 API uses Bearer token authentication:

- **Header Name**: `Authorization`
- **Format**: `Bearer <api_key>`
- **Alternative**: `X-API-Key: <api_key>` (if Bearer not supported)

The client automatically handles authentication headers for all v6 API requests.

## Security Best Practices

### 1. Never Commit API Keys

Add your `.env` file to `.gitignore`:

```gitignore
.env
.env.local
.env.*.local
```

### 2. Use Environment Variables in Production

Set environment variables in your production environment:

```bash
export UPLOADTHING_API_KEY=ut_sk_...
```

### 3. Rotate Keys Regularly

- Regularly rotate your API keys
- Revoke unused or compromised keys
- Use different keys for different environments (development, staging, production)

### 4. Monitor Usage

- Monitor your API usage in the UploadThing dashboard
- Set up alerts for unusual activity
- Review access logs regularly

## Error Handling

The client provides specific exceptions for authentication errors:

```php
use UploadThing\Exceptions\AuthenticationException;

try {
    $client = Client::create($config);
    $files = $client->files()->listFiles();
} catch (AuthenticationException $e) {
    // Handle authentication failure
    echo "Authentication failed: " . $e->getMessage();
    
    // Check if the API key is valid
    if (empty($config->apiKey)) {
        echo "API key is not configured";
    }
}
```

## Testing Authentication

You can test your authentication setup by making a simple API call:

```php
use UploadThing\Client;
use UploadThing\Config;

$config = Config::create()->withApiKeyFromEnv();
$client = Client::create($config);

try {
    $files = $client->files()->listFiles();
    echo "Authentication successful!";
} catch (AuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage();
}
```

## V6 API Specific Considerations

### Base URL Configuration

The v6 API uses the standard UploadThing base URL:

```php
$config = Config::create()
    ->withApiKeyFromEnv()
    ->withBaseUrl('https://api.uploadthing.com') // Default
    ->withApiVersion('v6'); // Default
```

### API Version

The client automatically uses the v6 API version for all requests:

```php
// All requests will use /v6/ endpoints
$file = $client->files()->uploadFile('/path/to/file.jpg'); // Uses POST /v6/uploadFiles
$files = $client->files()->listFiles(); // Uses GET /v6/listFiles
```

## Troubleshooting

### Common Issues

1. **"API key is not configured"**
   - Ensure you've set the API key using `withApiKey()` or `withApiKeyFromEnv()`
   - Check that the environment variable is set correctly

2. **"Authentication failed"**
   - Verify your API key is correct
   - Check that the API key is active in your UploadThing dashboard
   - Ensure you're using the correct base URL (`https://api.uploadthing.com`)

3. **"Environment variable not found"**
   - Check that the environment variable name is correct
   - Verify the variable is set in your environment
   - Make sure you're loading environment files if using them

4. **"Invalid API version"**
   - Ensure you're using the v6 API version
   - Check that your client is configured with `apiVersion: 'v6'`

### Debug Mode

Enable debug logging to troubleshoot authentication issues:

```php
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('uploadthing');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$config = Config::create()
    ->withApiKeyFromEnv()
    ->withLogger($logger);
```

This will log all HTTP requests and responses, including authentication headers (sanitized for security).

### V6 API Endpoint Verification

You can verify that you're using the correct v6 API endpoints by checking the request logs:

```php
// This should make requests to /v6/listFiles
$files = $client->files()->listFiles();

// This should make requests to /v6/prepareUpload
$prepareData = $client->uploads()->prepareUpload('file.jpg', 1024 * 1024, 'image/jpeg');

// This should make requests to /v6/serverCallback
$client->uploads()->serverCallback('file-id', 'completed');
```

## Webhook Authentication

For webhook verification, you'll need a webhook secret (separate from your API key):

```php
// Verify webhook signature
$isValid = $client->webhooks()->verifySignature($payload, $signature, $webhookSecret);

// Handle webhook from globals
$webhookEvent = $client->webhooks()->handleWebhookFromGlobals($webhookSecret);
```

The webhook secret is used for HMAC-SHA256 signature verification and is different from your API key.