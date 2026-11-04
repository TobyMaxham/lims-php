<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Models;

/**
 * @property string $firstname
 * @property string $lastname
 * @property string $academic_title
 * @property int $birthdate
 * @property string $gender
 * @property string $street
 * @property string $postal
 * @property string $city
 * @property string $mail
 * @property string $phone
 * @property int $issue_date
 * @property int $first_issue_date
 * @property int $training_course_id
 * @property string $issue_place
 * @property int $valid_until
 * @property bool $honor_code
 * @property int $honor_code_date
 * @property bool $first_aid
 * @property int $first_aid_date
 * @property string $license_number_organisation
 * @property string $prequalification_type
 * @property string $prequalification_number
 * @property int $organisation_id
 * @property int $lid
 * @property string $license_number_dosb
 * @property string $custom_1
 * @property string $custom_2
 * @property string $additional_info
 * @property string $training_activity_1
 * @property string $training_activity_2
 * @property string $license_holder_uuid
 */
class License extends Model
{
    protected array $casts = [
        'lid'                => 'int',
        'training_course_id' => 'int',
        'organisation_id'    => 'int',
        'birthdate'          => 'int',
        'valid_until'        => 'int',
        'issue_date'         => 'int',
        'first_issue_date'   => 'int',
        'honor_code_date'    => 'int',
        'first_aid_date'     => 'int',
        'honor_code'         => 'bool',
        'first_aid'          => 'bool',
    ];
}
