<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Exceptions\ApiException;

/**
 * Abstract base class for UploadThing API resources.
 * Provides common functionality for HTTP requests, authentication, and MIME type detection.
 */
abstract class AbstractResource
{
    protected Client $httpClient;
    protected ApiKeyAuthenticator $authenticator;
    protected string $baseUrl;
    protected string $apiVersion;

    public function __construct()
    {
        $apiKey = $this->getEnv('UPLOADTHING_API_KEY', '');
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('UPLOADTHING_API_KEY environment variable is not set');
        }

        $this->baseUrl = $this->getEnv('UPLOADTHING_BASE_URL', 'https://api.uploadthing.com');
        $this->apiVersion = $this->getEnv('UPLOADTHING_API_VERSION', 'v6');
        $this->authenticator = new ApiKeyAuthenticator($apiKey);
        $this->httpClient = new Client([
            'timeout' => (int) $this->getEnv('UPLOADTHING_TIMEOUT', 30),
        ]);
    }

    /**
     * Get environment variable value with fallback.
     */
    protected function getEnv(string $key, mixed $default = ''): mixed
    {
        if (function_exists('env')) {
            $value = env($key, $default);
            return $value !== null ? $value : $default;
        }

        // Fallback for non-Laravel environments
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Send a request and handle the response.
     */
    protected function sendRequest(string $method, string $path, array $queryParams = [], ?array $body = null): ResponseInterface
    {
        $uri = $this->baseUrl . $path;

        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        $request = new Request($method, $uri);

        $headers = [];
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        $request = $this->authenticator->authenticate($request);
        
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $request = $request->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor(json_encode($body))
            );
        }

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }

    /**
     * Detect MIME type from filename and content.
     */
    protected function detectMimeType(string $filename, string $content): string
    {
        // Try to detect from file extension first
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
        ];

        if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }

        // Fallback to content detection if available
        if (function_exists('finfo_buffer')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_buffer($finfo, $content);
                finfo_close($finfo);
                if ($detected !== false) {
                    return $detected;
                }
            }
        }

        return 'application/octet-stream';
    }
}
