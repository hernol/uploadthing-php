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
 * Files resource for managing uploaded files using UploadThing v6 API.
 */
final class Files extends AbstractResource
{
    public function __construct(
        HttpClientInterface $httpClient,
        ApiKeyAuthenticator $authenticator,
        string $baseUrl,
        string $apiVersion,
        Serializer $serializer = new Serializer(),
    ) {
        parent::__construct($httpClient, $authenticator, $baseUrl, $apiVersion, $serializer);
    }

    /**
     * List all files using v6 API.
     */
    public function listFiles(int $limit = 50, ?string $cursor = null): FileListResponse
    {
        $queryParams = ['limit' => $limit];
        if ($cursor !== null) {
            $queryParams['cursor'] = $cursor;
        }

        $request = $this->createRequest('GET', "/{$this->apiVersion}/listFiles", queryParams: $queryParams);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), FileListResponse::class);
    }

    /**
     * Get a specific file by ID using v6 API.
     */
    public function getFile(string $fileId): File
    {
        $request = $this->createRequest('GET', "/{$this->apiVersion}/getFile", queryParams: ['fileId' => $fileId]);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), File::class);
    }

    /**
     * Delete a file by ID using v6 API.
     */
    public function deleteFile(string $fileId): void
    {
        $requestBody = ['fileId' => $fileId];
        $request = $this->createRequest('POST', "/{$this->apiVersion}/deleteFile", body: $requestBody);
        $this->sendRequest($request);
    }

    /**
     * Rename a file using v6 API.
     */
    public function renameFile(string $fileId, string $newName): File
    {
        $requestBody = [
            'fileId' => $fileId,
            'newName' => $newName
        ];

        $request = $this->createRequest('POST', "/{$this->apiVersion}/renameFile", body: $requestBody);
        $response = $this->sendRequest($request);

        return $this->serializer->deserialize($response->getBody()->getContents(), File::class);
    }

    /**
     * Upload a file from a local path using v6 API.
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
     * Upload file content from a string using v6 API.
     */
    public function uploadContent(string $content, string $name, ?string $mimeType = null): File
    {
        $mimeType = $mimeType ?? $this->detectMimeType($name, $content);
        
        // Use the uploadFiles endpoint for direct upload
        $requestBody = [
            'files' => [
                [
                    'name' => $name,
                    'size' => strlen($content),
                    'type' => $mimeType,
                    'content' => base64_encode($content)
                ]
            ]
        ];

        $request = $this->createRequest('POST', "/{$this->apiVersion}/uploadFiles", body: $requestBody);
        $response = $this->sendRequest($request);

        $data = json_decode($response->getBody()->getContents(), true);
        
        if (!isset($data['data'][0])) {
            throw new \RuntimeException('Invalid upload response');
        }

        return $this->serializer->deserialize(json_encode($data['data'][0]), File::class);
    }

    /**
     * Upload a file from a stream resource using v6 API.
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
     * Upload a file with progress tracking using v6 API.
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

        // For large files, use prepareUpload + uploadFiles flow
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
     * Upload a file using chunked upload for large files with v6 API.
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
        
        // Step 1: Prepare upload (S3 POST data)
        $prepareResponse = $this->prepareUpload($name, $fileSize, $mimeType);

        if (!isset($prepareResponse['data'][0])) {
            throw new \RuntimeException('Invalid prepareUpload response');
        }

        $item = $prepareResponse['data'][0];
        if (!isset($item['url'], $item['fields']) || !is_array($item['fields'])) {
            throw new \RuntimeException('Missing S3 POST data (url/fields) from prepareUpload');
        }

        // Step 2: Upload file to S3 using POST form fields
        $this->uploadToS3Post($item['url'], $item['fields'], $filePath, $mimeType, $progressCallback);

        // Step 3: Finalize via polling if provided, else fallback to serverCallback when fileId is present
        $fileId = $this->finalizePolling($item);
        if ($fileId !== null) {
            return $this->getFile($fileId);
        }

        if (isset($item['fileId'])) {
            $this->serverCallback($item['fileId']);
            return $this->getFile($item['fileId']);
        }

        throw new \RuntimeException('Upload completed but file could not be finalized');
    }

    /**
     * Prepare upload using v6 prepareUpload endpoint.
     */
    private function prepareUpload(string $fileName, int $fileSize, string $mimeType): array
    {
        $requestBody = [
            'files' => [
                [
                    'name' => $fileName,
                    'size' => $fileSize,
                    'type' => $mimeType
                ]
            ]
        ];

        $request = $this->createRequest('POST', "/{$this->apiVersion}/prepareUpload", body: $requestBody);
        $response = $this->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Upload a file to S3 using a POST with provided fields.
     */
    private function uploadToS3Post(string $s3Url, array $fields, string $filePath, ?string $contentType = null, ?callable $progressCallback = null): void
    {
        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open file: {$filePath}");
        }

        try {
            $content = stream_get_contents($handle);
            if ($content === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }

            $multipartBuilder = new \UploadThing\Utils\MultipartBuilder();
            foreach ($fields as $name => $value) {
                $multipartBuilder->addField((string) $name, (string) $value);
            }
            $multipartBuilder->addFile('file', basename($filePath), $content, $contentType);

            $request = new \GuzzleHttp\Psr7\Request('POST', $s3Url);
            $request = $request
                ->withHeader('Content-Type', $multipartBuilder->getContentType())
                ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($multipartBuilder->build()));

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() >= 400) {
                throw ApiException::fromResponse($response);
            }

            if ($progressCallback !== null) {
                $progressCallback($fileSize, $fileSize);
            }

        } finally {
            fclose($handle);
        }
    }

    /**
     * Complete upload using v6 serverCallback endpoint.
     */
    private function serverCallback(string $fileId): void
    {
        $requestBody = [
            'fileId' => $fileId,
            'status' => 'completed'
        ];

        $request = $this->createRequest('POST', "/{$this->apiVersion}/serverCallback", body: $requestBody);
        $this->sendRequest($request);
    }

    /**
     * Finalize upload using polling token if available. Returns fileId when known.
     */
    private function finalizePolling(array $prepareItem): ?string
    {
        $pollingUrl = $prepareItem['pollingUrl'] ?? null;
        $pollingJwt = $prepareItem['pollingJwt'] ?? null;

        if (!$pollingUrl || !$pollingJwt) {
            return null;
        }

        $request = new \GuzzleHttp\Psr7\Request('POST', $pollingUrl);
        $request = $request
            ->withHeader('Authorization', 'Bearer ' . $pollingJwt)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor(json_encode(new \stdClass())));

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        $data = json_decode($response->getBody()->getContents(), true);
        if (is_array($data) && isset($data['fileId']) && is_string($data['fileId'])) {
            return $data['fileId'];
        }

        return null;
    }

}