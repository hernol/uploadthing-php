# Authentication Guide

## API Key Authentication

The UploadThing PHP Client uses API key authentication. You need to obtain an API key from your UploadThing dashboard and configure it in your client.

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

## Troubleshooting

### Common Issues

1. **"API key is not configured"**
   - Ensure you've set the API key using `withApiKey()` or `withApiKeyFromEnv()`
   - Check that the environment variable is set correctly

2. **"Authentication failed"**
   - Verify your API key is correct
   - Check that the API key is active in your UploadThing dashboard
   - Ensure you're using the correct base URL

3. **"Environment variable not found"**
   - Check that the environment variable name is correct
   - Verify the variable is set in your environment
   - Make sure you're loading environment files if using them

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
