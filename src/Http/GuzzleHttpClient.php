<?php

declare(strict_types=1);

namespace UploadThing\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UploadThing\Exceptions\ApiException;

/**
 * Guzzle-based HTTP client implementation.
 */
final class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(
        private GuzzleClient $guzzleClient,
    ) {
    }

    /**
     * Create a new Guzzle HTTP client.
     */
    public static function create(int $timeout = 30, string $userAgent = 'uploadthing-php/1.0.0'): self
    {
        $guzzleClient = new GuzzleClient([
            'timeout' => $timeout,
            'headers' => [
                'User-Agent' => $userAgent,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return new self($guzzleClient);
    }

    /**
     * Send a PSR-7 request and return a PSR-7 response.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->guzzleClient->send($request);
        } catch (GuzzleException $e) {
            throw new ApiException(
                message: 'HTTP request failed: ' . $e->getMessage(),
                code: $e->getCode(),
                previous: $e,
            );
        }
    }
}
