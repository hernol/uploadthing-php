<?php

declare(strict_types=1);

namespace UploadThing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UploadThing\Utils\UploadHelper;
use UploadThing\Resources\Files;
use UploadThing\Resources\Uploads;
use UploadThing\Http\HttpClientInterface;
use UploadThing\Auth\ApiKeyAuthenticator;

class UploadHelperTest extends TestCase
{
    public function testValidateFile(): void
    {
        $filePath = '/tmp/test.txt';
        $options = [
            'maxSize' => 1024,
            'allowedTypes' => ['txt'],
            'allowedMimeTypes' => ['text/plain'],
        ];
        
        // Create a temporary file
        file_put_contents($filePath, 'test content');

        // Create UploadHelper with minimal dependencies
        $httpClient = $this->createMock(HttpClientInterface::class);
        $authenticator = new ApiKeyAuthenticator('test-key');
        
        $filesResource = new Files($httpClient, $authenticator, 'https://api.uploadthing.com');
        $uploadsResource = new Uploads($httpClient, $authenticator, 'https://api.uploadthing.com');
        
        $uploadHelper = new UploadHelper($filesResource, $uploadsResource);

        $this->expectNotToPerformAssertions();
        $uploadHelper->validateFile($filePath, $options);

        // Clean up
        unlink($filePath);
    }

    public function testValidateFileNotExists(): void
    {
        $filePath = '/tmp/nonexistent.txt';
        $options = [];

        $httpClient = $this->createMock(HttpClientInterface::class);
        $authenticator = new ApiKeyAuthenticator('test-key');
        
        $filesResource = new Files($httpClient, $authenticator, 'https://api.uploadthing.com');
        $uploadsResource = new Uploads($httpClient, $authenticator, 'https://api.uploadthing.com');
        
        $uploadHelper = new UploadHelper($filesResource, $uploadsResource);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist');
        
        $uploadHelper->validateFile($filePath, $options);
    }

    public function testValidateFileSizeExceeded(): void
    {
        $filePath = '/tmp/large-test.txt';
        $options = ['maxSize' => 10]; // Very small limit
        
        // Create a temporary file larger than the limit
        file_put_contents($filePath, 'this is larger than 10 bytes');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $authenticator = new ApiKeyAuthenticator('test-key');
        
        $filesResource = new Files($httpClient, $authenticator, 'https://api.uploadthing.com');
        $uploadsResource = new Uploads($httpClient, $authenticator, 'https://api.uploadthing.com');
        
        $uploadHelper = new UploadHelper($filesResource, $uploadsResource);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size (28 bytes) exceeds maximum allowed size (10 bytes)');
        
        $uploadHelper->validateFile($filePath, $options);

        // Clean up
        unlink($filePath);
    }

    public function testValidateFileTypeNotAllowed(): void
    {
        $filePath = '/tmp/test.jpg';
        $options = ['allowedTypes' => ['txt']]; // Only allow txt files
        
        // Create a temporary file with jpg extension
        file_put_contents($filePath, 'test content');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $authenticator = new ApiKeyAuthenticator('test-key');
        
        $filesResource = new Files($httpClient, $authenticator, 'https://api.uploadthing.com');
        $uploadsResource = new Uploads($httpClient, $authenticator, 'https://api.uploadthing.com');
        
        $uploadHelper = new UploadHelper($filesResource, $uploadsResource);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File type \'jpg\' is not allowed');
        
        $uploadHelper->validateFile($filePath, $options);

        // Clean up
        unlink($filePath);
    }

    public function testValidateFileMimeTypeNotAllowed(): void
    {
        $filePath = '/tmp/test.txt';
        $options = ['allowedMimeTypes' => ['image/jpeg']]; // Only allow JPEG images
        
        // Create a temporary file with txt extension
        file_put_contents($filePath, 'test content');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $authenticator = new ApiKeyAuthenticator('test-key');
        
        $filesResource = new Files($httpClient, $authenticator, 'https://api.uploadthing.com');
        $uploadsResource = new Uploads($httpClient, $authenticator, 'https://api.uploadthing.com');
        
        $uploadHelper = new UploadHelper($filesResource, $uploadsResource);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MIME type \'text/plain\' is not allowed. Allowed types: image/jpeg');
        
        $uploadHelper->validateFile($filePath, $options);

        // Clean up
        unlink($filePath);
    }

    public function testDetectMimeType(): void
    {
        $filePath = '/tmp/test.jpg';
        
        // Create a temporary file
        file_put_contents($filePath, 'fake image content');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $authenticator = new ApiKeyAuthenticator('test-key');
        
        $filesResource = new Files($httpClient, $authenticator, 'https://api.uploadthing.com');
        $uploadsResource = new Uploads($httpClient, $authenticator, 'https://api.uploadthing.com');
        
        $uploadHelper = new UploadHelper($filesResource, $uploadsResource);

        // Test that validation passes for allowed MIME type
        $options = ['allowedMimeTypes' => ['image/jpeg']];
        
        $this->expectNotToPerformAssertions();
        $uploadHelper->validateFile($filePath, $options);

        // Clean up
        unlink($filePath);
    }
}