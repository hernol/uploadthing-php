<?php

declare(strict_types=1);

namespace UploadThing\Tests\Integration;

use PHPUnit\Framework\TestCase;
use UploadThing\Client;
use UploadThing\Config;
use UploadThing\Models\File as UploadedFile;

class IntegrationTest extends TestCase
{
    /**
     * @group integration
     */
    public function testUploadSingleFile(): void
    {
        $apiKey = getenv('UPLOADTHING_API_KEY') ?: '';
        if ($apiKey === '') {
            $this->markTestSkipped('UPLOADTHING_API_KEY is not set; skipping integration test.');
        }

        $config = Config::create()->withApiKey($apiKey);

        $baseUrl = getenv('UPLOADTHING_BASE_URL') ?: '';
        if ($baseUrl !== '') {
            $config = $config->withBaseUrl($baseUrl);
        }

        $client = Client::create($config);

        // Create a small temporary file to upload
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'ut-php-');
        $this->assertNotFalse($tmpFilePath, 'Failed to create temporary file');
        file_put_contents($tmpFilePath, 'hello from uploadthing-php integration test');

        try {
            // Use presigned URL flow which completes and then fetches the file by ID
            $uploaded = $client->uploads()->uploadWithPresignedUrl(
                $tmpFilePath,
                basename($tmpFilePath) . '.txt',
                'text/plain'
            );

            $this->assertInstanceOf(UploadedFile::class, $uploaded);
            $this->assertNotEmpty($uploaded->id);
            $this->assertSame('text/plain', $uploaded->mimeType);
            $this->assertGreaterThan(0, $uploaded->size);

            // Best-effort cleanup to avoid polluting the account
            $client->files()->deleteFile($uploaded->id);
        } catch (\Exception $e) {
            $this->fail('Upload failed: ' . $e->getMessage());
        } finally {
            @unlink($tmpFilePath);
        }
    }
}
