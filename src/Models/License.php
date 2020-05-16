<?php

namespace TobyMaxham\Lims\Models;

use TobyMaxham\Lims\Interfaces\LicenseInterface;

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
