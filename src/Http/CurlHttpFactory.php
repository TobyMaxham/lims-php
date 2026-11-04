<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use LimsPhp\Lims\Http\Psr7\Request;
use LimsPhp\Lims\Http\Psr7\Stream;

/**
 * Minimal PSR-17 request and stream factory for the built-in HTTP stack.
 */
final class CurlHttpFactory implements RequestFactoryInterface, StreamFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $content = @file_get_contents($filename);

        if ($content === false) {
            throw new RuntimeException("Datei konnte nicht gelesen werden: {$filename}");
        }

        return new Stream($content);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream((string) stream_get_contents($resource));
    }
}
