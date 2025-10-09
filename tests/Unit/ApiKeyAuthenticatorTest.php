<?php

declare(strict_types=1);

namespace UploadThing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UploadThing\Auth\ApiKeyAuthenticator;

class ApiKeyAuthenticatorTest extends TestCase
{
    public function testAuthenticateRequest(): void
    {
        $authenticator = new ApiKeyAuthenticator('test-api-key');
        $request = new \GuzzleHttp\Psr7\Request('GET', 'https://api.uploadthing.com/test');
        
        $authenticatedRequest = $authenticator->authenticate($request);
        
        $this->assertEquals('Bearer test-api-key', $authenticatedRequest->getHeaderLine('Authorization'));
    }

    public function testGetApiKey(): void
    {
        $authenticator = new ApiKeyAuthenticator('test-api-key');
        
        $this->assertEquals('test-api-key', $authenticator->getApiKey());
    }
}
