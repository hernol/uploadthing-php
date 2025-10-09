<?php

declare(strict_types=1);

namespace UploadThing\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use UploadThing\Http\HttpClientInterface;

/**
 * Logging middleware for HTTP requests and responses.
 */
final class LoggingMiddleware implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Send a request with logging.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $startTime = microtime(true);

        $this->logger->info('Sending HTTP request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $this->sanitizeHeaders($request->getHeaders()),
        ]);

        try {
            $response = $this->httpClient->sendRequest($request);

            $duration = microtime(true) - $startTime;

            $this->logger->info('HTTP response received', [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration * 1000, 2),
                'headers' => $this->sanitizeHeaders($response->getHeaders()),
            ]);

            return $response;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('HTTP request failed', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'duration_ms' => round($duration * 1000, 2),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sanitize headers to remove sensitive information.
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie'];

        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), $sensitiveHeaders, true)) {
                $headers[$name] = ['***'];
            }
        }

        return $headers;
    }
}