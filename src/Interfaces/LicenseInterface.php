<?php

namespace TobyMaxham\Lims\Interfaces;

/**
 * @property $lid
 * @property $firstname
 * @property $lastname
 * @property $academic_title
 * @property $birthdate
 * @property $gender
 * @property $street
 * @property $postal
 * @property $city
 * @property $mail
 * @property $phone
 * @property $training_course_id
 * @property $valid_until
 * @property $issue_date
 * @property $first_issue_date
 * @property $issue_place
 * @property $license_number_dosb
 * @property $license_number_organisation
 * @property $honor_code
 * @property $honor_code_date
 * @property $first_aid
 * @property $first_aid_date
 * @property $organisation_id
 * @property $prequalification_type
 * @property $prequalification_number
 * @property $custom_1
 * @property $custom_2
 * @property $additional_info
 * @property $training_activity_1
 * @property $training_activity_2
 */
interface LicenseInterface
{
    public function __construct(array $data = []);
}