<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Tests\Http;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use TobiasMaxham\PhpLims\Http\CurlClient;
use TobiasMaxham\PhpLims\Http\CurlHttpFactory;
use TobiasMaxham\PhpLims\Http\Exceptions\CurlNetworkException;
use TobiasMaxham\PhpLims\Http\Psr7\Request;

#[RequiresPhpExtension('curl')]
final class CurlClientTest extends TestCase
{
    private static int $port;

    /** @var resource|null */
    private static $serverProcess = null;

    public static function setUpBeforeClass(): void
    {
        self::$port = random_int(20000, 60000);

        $command = sprintf(
            'exec %s -S 127.0.0.1:%d %s',
            escapeshellarg(PHP_BINARY),
            self::$port,
            escapeshellarg(__DIR__.'/fixtures/router.php'),
        );

        self::$serverProcess = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        // Give the built-in webserver a moment to start accepting connections.
        $deadline = microtime(true) + 5;

        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', self::$port, $errorCode, $errorMessage, 0.2);

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(50_000);
        }

        self::fail('PHP built-in webserver did not start in time.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
        }
    }

    private function url(string $path): string
    {
        return 'http://127.0.0.1:'.self::$port.$path;
    }

    public function test_it_sends_a_post_request_with_headers_and_body(): void
    {
        $factory = new CurlHttpFactory();
        $request = $factory->createRequest('POST', $this->url('/echo'))
            ->withHeader('Authorization', 'Basic dXNlcjpzZWNyZXQ=')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream('{"limit":5}'));

        $response = (new CurlClient())->sendRequest($request);
        $data = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('POST', $data['method']);
        $this->assertSame('Basic dXNlcjpzZWNyZXQ=', $data['authorization']);
        $this->assertSame('application/json', $data['accept']);
        $this->assertSame('application/json', $data['content_type']);
        $this->assertSame('{"limit":5}', $data['body']);
    }

    public function test_it_sends_a_content_length_for_empty_post_requests(): void
    {
        $response = (new CurlClient())->sendRequest(new Request('POST', $this->url('/echo')));
        $data = json_decode((string) $response->getBody(), true);

        $this->assertSame('POST', $data['method']);
        $this->assertSame('0', $data['content_length']);
        $this->assertSame('', $data['body']);
    }

    public function test_it_returns_error_responses_with_status_code_and_headers(): void
    {
        $response = (new CurlClient())->sendRequest(new Request('POST', $this->url('/not-found')));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('yes', $response->getHeaderLine('X-Custom'));
        $this->assertSame('["License not found"]', (string) $response->getBody());
    }

    public function test_it_throws_a_network_exception_when_the_connection_is_refused(): void
    {
        $request = new Request('POST', 'http://127.0.0.1:1/echo');

        try {
            (new CurlClient())->sendRequest($request);
            $this->fail('Expected CurlNetworkException');
        } catch (CurlNetworkException $e) {
            $this->assertSame($request, $e->getRequest());
        }
    }

    public function test_it_throws_a_network_exception_on_timeout(): void
    {
        $this->expectException(CurlNetworkException::class);

        (new CurlClient(timeout: 1))->sendRequest(new Request('POST', $this->url('/slow')));
    }
}
