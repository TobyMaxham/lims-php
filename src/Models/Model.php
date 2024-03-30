<?php

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
 * @property int $honor_code
 * @property int $honor_code_date
 * @property int $first_aid
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
 */
abstract class Model
{
    protected array $casts = [];

    public function __construct(private array $attributes = [])
    {
        $this->processAttributes();
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    private function processAttributes(): void
    {
        foreach ($this->casts as $key => $type) {
            if (array_key_exists($key, $this->attributes)) {

                if (null || empty($this->attributes[$key])) {
                    continue;
                }
                if (0 === $this->attributes[$key]) {
                    $this->attributes[$key] = 0;
                    continue;
                }

                $this->attributes[$key] = match ($type) {
                    'int' => (int) $this->attributes[$key],
                    'float' => (float) $this->attributes[$key],
                    'bool' => (bool) $this->attributes[$key],
                    /*
                     * Seems like the service uses a wired timezone for the dates.
                     * So we won't use the DateTime object in this version.
                     */
//                    'datetime' => new \DateTime('@'.$this->attributes[$key]),
                    default => $this->attributes[$key],
                };
            }
        }
    }
}