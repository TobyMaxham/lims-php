<?php

namespace LimsPhp\Lims\Models;

use LimsPhp\Lims\Interfaces\LicenseInterface;

class License implements LicenseInterface
{
    /** @var array */
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function __get(string $attribute)
    {
        return $this->data[$attribute] ?? null;
    }
}
