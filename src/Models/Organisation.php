<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Models;

/**
 * An organisation (Verband) returned by `lookup_organisations`.
 *
 * @property int $organisation_id
 * @property string $name
 * @property int|null $parent_id
 */
class Organisation extends Model
{
    protected array $casts = [
        'organisation_id' => 'int',
        'parent_id'       => 'int',
    ];
}
