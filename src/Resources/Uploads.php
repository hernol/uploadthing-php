<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Exceptions\ApiException;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Models\CreateUploadRequest;
use UploadThing\Models\File;
use UploadThing\Models\UploadSession;
use UploadThing\Utils\Serializer;

/**
 * Uploads resource for managing file uploads.
 */
final class Uploads
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ApiKeyAuthenticator $authenticator,
        private string $baseUrl,
        private Serializer $serializer = new Serializer(),
    ) {
    }

    /**
     * Create a new upload session.
     */
    public function createUploadSession(string $fileName, int $fileSize, ?string $mimeType = null): UploadSession
    {
        $requestBody = new CreateUploadRequest($fileName, $fileSize, $mimeType);

        $request = $this->createRequest('POST', '/uploads', body: $requestBody);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), UploadSession::class);
    }

    /**
     * Get upload session status.
     */
    public function getUploadStatus(string $uploadId): UploadSession
    {
        $request = $this->createRequest('GET', "/uploads/{$uploadId}");
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), UploadSession::class);
    }

    /**
     * Complete an upload session.
     */
    public function completeUpload(string $uploadId): UploadSession
    {
        $request = $this->createRequest('POST', "/uploads/{$uploadId}/complete");
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), UploadSession::class);
    }

    /**
     * Cancel an upload session.
     */
    public function cancelUpload(string $uploadId): void
    {
        $request = $this->createRequest('DELETE', "/uploads/{$uploadId}");
        $this->sendRequest($request);
    }

    /**
     * Get a presigned URL for direct file upload.
     */
    public function getPresignedUrl(string $fileName, int $fileSize, ?string $mimeType = null): array
    {
        $requestBody = new CreateUploadRequest($fileName, $fileSize, $mimeType);

        $request = $this->createRequest('POST', '/uploads/presigned', body: $requestBody);
        $response = $this->sendRequest($request);

        $data = json_decode($response->getBody()->getContents(), true);
        
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid presigned URL response');
        }

        return $data;
    }

    /**
     * Upload a file using a presigned URL.
     */
    public function uploadWithPresignedUrl(
        string $filePath, 
        ?string $name = null, 
        ?string $mimeType = null
    ): File {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $fileSize = filesize($filePath);
        
        if ($fileSize === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }

        $mimeType = $mimeType ?? $this->detectMimeType($name, '');
        
        // Get presigned URL
        $presignedData = $this->getPresignedUrl($name, $fileSize, $mimeType);
        
        if (!isset($presignedData['uploadUrl']) || !isset($presignedData['fileId'])) {
            throw new \RuntimeException('Invalid presigned URL response');
        }

        // Upload file to presigned URL
        $this->uploadToPresignedUrl($presignedData['uploadUrl'], $filePath, $presignedData['fields'] ?? []);

        // Return file details
        return $this->getFile($presignedData['fileId']);
    }

    /**
     * Upload a file to a presigned URL.
     */
    private function uploadToPresignedUrl(string $uploadUrl, string $filePath, array $fields = []): void
    {
        $multipartBuilder = new \UploadThing\Utils\MultipartBuilder();
        
        // Add any required fields
        foreach ($fields as $name => $value) {
            $multipartBuilder->addField($name, $value);
        }
        
        // Add the file
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }
        
        $multipartBuilder->addFile('file', basename($filePath), $content);

        // Create request to presigned URL
        $request = new \GuzzleHttp\Psr7\Request('POST', $uploadUrl);
        $request = $request
            ->withHeader('Content-Type', $multipartBuilder->getContentType())
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($multipartBuilder->build()));

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }
    }

    /**
     * Detect MIME type from filename and content.
     */
    private function detectMimeType(string $filename, string $content): string
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

    /**
     * Get a file by ID.
     */
    private function getFile(string $fileId): File
    {
        $filesResource = new \UploadThing\Resources\Files(
            $this->httpClient,
            $this->authenticator,
            $this->baseUrl,
            $this->serializer
        );
        
        return $filesResource->getFile($fileId);
    }

    /**
     * Create a PSR-7 request.
     */
    private function createRequest(
        string $method,
        string $path,
        array $queryParams = [],
        ?object $body = null,
    ): RequestInterface {
        $uri = $this->baseUrl . $path;

        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        $request = new \GuzzleHttp\Psr7\Request($method, $uri);

        if ($body !== null) {
            $request = $request->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor($this->serializer->serialize($body))
            );
        }

        return $this->authenticator->authenticate($request);
    }

    /**
     * Send a request and handle the response.
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }
}
