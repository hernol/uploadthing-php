<?php

declare(strict_types=1);

namespace UploadThing\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client interface for UploadThing API.
 */
interface HttpClientInterface
{
    /**
     * Send a PSR-7 request and return a PSR-7 response.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
