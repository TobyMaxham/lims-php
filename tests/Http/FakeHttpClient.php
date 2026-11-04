<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Tests\Http;

use Closure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 test double that records all requests and returns a fixed response.
 */
final class FakeHttpClient implements ClientInterface
{
    /** @var RequestInterface[] */
    public array $requests = [];

    /**
     * @param ResponseInterface|Closure(RequestInterface): ResponseInterface $response
     */
    public function __construct(
        private readonly ResponseInterface|Closure $response,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        if ($this->response instanceof Closure) {
            return ($this->response)($request);
        }

        return $this->response;
    }

    public function lastRequest(): ?RequestInterface
    {
        return $this->requests[array_key_last($this->requests)] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lastJsonBody(): ?array
    {
        $body = (string) $this->lastRequest()?->getBody();

        return $body === '' ? null : json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    }
}
