<?php

namespace LimsPhp\Lims;

use LimsPhp\Lims\Models\License;
use LimsPhp\Lims\Interfaces\ClientInterface;
use LimsPhp\Lims\Interfaces\LicenseInterface;
use LimsPhp\Lims\Exceptions\InvalidApiResponse;
use LimsPhp\Lims\Exceptions\InvalidDownloadFormatException;

class Client implements ClientInterface
{

    protected string $liveURI = 'https://wissensnetz.dosb.de/api/lims';

    protected string $demoURI = 'https://demo-dosb.wissensnetz.eu/api/lims';

    protected bool $isLive = true;

    protected int $timeout = 30;

    public function __construct(private string $username, private string $password)
    {
        //
    }

    public function isDemo(): void
    {
        $this->isLive = false;
    }

    public function isLive(): void
    {
        $this->isLive = true;
    }

    public function lookup(array $params = []): array
    {
        $response = $this->callApi('/lookup', $params);

        if (!isset($response['licenses'])) {
            throw new InvalidApiResponse('No Licenses found in response');
        }

        return array_map(fn ($data) => new License($data), $response['licenses']);
    }

    public function load(string $licenseNumberDOSB): LicenseInterface|null
    {
        $response = $this->callApi('/load/'.$licenseNumberDOSB);
        if (!isset($response)) {
            return null;
        }

        return new License($response);
    }


    public function download(string $licenseNumberDOSB, string $format = 'dina4', $urlOnly = false): string|null
    {
        if (! in_array($format, ['dina4', 'card', 'signet',])) {
            throw new InvalidDownloadFormatException($format);
        }

        $response = $this->callApi('/download/'.$licenseNumberDOSB, [
            'format' => $format,
            'file_url_only' => $urlOnly,
        ], true);
        if (!isset($response) || !isset($response['response'])) {
            return null;
        }

        if (! $urlOnly) {
            return $response['response'];
        }

        $download = json_decode($response['response'], true);
        if (!isset($download['download_url'])) {
            throw new InvalidApiResponse('Could not generate DownloadURL');
        }

        return $download['download_url'];
    }

    private function buildPath(string $path): string
    {
        if ($this->isLive) {
            return $this->liveURI.$path;
        }

        return $this->demoURI.$path;
    }

    private function callApi(string $path, array $params = [], bool $isResource = false): array
    {
        $ch = curl_init($this->buildPath($path));
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_POST, true);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 !== $httpCode) {
            throw new InvalidApiResponse('Invalid HTTP status code: '.$httpCode);
        }

        if ($isResource) {
            return [
                'response' => $output,
            ];
        }

        return json_decode($output, true);
    }
}