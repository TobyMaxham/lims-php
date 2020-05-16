<?php

namespace LimsPhp\Lims\Interfaces;

interface ClientInterface
{
    public function __construct(string $username, string $password);

    public function lookup(array $params = []): array;

    public function load(string $licenseNumberDOSB): ?LicenseInterface;

    public function isDemo(): void;

    public function isLive(): void;
}
