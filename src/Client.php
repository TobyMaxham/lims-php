<?php

namespace TobiasMaxham\PhpLims;

use TobiasMaxham\PhpLims\Models\License;
use GuzzleHttp\Exception\GuzzleException;
use TobiasMaxham\PhpLims\Models\DeletedLicense;
use TobiasMaxham\PhpLims\Models\UpdatedLicense;
use TobiasMaxham\PhpLims\Models\CreatedLicense;
use TobiasMaxham\PhpLims\Exceptions\NotFoundException;
use TobiasMaxham\PhpLims\Exceptions\NotAcceptableException;
use TobiasMaxham\PhpLims\Exceptions\UnauthorizedException;
use TobiasMaxham\PhpLims\Exceptions\AccessDeniedException;
use TobiasMaxham\PhpLims\Exceptions\InvalidResponseException;
use TobiasMaxham\PhpLims\Exceptions\InvalidInputDataException;
use TobiasMaxham\PhpLims\Exceptions\UnprocessableEntityException;

class Client
{
    private string $apiEndpoint = 'https://wissensnetz.dosb.de/api/lims';

    private string $testApiEndpoint = 'https://demo-dosb.wissensnetz.eu/api/lims';

    private bool $testmode = false;

    private null|int $organizationId = null;

    private \GuzzleHttp\Client $guzzleClient;

    public function __construct(private string $username, private string $password)
    {
        $this->guzzleClient = new \GuzzleHttp\Client([
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

        return $this->request($license);
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
                throw new NotFoundException($message, 403);
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
}