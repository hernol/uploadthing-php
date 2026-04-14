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
     * Upload a file using the v7 server-side upload flow.
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
        
        $preparedUpload = $this->prepareUpload($name, $fileSize, $mimeType);
        $uploadResult = $this->uploadToSignedUrl($preparedUpload['url'], $filePath, $mimeType);

        return new File(
            id: $preparedUpload['key'],
            name: $name,
            size: $fileSize,
            mimeType: $mimeType,
            url: $uploadResult['ufsUrl'] ?? $uploadResult['url'] ?? $uploadResult['appUrl'] ?? '',
            createdAt: new \DateTimeImmutable('now'),
            metadata: $uploadResult,
        );
    }

    /**
     * Request a signed UploadThing ingest URL for a server-side upload.
     *
     * @return array{url:string,key:string}
     */
    private function prepareUpload(string $name, int $fileSize, string $mimeType): array
    {
        $response = $this->sendRequest('POST', '/v7/prepareUpload', [], [
            'fileName' => $name,
            'fileSize' => $fileSize,
            'fileType' => $mimeType,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (!is_array($data) || !isset($data['url'], $data['key'])) {
            throw new \RuntimeException('Invalid prepareUpload response');
        }

        return [
            'url' => (string) $data['url'],
            'key' => (string) $data['key'],
        ];
    }

    /**
     * Upload a file to a signed UploadThing ingest URL.
     *
     * @return array<string,mixed>
     */
    private function uploadToSignedUrl(string $signedUrl, string $filePath, string $mimeType): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        $multipartBuilder = new MultipartBuilder();
        $multipartBuilder->addFile('file', basename($filePath), $content, $mimeType);

        $request = new Request('PUT', $signedUrl);
        $request = $request
            ->withHeader('Content-Type', $multipartBuilder->getContentType())
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($multipartBuilder->build()));

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($response);
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid upload response');
        }

        return $data;
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
     * Retries with 1-second sleep when status is "still working".
     */
    private function finalizePolling(array $item, int $maxRetries = 5): ?string
    {
        $fileKey = $item['key'] ?? null;

        if (!$fileKey) {
            return null;
        }

        $attempts = 0;

        while ($attempts < $maxRetries) {
            $response = $this->sendRequest('GET', "/{$this->apiVersion}/pollUpload/{$fileKey}");

            if ($response->getStatusCode() >= 400) {
                throw ApiException::fromResponse($response);
            }

            $data = json_decode($response->getBody()->getContents(), true);
            if (is_array($data) && isset($data['status']) && is_string($data['status'])) {
                $status = $data['status'];

                if ($status !== 'still working') {
                    return $status;
                }
            }

            $attempts++;
            if ($attempts < $maxRetries) {
                sleep(1);
            }
        }

        return null;
    }
}
