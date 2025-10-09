<?php

declare(strict_types=1);

namespace UploadThing\Utils;

/**
 * Multipart form data builder for file uploads.
 */
final class MultipartBuilder
{
    private string $boundary;
    private array $fields = [];
    private array $files = [];

    public function __construct()
    {
        $this->boundary = '----formdata-' . bin2hex(random_bytes(16));
    }

    /**
     * Add a text field to the multipart data.
     */
    public function addField(string $name, string $value): self
    {
        $this->fields[$name] = $value;
        return $this;
    }

    /**
     * Add a file to the multipart data.
     */
    public function addFile(string $name, string $filename, string $content, ?string $mimeType = null): self
    {
        $this->files[$name] = [
            'filename' => $filename,
            'content' => $content,
            'mime_type' => $mimeType ?? 'application/octet-stream',
        ];
        return $this;
    }

    /**
     * Build the multipart form data.
     */
    public function build(): string
    {
        $body = '';

        // Add fields
        foreach ($this->fields as $name => $value) {
            $body .= "--{$this->boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n";
            $body .= "\r\n";
            $body .= "{$value}\r\n";
        }

        // Add files
        foreach ($this->files as $name => $file) {
            $body .= "--{$this->boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$file['filename']}\"\r\n";
            $body .= "Content-Type: {$file['mime_type']}\r\n";
            $body .= "\r\n";
            $body .= "{$file['content']}\r\n";
        }

        $body .= "--{$this->boundary}--\r\n";

        return $body;
    }

    /**
     * Get the content type header value.
     */
    public function getContentType(): string
    {
        return "multipart/form-data; boundary={$this->boundary}";
    }
}