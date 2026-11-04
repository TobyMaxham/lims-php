<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use LimsPhp\Lims\Http\Exceptions\CurlNetworkException;
use LimsPhp\Lims\Http\Psr7\Response;

/**
 * Minimal PSR-18 client based on ext-curl.
 *
 * Used as fallback when no PSR-18 client is passed to the LiMS client
 * and none can be discovered in the project.
 */
final class CurlClient implements ClientInterface
{
    public function __construct(
        private readonly int $timeout = 60,
    ) {
        if (! extension_loaded('curl')) {
            throw new RuntimeException(
                'ext-curl ist nicht aktiviert. Bitte curl aktivieren oder einen eigenen PSR-18-Client via Konstruktor übergeben.',
            );
        }
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $handle = curl_init();

        $headerLines = [];

        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headerLines[] = "{$name}: {$value}";
            }
        }

        $responseHeaders = [];

        curl_setopt_array($handle, [
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            // The LiMS FAQ recommends forcing HTTP/1.1.
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADERFUNCTION => function ($curlHandle, string $headerLine) use (&$responseHeaders): int {
                $trimmed = trim($headerLine, "\r\n");

                if ($trimmed === '' || ! str_contains($trimmed, ':')) {
                    return strlen($headerLine);
                }

                [$name, $value] = explode(':', $trimmed, 2);
                $responseHeaders[trim($name)][] = trim($value);

                return strlen($headerLine);
            },
        ]);

        $body = (string) $request->getBody();

        // Always set a body for POST/PUT/PATCH so curl sends a Content-Length header.
        if ($body !== '' || in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH'], true)) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($handle);

        if ($responseBody === false) {
            $errorMessage = curl_error($handle);

            throw new CurlNetworkException(
                "Curl-Request fehlgeschlagen: {$errorMessage}",
                $request,
            );
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        return new Response($statusCode, $responseHeaders, (string) $responseBody);
    }
}
