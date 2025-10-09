<?php

declare(strict_types=1);

namespace UploadThing\Utils;

use UploadThing\Models\File;
use UploadThing\Resources\Files;
use UploadThing\Resources\Uploads;

/**
 * Upload helper utility providing unified upload methods.
 */
final class UploadHelper
{
    public function __construct(
        private Files $filesResource,
        private Uploads $uploadsResource,
    ) {
    }

    /**
     * Upload a file with automatic method selection based on size and requirements.
     */
    public function uploadFile(
        string $filePath,
        ?string $name = null,
        ?string $mimeType = null,
        ?callable $progressCallback = null,
        bool $usePresignedUrl = false
    ): File {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $fileSize = filesize($filePath);
        
        if ($fileSize === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }

        // For very large files or when presigned URL is requested, use presigned URL
        if ($usePresignedUrl || $fileSize > 50 * 1024 * 1024) { // 50MB threshold
            return $this->uploadsResource->uploadWithPresignedUrl($filePath, $name, $mimeType);
        }

        // For large files, use chunked upload
        if ($fileSize > 10 * 1024 * 1024) { // 10MB threshold
            return $this->filesResource->uploadFileChunked($filePath, $name, $progressCallback);
        }

        // For smaller files, use direct upload
        return $this->filesResource->uploadFileWithProgress($filePath, $name, $progressCallback);
    }

    /**
     * Upload content from a string.
     */
    public function uploadContent(
        string $content,
        string $name,
        ?string $mimeType = null
    ): File {
        return $this->filesResource->uploadContent($content, $name, $mimeType);
    }

    /**
     * Upload content from a stream resource.
     */
    public function uploadStream(
        mixed $stream,
        string $name,
        ?string $mimeType = null
    ): File {
        return $this->filesResource->uploadStream($stream, $name, $mimeType);
    }

    /**
     * Upload a file using chunked upload with custom chunk size.
     */
    public function uploadFileChunked(
        string $filePath,
        string $name,
        int $chunkSize = 5 * 1024 * 1024, // 5MB default
        ?callable $progressCallback = null
    ): File {
        return $this->filesResource->uploadFileChunked($filePath, $name, $progressCallback);
    }

    /**
     * Upload a file using presigned URL.
     */
    public function uploadWithPresignedUrl(
        string $filePath,
        ?string $name = null,
        ?string $mimeType = null
    ): File {
        return $this->uploadsResource->uploadWithPresignedUrl($filePath, $name, $mimeType);
    }

    /**
     * Get a presigned URL for client-side uploads.
     */
    public function getPresignedUrl(
        string $fileName,
        int $fileSize,
        ?string $mimeType = null
    ): array {
        return $this->uploadsResource->getPresignedUrl($fileName, $fileSize, $mimeType);
    }

    /**
     * Upload multiple files in parallel.
     */
    public function uploadMultipleFiles(
        array $filePaths,
        ?callable $progressCallback = null,
        bool $usePresignedUrl = false
    ): array {
        $results = [];
        $totalFiles = count($filePaths);
        $completedFiles = 0;

        foreach ($filePaths as $index => $filePath) {
            try {
                $file = $this->uploadFile(
                    $filePath,
                    null,
                    null,
                    function ($uploaded, $total) use ($progressCallback, $totalFiles, $completedFiles) {
                        if ($progressCallback !== null) {
                            // Calculate overall progress
                            $fileProgress = $total > 0 ? ($uploaded / $total) : 1;
                            $overallProgress = ($completedFiles + $fileProgress) / $totalFiles;
                            $progressCallback($overallProgress, $totalFiles);
                        }
                    },
                    $usePresignedUrl
                );

                $results[] = [
                    'success' => true,
                    'file' => $file,
                    'path' => $filePath,
                ];

                $completedFiles++;
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'path' => $filePath,
                ];
            }
        }

        return $results;
    }

    /**
     * Validate file before upload.
     */
    public function validateFile(string $filePath, array $options = []): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }

        // Check file size limits
        $maxSize = $options['maxSize'] ?? null;
        if ($maxSize !== null && $fileSize > $maxSize) {
            throw new \InvalidArgumentException("File size ({$fileSize} bytes) exceeds maximum allowed size ({$maxSize} bytes)");
        }

        // Check file type restrictions
        $allowedTypes = $options['allowedTypes'] ?? null;
        if ($allowedTypes !== null) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes, true)) {
                throw new \InvalidArgumentException("File type '{$extension}' is not allowed. Allowed types: " . implode(', ', $allowedTypes));
            }
        }

        // Check MIME type restrictions
        $allowedMimeTypes = $options['allowedMimeTypes'] ?? null;
        if ($allowedMimeTypes !== null) {
            $content = file_get_contents($filePath, false, null, 0, 1024) ?: '';
            $mimeType = $this->detectMimeType($filePath, $content);
            
            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                throw new \InvalidArgumentException("MIME type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $allowedMimeTypes));
            }
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
}
