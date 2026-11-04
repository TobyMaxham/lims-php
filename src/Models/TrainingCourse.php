<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Models;

/**
 * A DOSB training course (Ausbildungsgang) returned by `lookup_training_courses`.
 *
 * @property string $id Training course id, used as `training_course_id` for licenses
 * @property string $title
 * @property int $level License level (Lizenzstufe)
 * @property string $course e.g. "T-C/B" or "ÜL-B"
 * @property string|null $sport
 * @property string|null $profile
 * @property string|null $discipline
 * @property string|null $focus
 * @property int $validity_period Validity in years
 */
class TrainingCourse extends Model
{
    protected array $casts = [
        'level'           => 'int',
        'validity_period' => 'int',
    ];
}
