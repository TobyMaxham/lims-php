<?php

namespace LimsPhp\Lims\Support;

use LimsPhp\Lims\Client;
use LimsPhp\Lims\Exceptions\InvalidInputDataException;

class LimsValidator
{
    private string $type;

    public function __construct(private Client $client)
    {

    }

    /**
     * @throws InvalidInputDataException
     */
    public function validateRequest(array &$license, string $type): void
    {
        $this->type = $type;

        $this->validatePersonData($license);

        $this->validateCommunicationData($license);

        $this->validateLicenseData($license);

        if (isset($license['honor_code']) && (!is_bool($license['honor_code']) && !in_array($license['honor_code'],
                    [0, 1]))) {
            throw new InvalidInputDataException('Honor code must be a boolean or 0 or 1.');
        } elseif (isset($license['honor_code'])) {
            $license['honor_code'] = (int) $license['honor_code'];
        }

        if (isset($license['honor_code_date']) && !is_int($license['honor_code_date'])) {
            throw new InvalidInputDataException('Honor code date date must be a valid timestamp.');
        }

        if (isset($license['first_aid']) && (!is_bool($license['first_aid']) && !in_array($license['first_aid'],
                    [0, 1]))) {
            throw new InvalidInputDataException('First aid must be a boolean or 0 or 1.');
        } elseif (isset($license['first_aid'])) {
            $license['first_aid'] = (int) $license['first_aid'];
        }

        if (isset($license['first_aid_date']) && !is_int($license['first_aid_date'])) {
            throw new InvalidInputDataException('First aid date date must be a valid timestamp.');
        }

        if (isset($license['license_number_organisation']) && empty($license['license_number_organisation'])) {
            throw new InvalidInputDataException('License number organisation must not be empty.');
        }

        if (isset($license['prequalification_type']) && empty($license['prequalification_type'])) {
            throw new InvalidInputDataException('Prequalification type must not be empty.');
        }

        if (isset($license['prequalification_number']) && empty($license['prequalification_number'])) {
            throw new InvalidInputDataException('Prequalification number must not be empty.');
        }

        if (isset($license['organisation_id']) && (!is_int($license['organisation_id'])
                || $license['organisation_id'] <= 0)) {
            throw new InvalidInputDataException('Organisation id must be an integer.');
        }

        if (isset($license['lid']) && (!is_int($license['lid'])
                || $license['lid'] <= 0)) {
            throw new InvalidInputDataException('Lid must be an integer.');
        }

        if (isset($license['license_number_dosb']) && empty($license['license_number_dosb'])) {
            throw new InvalidInputDataException('License number dosb must not be empty.');
        }
    }

    /**
     * @throws InvalidInputDataException
     */
    private function validatePersonData(array $license): void
    {
        $requiredFields = LicenseValidator::required($this->type);

        foreach ($requiredFields as $field) {
            if (('create' === $this->type || isset($license[$field])) && ($license[$field] === '' || is_null($license[$field]))) {
                throw new InvalidInputDataException('`'.$field.'` must not be empty.');
            }
        }

        if (isset($license['academic_title']) && empty($license['academic_title'])) {
            throw new InvalidInputDataException('Academic Title must not be empty.');
        }

        if (isset($license['birthdate']) && (!is_int($license['birthdate'])
                || $license['birthdate'] >= strtotime("- {$this->client->minYears} years")
                || $license['birthdate'] <= strtotime("- {$this->client->maxYears} years"))) {
            throw new InvalidInputDataException('Birthdate must be a valid timestamp and between '.$this->client->minYears.' and '.$this->client->maxYears.' years ago.');
        }

        if (isset($license['gender']) && !in_array($license['gender'], ['m', 'w', 'u'])) {
            throw new InvalidInputDataException('Gender must be either "m", "w" or "u".');
        }
    }

    /**
     * @throws InvalidInputDataException
     */
    private function validateCommunicationData(array $license): void
    {
        if (isset($license['street']) && empty($license['street'])) {
            throw new InvalidInputDataException('Street must not be empty.');
        }

        if (isset($license['postal']) && empty($license['postal'])) {
            throw new InvalidInputDataException('Postal must not be empty.');
        }

        if (isset($license['city']) && empty($license['city'])) {
            throw new InvalidInputDataException('City must not be empty.');
        }

        if (isset($license['mail']) && (empty($license['mail']) || !filter_var($license['mail'],
                    FILTER_VALIDATE_EMAIL))) {
            throw new InvalidInputDataException('Mail must be a valid email address.');
        }

        if (isset($license['phone']) && empty($license['phone'])) {
            throw new InvalidInputDataException('Phone must not be empty.');
        }
    }

    /**
     * @throws InvalidInputDataException
     */
    private function validateLicenseData(array $license): void
    {
        if (isset($license['issue_date']) && !is_int($license['issue_date'])) {
            throw new InvalidInputDataException('Issue date must be a valid timestamp.');
        }

        if (isset($license['first_issue_date']) && !is_int($license['first_issue_date'])) {
            throw new InvalidInputDataException('First issue date must be a valid timestamp.');
        }

        if (isset($license['training_course_id']) && (!is_int($license['training_course_id'])
                || $license['training_course_id'] <= 0)) {
            throw new InvalidInputDataException('`training_course_id` must be an integer.');
        }

        if (isset($license['issue_place']) && empty($license['issue_place'])) {
            throw new InvalidInputDataException('Issue Place must not be empty.');
        }

        if (isset($license['valid_until']) && !is_int($license['valid_until'])) {
            throw new InvalidInputDataException('Valid until date must be a valid timestamp.');
        }
    }
}