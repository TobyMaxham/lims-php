<?php

namespace LimsPhp\Lims\Models;

use LimsPhp\Lims\Interfaces\LicenseInterface;

class License implements LicenseInterface
{
    public function __construct(protected array $data = [])
    {
        //
    }

    public function __get(string $attribute)
    {
        return $this->data[$attribute] ?? null;
    }
}