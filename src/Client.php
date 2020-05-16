<?php

namespace LimsPhp\Lims;

use LimsPhp\Lims\Models\License;
use LimsPhp\Lims\Interfaces\ClientInterface;
use LimsPhp\Lims\Interfaces\LicenseInterface;
use LimsPhp\Lims\Exceptions\InvalidApiResponse;

class Client implements ClientInterface
{
    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    protected $liveURI = 'https://wissensnetz.dosb.de/api/lims';

    /** @var string */
    protected $demoURI = 'https://demo-dosb.wissensnetz.eu/api/lims';

    /** @var bool */
    protected $isLive = true;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
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

        return array_map(function ($data) {
            return new License($data);
        }, $response['licenses']);
    }

    public function load(string $licenseNumberDOSB): ?LicenseInterface
    {
        $response = $this->callApi('/load/'.$licenseNumberDOSB);
        if (!isset($response)) {
            return null;
        }

        return new License($response);
    }

    private function buildPath(string $path): string
    {
        if ($this->isLive) {
            return $this->liveURI.$path;
        }

        return $this->demoURI.$path;
    }

    private function callApi(string $path, array $params = []): ?array
    {
        $ch = curl_init($this->buildPath($path));
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

        return json_decode($output, true);
    }
}
