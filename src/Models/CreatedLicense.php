<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Models;

/**
 * Contains the id fields `lid` and `license_number_dosb`.
 *
 * @property int $lid
 * @property string $license_number_dosb
 */
class CreatedLicense extends Model
{
    protected array $casts = [
        'lid' => 'int',
    ];
}
