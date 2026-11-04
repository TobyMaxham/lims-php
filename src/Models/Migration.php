<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Models;

/**
 * An open migration request returned by `migration_get`.
 *
 * @property int $migration_id Id for `migrationAccept()` / `migrationDecline()` (equals the `lid` of the license)
 * @property string $license_number_dosb
 * @property int $organisation_id Target organisation that requested the license
 */
class Migration extends Model
{
    protected array $casts = [
        'migration_id'    => 'int',
        'organisation_id' => 'int',
    ];
}
