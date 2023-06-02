<?php

namespace TobyMaxham\Lims\Interfaces;

interface ClientInterface
{
    public function __construct(string $username, string $password);

    public function lookup(array $params = []): array;

    public function load(string $licenseNumberDOSB): LicenseInterface|null;

    public function download(string $licenseNumberDOSB, string $format = 'dina4', $urlOnly = false): string|null;

    public function isDemo(): void;

    public function isLive(): void;
}