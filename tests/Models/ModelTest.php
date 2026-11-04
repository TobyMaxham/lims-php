<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Tests\Models;

use PHPUnit\Framework\TestCase;
use TobiasMaxham\PhpLims\Models\CreatedLicense;
use TobiasMaxham\PhpLims\Models\License;

final class ModelTest extends TestCase
{
    public function test_it_casts_attributes(): void
    {
        $license = new License([
            'lid'             => '42',
            'organisation_id' => '9001',
            'honor_code'      => '0',
            'first_aid'       => 1,
            'valid_until'     => '1767225600',
            'firstname'       => 'Max',
        ]);

        $this->assertSame(42, $license->lid);
        $this->assertSame(9001, $license->organisation_id);
        $this->assertFalse($license->honor_code);
        $this->assertTrue($license->first_aid);
        $this->assertSame(1767225600, $license->valid_until);
        $this->assertSame('Max', $license->firstname);
    }

    public function test_empty_values_are_not_casted(): void
    {
        $license = new License(['valid_until' => '', 'first_aid_date' => null]);

        $this->assertSame('', $license->valid_until);
        $this->assertNull($license->first_aid_date);
    }

    public function test_missing_attributes_return_null_and_support_isset(): void
    {
        $license = new CreatedLicense(['lid' => '1']);

        $this->assertNull($license->license_number_dosb);
        $this->assertTrue(isset($license->lid));
        $this->assertFalse(isset($license->license_number_dosb));
    }

    public function test_to_array_returns_the_casted_attributes(): void
    {
        $license = new CreatedLicense(['lid' => '1', 'license_number_dosb' => 'ABC-1']);

        $this->assertSame(['lid' => 1, 'license_number_dosb' => 'ABC-1'], $license->toArray());
    }
}
