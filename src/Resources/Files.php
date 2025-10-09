<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Exceptions\ApiException;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Models\File;
use UploadThing\Models\FileListResponse;
use UploadThing\Models\RenameFileRequest;
use UploadThing\Utils\Serializer;

/**
 * Files resource for managing uploaded files.
 */
final class Files
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ApiKeyAuthenticator $authenticator,
        private string $baseUrl,
        private Serializer $serializer = new Serializer(),
    ) {
    }

    /**
     * List all files.
     */
    public function listFiles(int $limit = 50, ?string $cursor = null): FileListResponse
    {
        $queryParams = ['limit' => $limit];
        if ($cursor !== null) {
            $queryParams['cursor'] = $cursor;
        }

        $request = $this->createRequest('GET', '/files', queryParams: $queryParams);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), FileListResponse::class);
    }

    /**
     * Get a specific file by ID.
     */
    public function getFile(string $fileId): File
    {
        $request = $this->createRequest('GET', "/files/{$fileId}");
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), File::class);
    }

    /**
     * Delete a file by ID.
     */
    public function deleteFile(string $fileId): void
    {
        $request = $this->createRequest('DELETE', "/files/{$fileId}");
        $this->sendRequest($request);
    }

    /**
     * Rename a file.
     */
    public function renameFile(string $fileId, string $newName): File
    {
        $requestBody = new RenameFileRequest($newName);

        $request = $this->createRequest('POST', "/files/{$fileId}/rename", body: $requestBody);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), File::class);
    }

    /**
     * Upload a file from a local path.
     */
    public function uploadFile(string $filePath, ?string $name = null): File
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        return $this->uploadContent($content, $name);
    }

    /**
     * Upload file content from a string.
     */
    public function uploadContent(string $content, string $name, ?string $mimeType = null): File
    {
        $mimeType = $mimeType ?? $this->detectMimeType($name, $content);
        
        $multipartBuilder = new \UploadThing\Utils\MultipartBuilder();
        $multipartBuilder
            ->addField('name', $name)
            ->addField('size', (string) strlen($content))
            ->addField('mimeType', $mimeType)
            ->addFile('file', $name, $content, $mimeType);

        $request = $this->createMultipartRequest('POST', '/files/upload', $multipartBuilder);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), File::class);
    }

    /**
     * Upload a file from a stream resource.
     */
    public function uploadStream(mixed $stream, string $name, ?string $mimeType = null): File
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a valid resource');
        }

        $content = stream_get_contents($stream);
        if ($content === false) {
            throw new \RuntimeException('Failed to read from stream');
        }

        return $this->uploadContent($content, $name, $mimeType);
    }

    /**
     * Upload a file with progress tracking.
     */
    public function uploadFileWithProgress(
        string $filePath, 
        ?string $name = null, 
        ?callable $progressCallback = null
    ): File {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $fileSize = filesize($filePath);
        
        if ($fileSize === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }

        // For large files, use chunked upload
        if ($fileSize > 10 * 1024 * 1024) { // 10MB threshold
            return $this->uploadFileChunked($filePath, $name, $progressCallback);
        }

        // For smaller files, use direct upload
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        if ($progressCallback !== null) {
            $progressCallback(0, $fileSize);
        }

        $file = $this->uploadContent($content, $name);
        
        if ($progressCallback !== null) {
            $progressCallback($fileSize, $fileSize);
        }

        return $file;
    }

    /**
     * Upload a file using chunked upload for large files.
     */
    public function uploadFileChunked(
        string $filePath, 
        string $name, 
        ?callable $progressCallback = null
    ): File {
        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }

        $mimeType = $this->detectMimeType($name, '');
        $chunkSize = 5 * 1024 * 1024; // 5MB chunks
        
        // Create upload session
        $session = $this->createUploadSession($name, $fileSize, $mimeType);
        
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open file: {$filePath}");
        }

        try {
            $uploadedBytes = 0;
            
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false) {
                    throw new \RuntimeException("Failed to read chunk from file");
                }

                // Upload chunk
                $this->uploadChunk($session->id, $chunk, $uploadedBytes);
                
                $uploadedBytes += strlen($chunk);
                
                if ($progressCallback !== null) {
                    $progressCallback($uploadedBytes, $fileSize);
                }
            }

            // Complete upload
            $completedSession = $this->completeUpload($session->id);
            
            if ($completedSession->fileId === null) {
                throw new \RuntimeException('Upload completed but no file ID returned');
            }

            return $this->getFile($completedSession->fileId);
            
        } finally {
            fclose($handle);
        }
    }

    /**
     * Upload a chunk of data to an upload session.
     */
    private function uploadChunk(string $uploadId, string $chunk, int $offset): void
    {
        $multipartBuilder = new \UploadThing\Utils\MultipartBuilder();
        $multipartBuilder
            ->addField('uploadId', $uploadId)
            ->addField('offset', (string) $offset)
            ->addFile('chunk', 'chunk.bin', $chunk, 'application/octet-stream');

        $request = $this->createMultipartRequest('POST', '/uploads/chunk', $multipartBuilder);
        $this->sendRequest($request);
    }

    /**
     * Create an upload session for chunked uploads.
     */
    private function createUploadSession(string $fileName, int $fileSize, string $mimeType): \UploadThing\Models\UploadSession
    {
        $uploadsResource = new \UploadThing\Resources\Uploads(
            $this->httpClient,
            $this->authenticator,
            $this->baseUrl,
            $this->serializer
        );
        
        return $uploadsResource->createUploadSession($fileName, $fileSize, $mimeType);
    }

    /**
     * Complete an upload session.
     */
    private function completeUpload(string $uploadId): \UploadThing\Models\UploadSession
    {
        $uploadsResource = new \UploadThing\Resources\Uploads(
            $this->httpClient,
            $this->authenticator,
            $this->baseUrl,
            $this->serializer
        );
        
        return $uploadsResource->completeUpload($uploadId);
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
     * Create a multipart request.
     */
    private function createMultipartRequest(string $method, string $path, \UploadThing\Utils\MultipartBuilder $multipartBuilder): RequestInterface
    {
        $uri = $this->baseUrl . $path;
        $request = new \GuzzleHttp\Psr7\Request($method, $uri);
        
        $request = $request
            ->withHeader('Content-Type', $multipartBuilder->getContentType())
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($multipartBuilder->build()));

        return $this->authenticator->authenticate($request);
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
