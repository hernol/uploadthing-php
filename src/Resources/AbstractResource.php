<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Exceptions\ApiException;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Utils\Serializer;

/**
 * Abstract base class for UploadThing API resources.
 * Provides common functionality for HTTP requests, authentication, and MIME type detection.
 */
abstract class AbstractResource
{
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected ApiKeyAuthenticator $authenticator,
        protected string $baseUrl,
        protected string $apiVersion,
        protected Serializer $serializer = new Serializer(),
    ) {
    }

    /**
     * Create a PSR-7 request with authentication.
     */
    protected function createRequest(
        string $method,
        string $path,
        array $queryParams = [],
        ?array $body = null,
    ): RequestInterface {
        $uri = $this->baseUrl . $path;

        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        $request = new \GuzzleHttp\Psr7\Request($method, $uri);

        if ($body !== null) {
            $request = $request->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor(json_encode($body))
            )->withHeader('Content-Type', 'application/json');
        }

        return $this->authenticator->authenticate($request);
    }

    /**
     * Send a request and handle the response.
     */
    protected function sendRequest(RequestInterface $request): ResponseInterface
    {
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
