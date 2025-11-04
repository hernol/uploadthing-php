<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Auth\ApiKeyAuthenticator;
use UploadThing\Exceptions\ApiException;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Models\File;
use UploadThing\Models\UploadSession;
use UploadThing\Utils\Serializer;

/**
 * Uploads resource for managing file uploads using UploadThing v6 API.
 */
final class Uploads extends AbstractResource
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
     * Prepare upload using v6 prepareUpload endpoint.
     */
    public function prepareUpload(string $fileName, int $fileSize, ?string $mimeType = null): array
    {
        $mimeType = $mimeType ?? $this->detectMimeType($fileName, '');
        
        $requestBody = [
            'callbackUrl' => 'https://2f8ea68162e0.ngrok-free.app',
            'callbackSlug' => '',
            'files' => [
                [
                    'name' => $fileName,
                    'size' => $fileSize,
                    'type' => $mimeType
                ]
            ],
            'routeConfig' => [
                'image'
            ]
        ];

        $request = $this->createRequest('POST', "/{$this->apiVersion}/prepareUpload", body: $requestBody);
        $response = $this->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Upload files using v6 uploadFiles endpoint.
     */
    public function uploadFiles(array $files): array
    {
        $requestBody = ['files' => $files];
        
        $request = $this->createRequest('POST', "/{$this->apiVersion}/uploadFiles", body: $requestBody);
        $response = $this->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Server callback using v6 serverCallback endpoint.
     */
    public function serverCallback(string $fileId, string $status = 'completed'): void
    {
        $requestBody = [
            'fileId' => $fileId,
            'status' => $status
        ];

        $request = $this->createRequest('POST', "/{$this->apiVersion}/serverCallback", body: $requestBody);
        $this->sendRequest($request);
    }

    /**
     * Upload a file using presigned URL flow.
     */
    public function uploadWithPresignedUrl(
        string $filePath, 
        ?string $name = null, 
        ?string $mimeType = null
    ): ?File {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $fileSize = filesize($filePath);
        
        if ($fileSize === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }

        $mimeType = $mimeType ?? $this->detectMimeType($name, '');
        
        // Step 1: Prepare upload
        $prepareResponse = $this->prepareUpload($name, $fileSize, $mimeType);

        if (!isset($prepareResponse[0])) {
            throw new \RuntimeException('Invalid prepareUpload response');
        }

        $item = $prepareResponse[0];

        if (!isset($item['url'], $item['fields']) || !is_array($item['fields'])) {
            throw new \RuntimeException('Missing S3 POST data (url/fields) from prepareUpload');
        }

        // Step 2: Upload file to S3 using POST form fields
        $this->uploadToS3Post($item['url'], $item['fields'], $filePath, $mimeType);

        // Step 3: Finalize via polling if provided, else fallback to serverCallback when fileId is present
        $status = $this->finalizePolling($item);
        return $status === 'ok' ? new File(
            id: $item['key'],
            name: $item['fileName'],
            size: $item['size'] ?? 0,
            mimeType: $item['fields']['Content-Type'] ?? '',
            url: $item['ufsUrl'],
            createdAt: new \DateTimeImmutable('now'),
        ) : null;
    }

    /**
     * Upload a file to S3 using a POST with provided fields.
     */
    private function uploadToS3Post(string $s3Url, array $fields, string $filePath, ?string $contentType = null): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        $multipartBuilder = new \UploadThing\Utils\MultipartBuilder();

        // Add all S3 POST fields
        foreach ($fields as $fieldName => $fieldValue) {
            $multipartBuilder->addField((string) $fieldName, (string) $fieldValue);
        }

        // Add the file payload as the final part
        $multipartBuilder->addFile('file', basename($filePath), $content, $contentType);

        $request = new \GuzzleHttp\Psr7\Request('POST', $s3Url);
        $request = $request
            ->withHeader('Content-Type', $multipartBuilder->getContentType())
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($multipartBuilder->build()));

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            var_export('could not upload to S3');
            exit(1);
            throw ApiException::fromResponse($response);
        }
    }

    /**
     * Finalize upload using polling token if available. Returns fileId when known.
     */
    private function finalizePolling(array $prepareItem): ?string
    {
        $pollingUrl = $prepareItem['pollingUrl'] ?? null;
        $pollingJwt = $prepareItem['pollingJwt'] ?? null;
        $fileKey = $prepareItem['key'] ?? null;

        if (!$pollingUrl || !$pollingJwt || !$fileKey) {
            return null;
        }

        $pollingUrlPath = parse_url($pollingUrl, PHP_URL_PATH);
        $request = $this->createRequest('POST', $pollingUrlPath, body: [
            'fileKey' => $fileKey,
            'callbackData' => ''
        ]);
        $response = $this->sendRequest($request);

        $data = json_decode($response->getBody()->getContents(), true);
        if (is_array($data) && isset($data['status']) && is_string($data['status'])) {
            return $data['status'];
        }

        return null;
    }

    /**
     * Upload multiple files at once.
     */
    public function uploadMultipleFiles(array $filePaths, ?callable $progressCallback = null): array
    {
        $files = [];
        $totalSize = 0;

        // Prepare file data
        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) {
                throw new \InvalidArgumentException("File does not exist: {$filePath}");
            }

            $name = basename($filePath);
            $fileSize = filesize($filePath);
            
            if ($fileSize === false) {
                throw new \RuntimeException("Failed to get file size: {$filePath}");
            }

            $totalSize += $fileSize;
            $files[] = [
                'name' => $name,
                'size' => $fileSize,
                'type' => $this->detectMimeType($name, ''),
                'path' => $filePath
            ];
        }

        // Prepare upload for all files
        $prepareData = [];
        foreach ($files as $file) {
            $prepareData[] = [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type']
            ];
        }

        $prepareResponse = $this->prepareUploadMultiple($prepareData);
        
        if (!isset($prepareResponse['data'])) {
            throw new \RuntimeException('Failed to prepare upload');
        }

        $uploadedFiles = [];
        $uploadedSize = 0;

        // Upload each file
        foreach ($prepareResponse as $index => $uploadData) {
            if (!isset($uploadData['url'], $uploadData['fields']) || !is_array($uploadData['fields'])) {
                continue;
            }

            $filePath = $files[$index]['path'];
            $this->uploadToS3Post($uploadData['url'], $uploadData['fields'], $filePath, $files[$index]['type']);

            $uploadedSize += $files[$index]['size'];
            
            if ($progressCallback !== null) {
                $progressCallback($uploadedSize, $totalSize);
            }

            // Complete upload
            $status = $this->finalizePolling($uploadData);
            if ($status === 'ok') {
                $uploadedFiles[] = $uploadData['key'];
            } else {
                throw new \RuntimeException('Upload not completed: ' . $status);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Prepare upload for multiple files.
     */
    private function prepareUploadMultiple(array $files): array
    {
        $requestBody = ['files' => $files];

        $request = $this->createRequest('POST', "/{$this->apiVersion}/prepareUpload", body: $requestBody);
        $response = $this->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

}