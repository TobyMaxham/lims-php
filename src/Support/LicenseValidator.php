<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Support;

class LicenseValidator
{
    /**
     * Fields that must not be empty. For `create` all of them (except the ids) are required,
     * for `correct` and `extend` they must not be empty if they are passed.
     */
    private const REQUIRED_FIELDS = [
        'firstname',
        'lastname',
        'birthdate',
        'gender',
        'street',
        'postal',
        'city',
        'issue_date',
        'training_course_id',
        'issue_place',
        'honor_code',
        'first_aid',
        'organisation_id',
        'lid',
        'license_number_dosb',
    ];

    public static function required(string $type): array
    {
        if ('create' === $type) {
            return array_values(array_diff(self::REQUIRED_FIELDS, ['lid', 'license_number_dosb']));
        }

        return self::REQUIRED_FIELDS;
    }
}