<?php

declare(strict_types=1);

namespace UploadThing\Resources;

use GuzzleHttp\Psr7\Request;
use UploadThing\Exceptions\ApiException;
use UploadThing\Models\File;
use UploadThing\Utils\MultipartBuilder;

/**
 * Uploads resource for managing file uploads using UploadThing v6 API.
 */
final class Uploads extends AbstractResource
{
    private ?string $callbackUrl;
    private ?string $callbackSlug;

    public function __construct()
    {
        parent::__construct();
        $this->callbackUrl = $this->getEnv('UPLOADTHING_CALLBACK_URL') ?: null;
        $this->callbackSlug = $this->getEnv('UPLOADTHING_CALLBACK_SLUG') ?: null;
    }


    /**
     * Upload a file using the v6 uploadFiles endpoint.
     */
    public function uploadFile(
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
        
        // Step 1: Call uploadFiles endpoint
        $requestBody = [
            'files' => [
                [
                    'name' => $name,
                    'size' => $fileSize,
                    'type' => $mimeType
                ]
            ]
        ];

        if ($this->callbackUrl !== null) {
            $requestBody['callbackUrl'] = $this->callbackUrl;
        }

        if ($this->callbackSlug !== null) {
            $requestBody['callbackSlug'] = $this->callbackSlug;
        }

        $response = $this->sendRequest('POST', "/{$this->apiVersion}/uploadFiles", [], $requestBody);
        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['data'][0])) {
            throw new \RuntimeException('Invalid uploadFiles response');
        }

        $item = $data['data'][0];

        if (!isset($item['url'], $item['fields']) || !is_array($item['fields'])) {
            throw new \RuntimeException('Missing S3 POST data (url/fields) from uploadFiles');
        }

        // Step 2: Upload file to S3 using POST form fields
        $this->uploadToS3Post($item['url'], $item['fields'], $filePath, $mimeType);

        // Step 3: Finalize via polling
        $status = $this->finalizePolling($item);
        return $status === 'ok' || $status === 'completed' || $status === 'done' ? new File(
            id: $item['key'],
            name: $item['fileName'],
            size: $item['size'] ?? 0,
            mimeType: $item['fields']['Content-Type'] ?? '',
            url: $item['ufsUrl'] ?? '',
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

        $multipartBuilder = new MultipartBuilder();

        // Add all S3 POST fields
        foreach ($fields as $fieldName => $fieldValue) {
            $multipartBuilder->addField((string) $fieldName, (string) $fieldValue);
        }

        // Add the file payload as the final part
        $multipartBuilder->addFile('file', basename($filePath), $content, $contentType);

        $request = new Request('POST', $s3Url);
        $request = $request
            ->withHeader('Content-Type', $multipartBuilder->getContentType())
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($multipartBuilder->build()));

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }
    }

    /**
     * Finalize upload using polling token if available. Returns status when known.
     */
    private function finalizePolling(array $item): ?string
    {
        $fileKey = $item['key'] ?? null;

        if (!$fileKey) {
            return null;
        }

        $response = $this->sendRequest('GET', "/{$this->apiVersion}/pollUpload/{$fileKey}");

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        $data = json_decode($response->getBody()->getContents(), true);
        if (is_array($data) && isset($data['status']) && is_string($data['status'])) {
            return $data['status'];
        }

        return null;
    }

}