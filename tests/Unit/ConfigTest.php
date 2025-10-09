<?php

declare(strict_types=1);

namespace UploadThing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UploadThing\Config;

class ConfigTest extends TestCase
{
    public function testCreateConfig(): void
    {
        $config = Config::create();
        
        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('', $config->apiKey);
        $this->assertEquals('https://api.uploadthing.com', $config->baseUrl);
        $this->assertEquals(30, $config->timeout);
        $this->assertEquals(3, $config->maxRetries);
        $this->assertEquals(1.0, $config->retryDelay);
    }

    public function testWithApiKey(): void
    {
        $config = Config::create()->withApiKey('test-api-key');
        
        $this->assertEquals('test-api-key', $config->apiKey);
    }

    public function testWithBaseUrl(): void
    {
        $config = Config::create()->withBaseUrl('https://custom-api.com');
        
        $this->assertEquals('https://custom-api.com', $config->baseUrl);
    }

    public function testWithTimeout(): void
    {
        $config = Config::create()->withTimeout(60);
        
        $this->assertEquals(60, $config->timeout);
    }

    public function testWithRetryPolicy(): void
    {
        $config = Config::create()->withRetryPolicy(5, 2.0);
        
        $this->assertEquals(5, $config->maxRetries);
        $this->assertEquals(2.0, $config->retryDelay);
    }

    public function testWithUserAgent(): void
    {
        $config = Config::create()->withUserAgent('custom-agent/1.0.0');
        
        $this->assertEquals('custom-agent/1.0.0', $config->userAgent);
    }

    public function testFluentInterface(): void
    {
        $config = Config::create()
            ->withApiKey('test-key')
            ->withBaseUrl('https://test.com')
            ->withTimeout(45)
            ->withRetryPolicy(2, 1.5)
            ->withUserAgent('test-agent/1.0.0');
        
        $this->assertEquals('test-key', $config->apiKey);
        $this->assertEquals('https://test.com', $config->baseUrl);
        $this->assertEquals(45, $config->timeout);
        $this->assertEquals(2, $config->maxRetries);
        $this->assertEquals(1.5, $config->retryDelay);
        $this->assertEquals('test-agent/1.0.0', $config->userAgent);
    }
}
