<?php

declare(strict_types=1);

namespace LimsPhp\Lims;

use DateTimeInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use LimsPhp\Lims\Enums\AgeGroup;
use LimsPhp\Lims\Enums\FederalState;
use LimsPhp\Lims\Enums\OrganisationCategory;
use LimsPhp\Lims\Exceptions\AccessDeniedException;
use LimsPhp\Lims\Exceptions\ApiException;
use LimsPhp\Lims\Exceptions\ConnectionException;
use LimsPhp\Lims\Exceptions\InvalidDownloadFormatException;
use LimsPhp\Lims\Exceptions\InvalidInputDataException;
use LimsPhp\Lims\Exceptions\InvalidResponseException;
use LimsPhp\Lims\Exceptions\LimsException;
use LimsPhp\Lims\Exceptions\NotAcceptableException;
use LimsPhp\Lims\Exceptions\NotFoundException;
use LimsPhp\Lims\Exceptions\UnauthorizedException;
use LimsPhp\Lims\Exceptions\UnprocessableEntityException;
use LimsPhp\Lims\Http\CurlClient;
use LimsPhp\Lims\Http\CurlHttpFactory;
use LimsPhp\Lims\Models\CreatedLicense;
use LimsPhp\Lims\Models\DeletedLicense;
use LimsPhp\Lims\Models\License;
use LimsPhp\Lims\Models\Migration;
use LimsPhp\Lims\Models\Organisation;
use LimsPhp\Lims\Models\TrainingCourse;
use LimsPhp\Lims\Models\UpdatedLicense;
use LimsPhp\Lims\Support\LimsValidator;

class Client
{
    public const LIVE_ENDPOINT = 'https://wissensnetz.dosb.de/api/lims';

    public const TEST_ENDPOINT = 'https://demo-dosb.wissensnetz.eu/api/lims';

    public const DOWNLOAD_FORMATS = ['dina4', 'card', 'signet'];

    public const DOWNLOAD_MULTIPLE_FORMATS = ['dina4', 'card'];

    /** Max. number of organisations or training courses per request. */
    public const MAX_LOOKUP_LIMIT = 100;

    /** Max. number of licenses per `lookup()` request. */
    public const MAX_LICENSE_LIMIT = 1000;

    /** Max. number of license numbers per `lookup()` request with `idsOnly`. */
    public const MAX_LICENSE_IDS_LIMIT = 20000;

    public int $minYears = 0;

    public int $maxYears = 120;

    private readonly ClientInterface $httpClient;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    private readonly LimsValidator $validator;

    /**
     * @param string $username API username (usually the part of the e-mail address before the "@").
     * @param string $password API password.
     * @param bool $testMode Use the demo environment instead of the live system.
     * @param ClientInterface|null $httpClient A PSR-18 HTTP client. If omitted, a client that is already
     *        installed in the project is used when it can be found via php-http/discovery (Guzzle,
     *        Symfony HttpClient, ...). Otherwise the built-in curl client is used (requires ext-curl).
     * @param RequestFactoryInterface|null $requestFactory A PSR-17 request factory (same detection as above).
     * @param StreamFactoryInterface|null $streamFactory A PSR-17 stream factory (same detection as above).
     * @param int $timeout Timeout in seconds for the built-in curl client (some API calls take more than 20 seconds).
     *        Ignored if a client is passed or discovered.
     */
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private bool $testMode = false,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        int $timeout = 60,
    ) {
        $this->validator = new LimsValidator($this);
        $this->httpClient = $httpClient ?? self::discoverHttpClient($timeout);
        $this->requestFactory = $requestFactory ?? self::discoverRequestFactory();
        $this->streamFactory = $streamFactory ?? self::discoverStreamFactory();
    }

    /**
     * Create a new license.
     *
     * @param array<string, mixed> $license
     *
     * @throws LimsException
     */
    public function create(array $license): CreatedLicense
    {
        if (isset($license['license_number_dosb']) || isset($license['lid'])) {
            throw new InvalidInputDataException('You can not create a license with an existing license number or lid');
        }

        $this->validator->validateRequest($license, 'create');

        return new CreatedLicense($this->post('/request', $license));
    }

    /**
     * Correct the data of an existing license.
     *
     * @param array<string, mixed> $license
     * @param int|string|null $id LiMS id (`lid`, numeric) or DOSB license number.
     *
     * @throws LimsException
     */
    public function correct(array $license, int|string|null $id = null): UpdatedLicense
    {
        if (isset($license['organisation_id'])) {
            throw new InvalidInputDataException('You can not correct an `organisation_id`');
        }

        $license = $this->withLicenseId($license, $id);

        $this->validator->validateRequest($license, 'correct');

        return new UpdatedLicense($this->post('/request', $license));
    }

    /**
     * Extend an existing license.
     *
     * @param array<string, mixed> $license
     * @param int|string|null $id LiMS id (`lid`, numeric) or DOSB license number.
     *
     * @throws LimsException
     */
    public function extend(array $license, int|string|null $id = null): UpdatedLicense
    {
        foreach (['organisation_id', 'first_issue_date', 'training_course_id'] as $field) {
            if (isset($license[$field])) {
                throw new InvalidInputDataException("You can not change the `{$field}` when extending a license");
            }
        }

        $license = $this->withLicenseId($license, $id);

        $this->validator->validateRequest($license, 'extend');

        return new UpdatedLicense($this->post('/request', $license));
    }

    /**
     * Load all fields of a license.
     *
     * @throws LimsException
     */
    public function load(string $licenseNumberDosb): License
    {
        return new License($this->post('/load/'.rawurlencode($licenseNumberDosb)));
    }

    /**
     * Look up licenses.
     *
     * @return License[]|string[] Licenses, or only the DOSB license numbers if `$idsOnly` is true.
     *
     * @throws LimsException
     */
    public function lookup(
        ?int $organisationId = null,
        ?int $offset = null,
        ?int $limit = null,
        ?bool $validationStatus = null,
        ?bool $isObscured = null,
        ?bool $idsOnly = null,
        ?string $licenseNumberOrganisation = null,
    ): array {
        $this->assertLimit($limit, $idsOnly ? self::MAX_LICENSE_IDS_LIMIT : self::MAX_LICENSE_LIMIT);

        $data = $this->filterNull([
            'organisation_id'             => $organisationId,
            'offset'                      => $offset,
            'limit'                       => $limit,
            'validation_status'           => $validationStatus,
            'is_obscured'                 => $isObscured,
            'ids_only'                    => $idsOnly,
            'license_number_organisation' => $licenseNumberOrganisation,
        ]);

        $licenses = $this->items($this->post('/lookup', $data), 'licenses');

        if ($idsOnly) {
            return $licenses;
        }

        return array_map(static fn (array $license) => new License($license), $licenses);
    }

    /**
     * Look up organisations (max. 100 per request).
     *
     * @return Organisation[]
     *
     * @throws LimsException
     */
    public function lookupOrganisations(
        ?int $offset = null,
        ?int $limit = null,
        FederalState|string|null $federalState = null,
        ?int $organisationParentId = null,
        OrganisationCategory|string|null $organisationCategory = null,
    ): array {
        $this->assertLimit($limit, self::MAX_LOOKUP_LIMIT);

        $result = $this->post('/lookup_organisations', $this->filterNull([
            'offset'                 => $offset,
            'limit'                  => $limit,
            'federal_state'          => $federalState instanceof FederalState ? $federalState->value : $federalState,
            'organisation_parent_id' => $organisationParentId,
            'organisation_category'  => $organisationCategory instanceof OrganisationCategory ? $organisationCategory->value : $organisationCategory,
        ]));

        return array_map(
            static fn (array $organisation) => new Organisation($organisation),
            $this->items($result, 'organisations'),
        );
    }

    /**
     * Look up all organisations matching the filters. Pages through the results automatically.
     *
     * @return Organisation[]
     *
     * @throws LimsException
     */
    public function lookupAllOrganisations(
        FederalState|string|null $federalState = null,
        ?int $organisationParentId = null,
        OrganisationCategory|string|null $organisationCategory = null,
    ): array {
        return $this->paginate(fn (int $offset, int $limit) => $this->lookupOrganisations(
            $offset,
            $limit,
            $federalState,
            $organisationParentId,
            $organisationCategory,
        ));
    }

    /**
     * Look up DOSB training courses (max. 100 per request).
     *
     * @param string|null $trainingCourse e.g. "T-C/B" or "ÜL-B"
     *
     * @return TrainingCourse[]
     *
     * @throws LimsException
     */
    public function lookupTrainingCourses(
        ?int $offset = null,
        ?int $limit = null,
        FederalState|string|null $federalState = null,
        ?int $assignedOrganisationId = null,
        ?int $level = null,
        ?string $trainingCourse = null,
        ?string $sport = null,
    ): array {
        $this->assertLimit($limit, self::MAX_LOOKUP_LIMIT);

        $result = $this->post('/lookup_training_courses', $this->filterNull([
            'offset'                   => $offset,
            'limit'                    => $limit,
            'federal_state'            => $federalState instanceof FederalState ? $federalState->value : $federalState,
            'assigned_organisation_id' => $assignedOrganisationId,
            'level'                    => $level,
            'training_course'          => $trainingCourse,
            'sport'                    => $sport,
        ]));

        return array_map(
            static fn (array $trainingCourse) => new TrainingCourse($trainingCourse),
            $this->items($result, 'data'),
        );
    }

    /**
     * Look up all DOSB training courses matching the filters. Pages through the results automatically.
     *
     * @return TrainingCourse[]
     *
     * @throws LimsException
     */
    public function lookupAllTrainingCourses(
        FederalState|string|null $federalState = null,
        ?int $assignedOrganisationId = null,
        ?int $level = null,
        ?string $trainingCourse = null,
        ?string $sport = null,
    ): array {
        return $this->paginate(fn (int $offset, int $limit) => $this->lookupTrainingCourses(
            $offset,
            $limit,
            $federalState,
            $assignedOrganisationId,
            $level,
            $trainingCourse,
            $sport,
        ));
    }

    /**
     * Statistics of DOSB training courses for a period.
     *
     * Dates are sent as unix timestamps, like all date fields of the LiMS API.
     *
     * @param string|null $postal First (max. three) digits of the postal code area.
     * @param string|null $gender `m`, `w` or `u`.
     * @param int|null $trainingCourseId Should be used alone, combined with other detail filters the result may be 0.
     *
     * @return array<string, mixed> Statistics grouped by training course, e.g. ['T-B/L' => ['new' => ['m' => 30, ...], ...]]
     *
     * @throws LimsException
     */
    public function stats(
        DateTimeInterface|int $startDate,
        DateTimeInterface|int $endDate,
        AgeGroup|int|null $birthdate = null,
        ?string $postal = null,
        ?string $gender = null,
        ?int $trainingCourseId = null,
        ?string $trainingCourse = null,
        ?int $level = null,
        ?string $sport = null,
        ?string $profile = null,
        ?string $focus = null,
        ?string $discipline = null,
        FederalState|string|null $federalState = null,
        ?int $organisationId = null,
    ): array {
        $start = $startDate instanceof DateTimeInterface ? $startDate->getTimestamp() : $startDate;
        $end = $endDate instanceof DateTimeInterface ? $endDate->getTimestamp() : $endDate;
        $ageGroup = $birthdate instanceof AgeGroup ? $birthdate->value : $birthdate;

        if ($start > $end) {
            throw new InvalidInputDataException('The start date must be before the end date');
        }
        if ($ageGroup !== null && AgeGroup::tryFrom($ageGroup) === null) {
            throw new InvalidInputDataException('The age group (`birthdate`) must be between 1 and 5');
        }
        if ($postal !== null && ($postal === '' || strlen($postal) > 3)) {
            throw new InvalidInputDataException('The postal code area must have one to three characters');
        }
        if ($gender !== null && ! in_array($gender, ['m', 'w', 'u'], true)) {
            throw new InvalidInputDataException('Gender must be either "m", "w" or "u".');
        }

        $result = $this->send('/stats', $this->filterNull([
            'start_date'         => $start,
            'end_date'           => $end,
            'birthdate'          => $ageGroup,
            'postal'             => $postal,
            'gender'             => $gender,
            'training_course_id' => $trainingCourseId,
            'training_course'    => $trainingCourse,
            'level'              => $level,
            'sport'              => $sport,
            'profile'            => $profile,
            'focus'              => $focus,
            'discipline'         => $discipline,
            'federal_state'      => $federalState instanceof FederalState ? $federalState->value : $federalState,
            'organisation_id'    => $organisationId,
        ]));

        $body = (string) $result->getBody();
        $decoded = json_decode($body, true);

        // An empty result is valid (no licenses in the period), so `[]` is accepted here.
        if (! is_array($decoded)) {
            throw new InvalidResponseException('Invalid response from LiMS API', $result->getStatusCode(), $body);
        }

        return $decoded;
    }

    /**
     * Delete a license.
     *
     * @throws LimsException
     */
    public function delete(string $licenseNumberDosb): DeletedLicense
    {
        return new DeletedLicense($this->post('/delete/'.rawurlencode($licenseNumberDosb)));
    }

    /**
     * Load the license print template. `dina4` and `card` are PDF files, `signet` is a JPG.
     *
     * @return string The raw file content, or the download URL if `$urlOnly` is true.
     *
     * @throws LimsException
     */
    public function download(string $licenseNumberDosb, string $format = 'dina4', bool $urlOnly = false): string
    {
        $this->assertDownloadFormat($format, self::DOWNLOAD_FORMATS);

        return $this->fetchDownload('/download/'.rawurlencode($licenseNumberDosb), ['format' => $format], $urlOnly);
    }

    /**
     * Load an aggregated print template (PDF) for multiple licenses.
     *
     * @param string[] $licenseNumbersDosb
     *
     * @return string The raw file content, or the download URL if `$urlOnly` is true.
     *
     * @throws LimsException
     */
    public function downloadMultiple(array $licenseNumbersDosb, string $format = 'dina4', bool $urlOnly = false): string
    {
        $this->assertDownloadFormat($format, self::DOWNLOAD_MULTIPLE_FORMATS);

        if ($licenseNumbersDosb === []) {
            throw new InvalidInputDataException('You must provide at least one license number');
        }

        return $this->fetchDownload('/download_multiple', [
            'license_numbers_dosb' => implode(',', $licenseNumbersDosb),
            'format'               => $format,
        ], $urlOnly);
    }

    /**
     * Request a license from another organisation (only within the same umbrella organisation).
     *
     * The request is sent by the target organisation (`$organisationId`), i.e. the organisation that wants
     * to take over the license. The current organisation of the license then accepts or declines it with
     * `migrationAccept()` / `migrationDecline()`. With sufficient permissions the license is moved directly.
     *
     * @param int $organisationId The target organisation.
     *
     * @return string The migration status message of the API, e.g. 'Ihre Anfrage wurde weitergeleitet.'
     *
     * @throws LimsException
     */
    public function migrationRequest(string $licenseNumberDosb, int $organisationId): string
    {
        return $this->migrationStatus($this->post('/migration_request', [
            'license_number_dosb' => $licenseNumberDosb,
            'organisation_id'     => $organisationId,
        ]));
    }

    /**
     * Get the open migration requests for licenses of an organisation (the current organisation of the licenses).
     *
     * @return Migration[]
     *
     * @throws NotFoundException If there are no migration requests.
     * @throws LimsException
     */
    public function migrationGet(int $organisationId, ?int $offset = null, ?int $limit = null): array
    {
        $result = $this->post('/migration_get/'.$organisationId, $this->filterNull([
            'offset' => $offset,
            'limit'  => $limit,
        ]));

        // The API returns the migrations as an object keyed by migration id.
        return array_map(
            static fn (array $migration) => new Migration($migration),
            $this->items($result, 'migrations'),
        );
    }

    /**
     * Decline a migration request. Called by the current organisation of the license.
     *
     * @return string The migration status message of the API.
     *
     * @throws LimsException
     */
    public function migrationDecline(int $migrationId): string
    {
        return $this->migrationStatus($this->post('/migration_decline/'.$migrationId));
    }

    /**
     * Accept a migration request and move the license to the requesting organisation.
     * Called by the current organisation of the license.
     *
     * @return string The migration status message of the API.
     *
     * @throws LimsException
     */
    public function migrationAccept(int $migrationId): string
    {
        return $this->migrationStatus($this->post('/migration_accept/'.$migrationId));
    }

    public function setTestMode(bool $testMode = true): void
    {
        $this->testMode = $testMode;
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function endpoint(): string
    {
        return $this->testMode ? self::TEST_ENDPOINT : self::LIVE_ENDPOINT;
    }

    public function setMinYears(int $years): void
    {
        $this->minYears = $years;
    }

    public function setMaxYears(int $years): void
    {
        $this->maxYears = $years;
    }

    /**
     * @param array<string, mixed> $license
     *
     * @return array<string, mixed>
     */
    private function withLicenseId(array $license, int|string|null $id): array
    {
        if ($id !== null && $id !== '') {
            $license[is_numeric($id) ? 'lid' : 'license_number_dosb'] = is_numeric($id) ? (int) $id : $id;
        }

        if (! isset($license['license_number_dosb']) && ! isset($license['lid'])) {
            throw new InvalidInputDataException('You must provide a license number or lid');
        }

        return $license;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function filterNull(array $data): array
    {
        return array_filter($data, static fn ($value) => $value !== null);
    }

    private function assertLimit(?int $limit, int $max): void
    {
        if ($limit !== null && ($limit < 1 || $limit > $max)) {
            throw new InvalidInputDataException("The limit must be between 1 and {$max}");
        }
    }

    /**
     * Extract the list of items from a paginated response (`size`, `offset`, `total`, `<key>`).
     *
     * @param array<string, mixed> $result
     *
     * @return array<int, mixed>
     */
    private function items(array $result, string $key): array
    {
        if (! array_key_exists($key, $result) || ! is_array($result[$key])) {
            throw new InvalidResponseException("Invalid response from LiMS API: missing `{$key}`", 200);
        }

        return array_values($result[$key]);
    }

    /**
     * Call a lookup page by page until no more items are returned.
     *
     * @template T
     *
     * @param callable(int $offset, int $limit): T[] $fetchPage
     *
     * @return T[]
     */
    private function paginate(callable $fetchPage): array
    {
        $items = [];
        $offset = 0;

        // Safety net against endless loops if the API ignores the offset.
        for ($page = 0; $page < 1000; $page++) {
            $batch = $fetchPage($offset, self::MAX_LOOKUP_LIMIT);

            if ($batch === []) {
                break;
            }

            // Continue until an empty page is returned. The API may return fewer items than
            // requested even if there are more, so a short page is not treated as the last one.
            array_push($items, ...$batch);
            $offset += count($batch);
        }

        return $items;
    }

    /**
     * @param string[] $allowed
     */
    private function assertDownloadFormat(string $format, array $allowed): void
    {
        if (! in_array($format, $allowed, true)) {
            throw new InvalidDownloadFormatException(
                "Invalid download format: {$format} (allowed: ".implode(', ', $allowed).')',
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fetchDownload(string $path, array $data, bool $urlOnly): string
    {
        if ($urlOnly) {
            // The API only accepts the string "1". With the integer 1 or true it silently returns the file.
            $data['file_url_only'] = '1';
        }

        $response = $this->send($path, $data);
        $body = (string) $response->getBody();

        if (! $urlOnly) {
            if ($body === '') {
                throw new InvalidResponseException('Empty download response from LiMS API', $response->getStatusCode());
            }

            return $body;
        }

        $download = json_decode($body, true);

        if (! is_array($download) || empty($download['download_url']) || ! is_string($download['download_url'])) {
            throw new InvalidResponseException('Could not generate download URL', $response->getStatusCode(), $body);
        }

        return $download['download_url'];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function migrationStatus(array $result): string
    {
        if (! isset($result['migration_status']) || ! is_string($result['migration_status'])) {
            throw new InvalidResponseException('Invalid response from LiMS API: missing `migration_status`', 200);
        }

        return $result['migration_status'];
    }

    /**
     * Send a POST request and decode the JSON response.
     *
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>
     */
    private function post(string $path, ?array $data = null): array
    {
        $response = $this->send($path, $data);
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (! is_array($decoded) || $decoded === []) {
            throw new InvalidResponseException('Invalid response from LiMS API', $response->getStatusCode(), $body);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function send(string $path, ?array $data = null): ResponseInterface
    {
        $request = $this->requestFactory
            ->createRequest('POST', $this->endpoint().$path)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Authorization', 'Basic '.base64_encode($this->username.':'.$this->password));

        if ($data !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream((string) json_encode($data, JSON_THROW_ON_ERROR)));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            // PSR-18 clients only throw on network errors, not on HTTP error status codes.
            throw new ConnectionException('Request to the LiMS API failed: '.$e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() >= 400) {
            throw $this->errorFromResponse($response);
        }

        return $response;
    }

    private function errorFromResponse(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        // The API returns errors as a JSON array of messages, e.g. ["License not found"].
        // Some endpoints (e.g. download_multiple) put another JSON object into that array,
        // e.g. ["{\"ABC-1\":\"License could not be found - ABC-1\"}"].
        $json = json_decode($body, true);
        $message = is_array($json) && $json !== [] ? implode(' | ', self::errorMessages($json)) : $body;

        if ($message === '') {
            $message = "LiMS API returned an error (HTTP {$status})";
        }

        return match ($status) {
            401 => new UnauthorizedException($message, $status, $body),
            403 => new AccessDeniedException($message, $status, $body),
            404 => new NotFoundException($message, $status, $body),
            406 => new NotAcceptableException($message, $status, $body),
            422 => new UnprocessableEntityException($message, $status, $body),
            default => new ApiException($message, $status, $body),
        };
    }

    /**
     * Flatten the error messages of the API into a list of strings.
     *
     * @param array<int|string, mixed> $errors
     *
     * @return string[]
     */
    private static function errorMessages(array $errors): array
    {
        $messages = [];

        foreach ($errors as $key => $error) {
            if (is_string($error) && str_starts_with(ltrim($error), '{')) {
                $nested = json_decode($error, true);
                if (is_array($nested)) {
                    $error = $nested;
                }
            }

            if (is_array($error)) {
                array_push($messages, ...self::errorMessages($error));
                continue;
            }

            $text = is_scalar($error) || $error === null ? (string) $error : (string) json_encode($error);

            // Keys of objects are license numbers or field names. They are only added if the message does not contain them.
            $messages[] = is_string($key) && ! str_contains($text, $key) ? "{$key}: {$text}" : $text;
        }

        return $messages;
    }

    private static function discoverHttpClient(int $timeout): ClientInterface
    {
        if (class_exists(Psr18ClientDiscovery::class)) {
            try {
                return Psr18ClientDiscovery::find();
            } catch (Throwable) {
                // No PSR-18 client found, fall back to the built-in curl client.
            }
        }

        return new CurlClient($timeout);
    }

    private static function discoverRequestFactory(): RequestFactoryInterface
    {
        if (class_exists(Psr17FactoryDiscovery::class)) {
            try {
                return Psr17FactoryDiscovery::findRequestFactory();
            } catch (Throwable) {
                // No PSR-17 factory found, fall back to the built-in factory.
            }
        }

        return new CurlHttpFactory();
    }

    private static function discoverStreamFactory(): StreamFactoryInterface
    {
        if (class_exists(Psr17FactoryDiscovery::class)) {
            try {
                return Psr17FactoryDiscovery::findStreamFactory();
            } catch (Throwable) {
                // No PSR-17 factory found, fall back to the built-in factory.
            }
        }

        return new CurlHttpFactory();
    }
}
