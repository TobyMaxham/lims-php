<?php

namespace TobiasMaxham\PhpLims\Support;

class LicenseValidator
{
    // Required not empty fields
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
////        'license_number_organisation',
        'organisation_id',
        'lid',
        'license_number_dosb',
    ];
    private const OPTIONAL_FIELDS = ['academic_title'];

    public static function required(string $type): array
    {
        $fields = self::REQUIRED_FIELDS;
        if ('create' == $type) {
            $fields = array_diff($fields, ['lid', 'license_number_dosb']);

            return $fields;
        }

        return $fields;
    }
}