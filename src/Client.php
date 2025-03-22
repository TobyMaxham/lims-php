<?php

namespace LimsPhp\Lims;

use LimsPhp\Lims\Models\License;
use GuzzleHttp\Exception\GuzzleException;
use LimsPhp\Lims\Models\DeletedLicense;
use LimsPhp\Lims\Models\UpdatedLicense;
use LimsPhp\Lims\Models\CreatedLicense;
use LimsPhp\Lims\Support\LimsValidator;
use LimsPhp\Lims\Exceptions\NotFoundException;
use LimsPhp\Lims\Exceptions\NotAcceptableException;
use LimsPhp\Lims\Exceptions\UnauthorizedException;
use LimsPhp\Lims\Exceptions\AccessDeniedException;
use LimsPhp\Lims\Exceptions\InvalidResponseException;
use LimsPhp\Lims\Exceptions\InvalidInputDataException;
use LimsPhp\Lims\Exceptions\InvalidDownloadFormatException;
use LimsPhp\Lims\Exceptions\UnprocessableEntityException;

class Client
{
    private string $apiEndpoint = 'https://wissensnetz.dosb.de/api/lims';

    private string $testApiEndpoint = 'https://demo-dosb.wissensnetz.eu/api/lims';

    private bool $testmode = false;

    private null|int $organizationId = null;

    private \GuzzleHttp\Client $guzzleClient;

    public int $minYears = 0;

    public int $maxYears = 120;

    private LimsValidator $validator;

    public function __construct(private string $username, private string $password)
    {
        $this->validator = new LimsValidator($this);
        $this->guzzleClient = new \GuzzleHttp\Client([
            'timeout' => 30,
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password),
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * @throws InvalidResponseException|InvalidInputDataException
     */
    public function create(array $license): CreatedLicense
    {
        if (isset($license['license_number_dosb']) || isset($license['lid'])) {
            throw new InvalidInputDataException('You can not create a license with an existing license number or lid');
        }

        $this->validate($license, 'create');

        return $this->request($license);
    }

    /**
     * @throws InvalidResponseException
     * @throws InvalidInputDataException
     * @throws UnauthorizedException
     * @throws NotAcceptableException
     * @throws UnprocessableEntityException
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function correct(array $license, int|string $id = null): UpdatedLicense
    {
        if(isset($license['organisation_id'])) {
            throw new InvalidInputDataException('You can not correct an organisation_id');
        }

        if ($id) {
            $license[is_numeric($id) ? 'lid' : 'license_number_dosb'] = $id;
        }
        if (!isset($license['license_number_dosb']) && !isset($license['lid'])) {
            throw new InvalidInputDataException('You must provide a license number or lid to correct a license');
        }

        $this->validate($license, 'correct');

        return $this->request($license);
    }

    /**
     * @throws InvalidResponseException
     * @throws InvalidInputDataException
     * @throws UnauthorizedException
     * @throws NotAcceptableException
     * @throws UnprocessableEntityException
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function extend(array $license, int|string $id = null): UpdatedLicense
    {
        if(isset($license['organisation_id'])) {
            throw new InvalidInputDataException('You can not correct an `organisation_id`');
        }
        if(isset($license['first_issue_date'])) {
            throw new InvalidInputDataException('You can not correct an `first_issue_date`');
        }
        if(isset($license['training_course_id'])) {
            throw new InvalidInputDataException('You can not correct an `training_course_id`');
        }

        if ($id) {
            $license[is_numeric($id) ? 'lid' : 'license_number_dosb'] = $id;
        }
        if (!isset($license['license_number_dosb']) && !isset($license['lid'])) {
            throw new InvalidInputDataException('You must provide a license number or lid to correct a license');
        }

        $this->validate($license, 'extend');

        return $this->request($license);
    }

    /**
     * @throws InvalidInputDataException
     */
    private function validate(array $license, string $type): void
    {
        $this->validator->validateRequest($license, $type);
    }

    /**
     * @throws UnprocessableEntityException
     * @throws InvalidResponseException
     * @throws AccessDeniedException
     * @throws UnauthorizedException
     * @throws NotAcceptableException
     * @throws NotFoundException
     */
    protected function request(array $license): CreatedLicense|UpdatedLicense
    {
//        $validator = new LimsValidator();
//        $this->validator->validateRequest($license);
//        dd(345345345);

        // nullable|empty
        // custom_1
        // custom_2
        // additional_info
        // training_activity_1
        // training_activity_2

        $response = $this->doRequest('POST',
            vsprintf('%s/request', [
                $this->endpoint(),
            ]),
            [
                'json' => $license,
            ]
        );


        $newLicense = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE || empty($newLicense)) {
            throw new InvalidResponseException('Invalid response from LiMS API');
        }

        if (isset($license['license_number_dosb']) || isset($license['lid'])) {
            return new UpdatedLicense($newLicense);
        }

        return new CreatedLicense($newLicense);
    }

    public function load(string $licenseNumberDosb)
    {
        $response = $this->doRequest('POST',
            vsprintf('%s/load/%s', [
                $this->endpoint(),
                urlencode($licenseNumberDosb),
            ])
        );


        $license = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE || empty($license)) {
            throw new InvalidResponseException('Invalid response from LiMS API');
        }

        return new License($license);
    }

    public function lookup(
        int $organisationId = null,
        int $offset = null,
        int $limit = null,
        bool $validationStatus = null,
        bool $isObscured = null,
        bool $idsOnly = null,
        bool $licenseNumberOrganisation = null
    ) {
        $options = [];
        if ($organisationId > 0) {
            $options['json']['organisation_id'] = $organisationId;
        }

        if (is_int($offset) && $offset >= 0) {
            $options['json']['offset'] = $offset;
        }

        if ($limit > 0) {
            $options['json']['limit'] = $limit;
        }

        if (is_bool($validationStatus)) {
            $options['json']['validation_status'] = $validationStatus;
        }

        if (is_bool($isObscured)) {
            $options['json']['is_obscured'] = $isObscured;
        }

        if (is_bool($idsOnly)) {
            $options['json']['ids_only'] = $idsOnly;
        }

        if (is_bool($licenseNumberOrganisation)) {
            $options['json']['license_number_organisation'] = $licenseNumberOrganisation;
        }

        $response = $this->doRequest('POST',
            vsprintf('%s/lookup', [
                $this->endpoint(),
            ]), $options
        );

        $result = json_decode($response, true);
        if (json_last_error() != JSON_ERROR_NONE || empty($result) || !isset($result['licenses'])) {
            throw new InvalidResponseException('Invalid response from LiMS API');
        }

        if (empty($result['licenses'])) {
            return [];
        }

        if ($idsOnly) {
            return $result['licenses'];
        }

        return array_map(fn ($license) => new License($license), $result['licenses']);
    }

    public function delete(string $licenseNumberDosb): DeletedLicense
    {
        $response = $this->doRequest('POST',
            vsprintf('%s/delete/%s', [
                $this->endpoint(),
                urlencode($licenseNumberDosb),
            ])
        );


        $deleted = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE || empty($deleted)) {
            throw new InvalidResponseException('Invalid response from LiMS API');
        }

        return new DeletedLicense($deleted);
    }

    /**
     * Load the license print template (dina4 and card as PDF, signet as JPG).
     *
     * Returns the raw file content, or only the download URL if $urlOnly is true.
     *
     * @throws InvalidDownloadFormatException
     * @throws InvalidResponseException
     * @throws UnauthorizedException
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function download(string $licenseNumberDosb, string $format = 'dina4', bool $urlOnly = false): string
    {
        if (!in_array($format, ['dina4', 'card', 'signet'], true)) {
            throw new InvalidDownloadFormatException('Invalid download format: '.$format.' (allowed: dina4, card, signet)');
        }

        $data = ['format' => $format];
        if ($urlOnly) {
            $data['file_url_only'] = 1;
        }

        $response = $this->doRequest('POST',
            vsprintf('%s/download/%s', [
                $this->endpoint(),
                urlencode($licenseNumberDosb),
            ]),
            [
                'json' => $data,
            ]
        );

        if (!$urlOnly) {
            if ('' === $response) {
                throw new InvalidResponseException('Empty download response from LiMS API');
            }

            return $response;
        }

        $download = json_decode($response, true);
        if (json_last_error() != JSON_ERROR_NONE || empty($download['download_url'])) {
            throw new InvalidResponseException('Could not generate download URL');
        }

        return $download['download_url'];
    }

    public function migrationRequest(string $licenseNumberDosb, int $organisationId)
    {
        dd('EXIT');
        $response = $this->doRequest('POST',
            vsprintf('%s/migration_request', [
                $this->endpoint(),
            ]),
            [
                'json' => [
                    'license_number_dosb' => $licenseNumberDosb,
                    'organisation_id'     => $organisationId,
                ],
            ]
        );

        dd($response);

    }

    private function doRequest(string $method, string $uri, array $options = []): string
    {
        try {
            $response = $this->guzzleClient->request($method, $uri, $options);
        } catch (GuzzleException $e) {

            $message = null;
            if ($e->hasResponse()) {
                $message = $e->getResponse()?->getBody()?->getContents();
                if (!empty($message)) {
                    $json = json_decode($message, true);

                    if (json_last_error() == JSON_ERROR_NONE && !empty($json)) {
                        $message = implode(' | ', $json);
                    }
                }
            }

            /**
             * Predefined Exceptions from LiMS API (https://gitlab.ghostthinker.de/pub/lims_connect)
             */

            // 401 Unauthorized
            if ($e->getCode() === 401) {
                throw new UnauthorizedException($message, 401);
            }

            // 403 Access Denied
            if ($e->getCode() === 403) {
                throw new AccessDeniedException($message, 403);
            }

            // 404 Not Found
            if ($e->getCode() === 404) {
                throw new NotFoundException($message, 404);
            }

            // 406 Not Acceptable
            if ($e->getCode() === 406) {
                throw new NotAcceptableException($message, 406);
            }

            // 422 Unprocessable Entity
            if ($e->getCode() === 422) {
                throw new UnprocessableEntityException($message, 422);
            }

            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $response->getBody()->getContents();
    }

    private function endpoint(): string
    {
        return $this->testmode ? $this->testApiEndpoint : $this->apiEndpoint;
    }

    public function setTestMode(bool $testmode = true): void
    {
        $this->testmode = $testmode;
    }

    public function setMaxYears(int $year): void
    {
        $this->maxYears = $year;
    }

    public function setMinYears(int $year): void
    {
        $this->minYears = $year;
    }
}