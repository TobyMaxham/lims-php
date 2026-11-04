<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Tests;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use LimsPhp\Lims\Client;
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
use LimsPhp\Lims\Http\CurlHttpFactory;
use LimsPhp\Lims\Http\Psr7\Response;
use LimsPhp\Lims\Models\CreatedLicense;
use LimsPhp\Lims\Models\DeletedLicense;
use LimsPhp\Lims\Models\License;
use LimsPhp\Lims\Models\Migration;
use LimsPhp\Lims\Models\Organisation;
use LimsPhp\Lims\Models\TrainingCourse;
use LimsPhp\Lims\Models\UpdatedLicense;
use LimsPhp\Lims\Tests\Http\FakeHttpClient;

final class ClientTest extends TestCase
{
    private FakeHttpClient $http;

    /**
     * @param ResponseInterface|Closure(RequestInterface): ResponseInterface $response
     */
    private function client(ResponseInterface|Closure $response, bool $testMode = false): Client
    {
        $this->http = new FakeHttpClient($response);
        $factory = new CurlHttpFactory();

        return new Client('user', 'secret', $testMode, $this->http, $factory, $factory);
    }

    private static function json(mixed $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], (string) json_encode($data));
    }

    /**
     * @return array<string, mixed>
     */
    private static function validLicense(): array
    {
        return [
            'organisation_id'    => 999,
            'firstname'          => 'Max',
            'lastname'           => 'Mustermann',
            'birthdate'          => strtotime('1980-01-01 12:00'),
            'gender'             => 'm',
            'street'             => 'Waldstraße 1',
            'postal'             => '10115',
            'city'               => 'Berlin',
            'issue_date'         => strtotime('2024-01-01 12:00'),
            'training_course_id' => 123,
            'issue_place'        => 'Berlin',
            'honor_code'         => true,
            'first_aid'          => false,
        ];
    }

    // ---------------------------------------------------------------------
    // Request basics
    // ---------------------------------------------------------------------

    public function test_it_sends_basic_auth_and_json_headers(): void
    {
        $this->client(self::json(['lid' => 1, 'license_number_dosb' => 'X']))->load('X');

        $request = $this->http->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('Basic '.base64_encode('user:secret'), $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function test_it_uses_the_live_endpoint_by_default(): void
    {
        $client = $this->client(self::json(['lid' => 1]));
        $client->load('TEST-T-A-0000001');

        $this->assertFalse($client->isTestMode());
        $this->assertSame(
            'https://wissensnetz.dosb.de/api/lims/load/TEST-T-A-0000001',
            (string) $this->http->lastRequest()->getUri(),
        );
    }

    public function test_it_uses_the_demo_endpoint_in_test_mode(): void
    {
        $client = $this->client(self::json(['lid' => 1]), testMode: true);
        $client->load('X');
        $this->assertStringStartsWith('https://demo-dosb.wissensnetz.eu/api/lims/', (string) $this->http->lastRequest()->getUri());

        $client->setTestMode(false);
        $client->load('X');
        $this->assertStringStartsWith('https://wissensnetz.dosb.de/api/lims/', (string) $this->http->lastRequest()->getUri());
    }

    public function test_it_works_with_third_party_psr17_factories(): void
    {
        $this->http = new FakeHttpClient(self::json(['licenses' => []]));
        $factory = new Psr17Factory();
        $client = new Client('user', 'secret', httpClient: $this->http, requestFactory: $factory, streamFactory: $factory);

        $this->assertSame([], $client->lookup(organisationId: 1));
        $this->assertSame(['organisation_id' => 1], $this->http->lastJsonBody());
    }

    public function test_it_works_without_any_injected_dependency(): void
    {
        $client = new Client('user', 'secret');

        $this->assertSame(Client::LIVE_ENDPOINT, $client->endpoint());
    }

    // ---------------------------------------------------------------------
    // create / correct / extend
    // ---------------------------------------------------------------------

    public function test_create_sends_the_license_and_returns_the_ids(): void
    {
        $license = $this->client(self::json(['lid' => '1000001', 'license_number_dosb' => 'TEST-T-A-0000001']))
            ->create(self::validLicense());

        $this->assertInstanceOf(CreatedLicense::class, $license);
        $this->assertSame(1000001, $license->lid);
        $this->assertSame('TEST-T-A-0000001', $license->license_number_dosb);

        $this->assertStringEndsWith('/request', (string) $this->http->lastRequest()->getUri());
        $this->assertSame('application/json', $this->http->lastRequest()->getHeaderLine('Content-Type'));

        $body = $this->http->lastJsonBody();
        $this->assertSame('Max', $body['firstname']);
        $this->assertSame(1, $body['honor_code'], 'booleans are converted to 0/1');
        $this->assertSame(0, $body['first_aid']);
    }

    public function test_create_rejects_existing_ids(): void
    {
        $client = $this->client(self::json([]));

        $this->expectException(InvalidInputDataException::class);
        $client->create(self::validLicense() + ['lid' => 1]);
    }

    public function test_create_validates_before_sending(): void
    {
        $client = $this->client(self::json([]));
        $license = self::validLicense();
        unset($license['lastname']);

        try {
            $client->create($license);
            $this->fail('Expected InvalidInputDataException');
        } catch (InvalidInputDataException $e) {
            $this->assertStringContainsString('lastname', $e->getMessage());
        }

        $this->assertSame([], $this->http->requests, 'no request is sent for invalid data');
    }

    public function test_correct_uses_the_license_number_or_lid(): void
    {
        $client = $this->client(self::json(['lid' => 5, 'license_number_dosb' => 'ABC-1']));

        $license = $client->correct(['firstname' => 'Erika'], 'ABC-1');
        $this->assertInstanceOf(UpdatedLicense::class, $license);
        $this->assertSame(['firstname' => 'Erika', 'license_number_dosb' => 'ABC-1'], $this->http->lastJsonBody());

        $client->correct(['firstname' => 'Erika'], 5);
        $this->assertSame(['firstname' => 'Erika', 'lid' => 5], $this->http->lastJsonBody());

        $client->correct(['firstname' => 'Erika'], '5');
        $this->assertSame(['firstname' => 'Erika', 'lid' => 5], $this->http->lastJsonBody());
    }

    public function test_correct_requires_an_id(): void
    {
        $this->expectException(InvalidInputDataException::class);
        $this->client(self::json([]))->correct(['firstname' => 'Erika']);
    }

    public function test_correct_rejects_organisation_id(): void
    {
        $this->expectException(InvalidInputDataException::class);
        $this->client(self::json([]))->correct(['organisation_id' => 1], 'ABC-1');
    }

    #[DataProvider('forbiddenExtendFields')]
    public function test_extend_rejects_fields_that_can_not_be_changed(string $field): void
    {
        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage($field);
        $this->client(self::json([]))->extend([$field => 1], 'ABC-1');
    }

    public static function forbiddenExtendFields(): array
    {
        return [['organisation_id'], ['first_issue_date'], ['training_course_id']];
    }

    public function test_extend_sends_the_data(): void
    {
        $license = $this->client(self::json(['lid' => 5, 'license_number_dosb' => 'ABC-1']))
            ->extend(['issue_date' => strtotime('2025-01-01 12:00')], 'ABC-1');

        $this->assertSame('ABC-1', $license->license_number_dosb);
        $this->assertSame('ABC-1', $this->http->lastJsonBody()['license_number_dosb']);
    }

    // ---------------------------------------------------------------------
    // load / lookup / delete
    // ---------------------------------------------------------------------

    public function test_load_returns_a_license_with_casted_attributes(): void
    {
        $license = $this->client(self::json([
            'lid'                 => '1000003',
            'license_number_dosb' => 'TEST-T-C-0000003',
            'firstname'           => 'Max',
            'honor_code'          => '1',
            'first_aid'           => '0',
            'birthdate'           => '315576000',
        ]))->load('TEST-T-C-0000003');

        $this->assertInstanceOf(License::class, $license);
        $this->assertSame(1000003, $license->lid);
        $this->assertTrue($license->honor_code);
        $this->assertFalse($license->first_aid);
        $this->assertSame(315576000, $license->birthdate);
        $this->assertSame('', (string) $this->http->lastRequest()->getBody(), 'load sends no body');
    }

    public function test_load_encodes_the_license_number(): void
    {
        $this->client(self::json(['lid' => 1]))->load('A B/C');

        $this->assertStringEndsWith('/load/A%20B%2FC', (string) $this->http->lastRequest()->getUri());
    }

    public function test_lookup_sends_only_given_parameters(): void
    {
        $this->client(self::json(['licenses' => []]))->lookup(
            organisationId: 9004,
            offset: 0,
            limit: 2,
            validationStatus: true,
            isObscured: false,
            licenseNumberOrganisation: 'V-123',
        );

        $this->assertSame([
            'organisation_id'             => 9004,
            'offset'                      => 0,
            'limit'                       => 2,
            'validation_status'           => true,
            'is_obscured'                 => false,
            'license_number_organisation' => 'V-123',
        ], $this->http->lastJsonBody());
    }

    public function test_lookup_returns_licenses(): void
    {
        $licenses = $this->client(self::json([
            'size'     => 2,
            'offset'   => 0,
            'total'    => 2,
            'licenses' => [
                ['lid' => '1', 'license_number_dosb' => 'A'],
                ['lid' => '2', 'license_number_dosb' => 'B'],
            ],
        ]))->lookup();

        $this->assertCount(2, $licenses);
        $this->assertContainsOnlyInstancesOf(License::class, $licenses);
        $this->assertSame(2, $licenses[1]->lid);
    }

    public function test_lookup_returns_only_ids(): void
    {
        $ids = $this->client(self::json(['licenses' => ['A', 'B']]))->lookup(idsOnly: true);

        $this->assertSame(['A', 'B'], $ids);
    }

    public function test_lookup_throws_on_unexpected_response(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->client(self::json(['foo' => 'bar']))->lookup();
    }

    public function test_delete_returns_the_deleted_ids(): void
    {
        $deleted = $this->client(self::json(['lid' => '7', 'license_number_dosb' => 'ABC-1']))->delete('ABC-1');

        $this->assertInstanceOf(DeletedLicense::class, $deleted);
        $this->assertSame(7, $deleted->lid);
        $this->assertStringEndsWith('/delete/ABC-1', (string) $this->http->lastRequest()->getUri());
    }

    // ---------------------------------------------------------------------
    // download
    // ---------------------------------------------------------------------

    public function test_download_returns_the_raw_file(): void
    {
        $file = $this->client(new Response(200, ['Content-Type' => 'application/pdf'], '%PDF-1.4 ...'))
            ->download('ABC-1', 'card');

        $this->assertSame('%PDF-1.4 ...', $file);
        $this->assertStringEndsWith('/download/ABC-1', (string) $this->http->lastRequest()->getUri());
        $this->assertSame(['format' => 'card'], $this->http->lastJsonBody());
    }

    public function test_download_can_return_only_the_url(): void
    {
        $url = $this->client(self::json(['download_url' => 'https://example.org/license.pdf']))
            ->download('ABC-1', urlOnly: true);

        $this->assertSame('https://example.org/license.pdf', $url);
        $this->assertSame(['format' => 'dina4', 'file_url_only' => '1'], $this->http->lastJsonBody(), 'the API only accepts the string "1"');
    }

    public function test_download_multiple_can_return_only_the_url(): void
    {
        $url = $this->client(self::json(['download_url' => 'https://example.org/Lizenzen-dina4.pdf']))
            ->downloadMultiple(['A', 'B'], urlOnly: true);

        $this->assertSame('https://example.org/Lizenzen-dina4.pdf', $url);
        $this->assertSame('1', $this->http->lastJsonBody()['file_url_only']);
    }

    public function test_download_throws_without_url(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->client(self::json(['foo' => 'bar']))->download('ABC-1', urlOnly: true);
    }

    public function test_download_rejects_unknown_formats(): void
    {
        $this->expectException(InvalidDownloadFormatException::class);
        $this->client(self::json([]))->download('ABC-1', 'pdf');
    }

    public function test_download_multiple_sends_a_comma_separated_list(): void
    {
        $file = $this->client(new Response(200, [], '%PDF'))->downloadMultiple(['A', 'B', 'C']);

        $this->assertSame('%PDF', $file);
        $this->assertStringEndsWith('/download_multiple', (string) $this->http->lastRequest()->getUri());
        $this->assertSame(['license_numbers_dosb' => 'A,B,C', 'format' => 'dina4'], $this->http->lastJsonBody());
    }

    public function test_download_multiple_does_not_support_signet(): void
    {
        $this->expectException(InvalidDownloadFormatException::class);
        $this->client(self::json([]))->downloadMultiple(['A'], 'signet');
    }

    public function test_download_multiple_requires_license_numbers(): void
    {
        $this->expectException(InvalidInputDataException::class);
        $this->client(self::json([]))->downloadMultiple([]);
    }

    // ---------------------------------------------------------------------
    // migrations
    // ---------------------------------------------------------------------

    public function test_migration_request(): void
    {
        $status = $this->client(self::json(['migration_status' => 'Ihre Anfrage wurde weitergeleitet.']))
            ->migrationRequest('TEST-T-C-0000003', 9001);

        $this->assertSame('Ihre Anfrage wurde weitergeleitet.', $status);
        $this->assertStringEndsWith('/migration_request', (string) $this->http->lastRequest()->getUri());
        $this->assertSame(['license_number_dosb' => 'TEST-T-C-0000003', 'organisation_id' => 9001], $this->http->lastJsonBody());
    }

    public function test_migration_get(): void
    {
        // Response format of the demo system (fictional data): migrations keyed by migration id, values as strings.
        $migrations = $this->client(self::json([
            'size'       => 1,
            'offset'     => 0,
            'total'      => 1,
            'migrations' => [
                '1000006' => ['migration_id' => '1000006', 'license_number_dosb' => 'TEST-T-C-0000006', 'organisation_id' => '9003'],
            ],
        ]))->migrationGet(9002, limit: 10);

        $this->assertStringEndsWith('/migration_get/9002', (string) $this->http->lastRequest()->getUri());
        $this->assertSame(['limit' => 10], $this->http->lastJsonBody());

        $this->assertCount(1, $migrations);
        $this->assertInstanceOf(Migration::class, $migrations[0]);
        $this->assertSame(1000006, $migrations[0]->migration_id);
        $this->assertSame('TEST-T-C-0000006', $migrations[0]->license_number_dosb);
        $this->assertSame(9003, $migrations[0]->organisation_id);
    }

    public function test_migration_get_throws_not_found_without_requests(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No migration requests available!');
        $this->client(self::json(['No migration requests available!'], 404))->migrationGet(9001);
    }

    public function test_migration_accept_and_decline(): void
    {
        $client = $this->client(self::json(['migration_status' => 'ok']));

        $this->assertSame('ok', $client->migrationAccept(42));
        $this->assertStringEndsWith('/migration_accept/42', (string) $this->http->lastRequest()->getUri());

        $this->assertSame('ok', $client->migrationDecline(43));
        $this->assertStringEndsWith('/migration_decline/43', (string) $this->http->lastRequest()->getUri());
    }

    // ---------------------------------------------------------------------
    // organisations, training courses, stats
    // ---------------------------------------------------------------------

    public function test_lookup_organisations(): void
    {
        $organisations = $this->client(self::json([
            'size'          => 2,
            'offset'        => 0,
            'total'         => 2,
            'organisations' => [
                ['organisation_id' => '9001', 'name' => 'Musterverband Nord e.V.', 'parent_id' => null],
                ['organisation_id' => '9005', 'name' => 'Musterverband Süd e.V.', 'parent_id' => '9000'],
            ],
        ]))->lookupOrganisations(
            offset: 0,
            limit: 100,
            federalState: FederalState::Bayern,
            organisationParentId: 9000,
            organisationCategory: OrganisationCategory::Untergliederung,
        );

        $this->assertStringEndsWith('/lookup_organisations', (string) $this->http->lastRequest()->getUri());
        $this->assertSame([
            'offset'                 => 0,
            'limit'                  => 100,
            'federal_state'          => 'Bayern',
            'organisation_parent_id' => 9000,
            'organisation_category'  => 'UG',
        ], $this->http->lastJsonBody());

        $this->assertContainsOnlyInstancesOf(Organisation::class, $organisations);
        $this->assertSame(9001, $organisations[0]->organisation_id);
        $this->assertNull($organisations[0]->parent_id);
        $this->assertSame(9000, $organisations[1]->parent_id);
        $this->assertSame('Musterverband Süd e.V.', $organisations[1]->name);
    }

    public function test_lookup_organisations_accepts_strings_for_enums(): void
    {
        $this->client(self::json(['organisations' => []]))
            ->lookupOrganisations(federalState: 'Thüringen', organisationCategory: 'LSB');

        $this->assertSame(['federal_state' => 'Thüringen', 'organisation_category' => 'LSB'], $this->http->lastJsonBody());
    }

    public function test_lookup_organisations_rejects_a_limit_above_100(): void
    {
        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('between 1 and 100');
        $this->client(self::json([]))->lookupOrganisations(limit: 101);
    }

    public function test_lookup_all_organisations_pages_through_all_results(): void
    {
        $pages = [
            0   => array_map(static fn ($i) => ['organisation_id' => $i, 'name' => "Org {$i}"], range(1, 100)),
            100 => array_map(static fn ($i) => ['organisation_id' => $i, 'name' => "Org {$i}"], range(101, 150)),
            150 => [],
        ];

        $organisations = $this->client(static function (RequestInterface $request) use ($pages): ResponseInterface {
            $body = json_decode((string) $request->getBody(), true);

            return self::json(['size' => count($pages[$body['offset']]), 'total' => 150, 'organisations' => $pages[$body['offset']]]);
        })->lookupAllOrganisations(federalState: FederalState::Hessen);

        $this->assertCount(150, $organisations);
        $this->assertSame(150, $organisations[149]->organisation_id);
        $this->assertCount(3, $this->http->requests);
        $this->assertSame(
            ['offset' => 100, 'limit' => 100, 'federal_state' => 'Hessen'],
            json_decode((string) $this->http->requests[1]->getBody(), true),
        );
    }

    public function test_lookup_training_courses(): void
    {
        $courses = $this->client(self::json([
            'size'   => 1,
            'offset' => 0,
            'total'  => 1,
            'data'   => [[
                'id'              => '4711',
                'title'           => 'Trainer C Breitensport',
                'level'           => '1',
                'course'          => 'T-C/B',
                'sport'           => 'Kanu',
                'profile'         => null,
                'discipline'      => null,
                'focus'           => null,
                'validity_period' => '4',
            ]],
        ]))->lookupTrainingCourses(
            limit: 10,
            federalState: FederalState::NordrheinWestfalen,
            assignedOrganisationId: 9001,
            level: 1,
            trainingCourse: 'T-C/B',
            sport: 'Kanu',
        );

        $this->assertStringEndsWith('/lookup_training_courses', (string) $this->http->lastRequest()->getUri());
        $this->assertSame([
            'limit'                    => 10,
            'federal_state'            => 'Nordrhein-Westfalen',
            'assigned_organisation_id' => 9001,
            'level'                    => 1,
            'training_course'          => 'T-C/B',
            'sport'                    => 'Kanu',
        ], $this->http->lastJsonBody());

        $this->assertInstanceOf(TrainingCourse::class, $courses[0]);
        $this->assertSame('4711', $courses[0]->id);
        $this->assertSame(1, $courses[0]->level);
        $this->assertSame(4, $courses[0]->validity_period);
        $this->assertSame('T-C/B', $courses[0]->course);
    }

    public function test_lookup_all_training_courses_stops_at_an_empty_page(): void
    {
        $courses = $this->client(static function (RequestInterface $request): ResponseInterface {
            $offset = json_decode((string) $request->getBody(), true)['offset'];

            return self::json(['data' => $offset === 0 ? [['id' => '1'], ['id' => '2']] : []]);
        })->lookupAllTrainingCourses(assignedOrganisationId: 9001);

        $this->assertCount(2, $courses);
        $this->assertCount(2, $this->http->requests, 'a short page is not treated as the last page');
    }

    public function test_lookup_training_courses_throws_on_unexpected_response(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('`data`');
        $this->client(self::json(['training_courses' => []]))->lookupTrainingCourses();
    }

    public function test_stats(): void
    {
        $stats = $this->client(self::json([
            'T-B/L' => ['new' => ['m' => 30, 'w' => '30', 'u' => 10]],
        ]))->stats(
            startDate: new DateTimeImmutable('2025-01-01 12:00:00', new DateTimeZone('Europe/Berlin')),
            endDate: 1767182400,
            birthdate: AgeGroup::From27To40,
            postal: '101',
            gender: 'w',
            trainingCourse: 'T-B/L',
            federalState: FederalState::Berlin,
            organisationId: 9001,
        );

        $this->assertStringEndsWith('/stats', (string) $this->http->lastRequest()->getUri());
        $this->assertSame([
            'start_date'      => 1735729200,
            'end_date'        => 1767182400,
            'birthdate'       => 3,
            'postal'          => '101',
            'gender'          => 'w',
            'training_course' => 'T-B/L',
            'federal_state'   => 'Berlin',
            'organisation_id' => 9001,
        ], $this->http->lastJsonBody());
        $this->assertSame(30, $stats['T-B/L']['new']['m']);
    }

    public function test_stats_accepts_an_empty_result(): void
    {
        $this->assertSame([], $this->client(self::json([]))->stats(1, 2));
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('invalidStatsArguments')]
    public function test_stats_validates_the_filters(array $arguments, string $message): void
    {
        $client = $this->client(self::json([]));

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage($message);
        $client->stats(...$arguments);
    }

    public static function invalidStatsArguments(): array
    {
        return [
            'start after end' => [['startDate' => 2, 'endDate' => 1], 'start date'],
            'age group'       => [['startDate' => 1, 'endDate' => 2, 'birthdate' => 6], 'between 1 and 5'],
            'postal too long' => [['startDate' => 1, 'endDate' => 2, 'postal' => '1011'], 'postal'],
            'gender'          => [['startDate' => 1, 'endDate' => 2, 'gender' => 'x'], 'Gender'],
        ];
    }

    public function test_lookup_validates_the_license_limit(): void
    {
        $client = $this->client(self::json(['licenses' => []]));

        $client->lookup(limit: 1000);
        $client->lookup(limit: 20000, idsOnly: true);

        $this->expectException(InvalidInputDataException::class);
        $client->lookup(limit: 1001);
    }

    // ---------------------------------------------------------------------
    // errors
    // ---------------------------------------------------------------------

    /**
     * @param class-string<ApiException> $exception
     */
    #[DataProvider('errorResponses')]
    public function test_it_maps_error_status_codes_to_exceptions(int $status, string $exception): void
    {
        $client = $this->client(self::json(['Access denied for user', 'second message'], $status));

        try {
            $client->load('ABC-1');
            $this->fail('Expected '.$exception);
        } catch (ApiException $e) {
            $this->assertInstanceOf($exception, $e);
            $this->assertInstanceOf(LimsException::class, $e);
            $this->assertSame($status, $e->statusCode);
            $this->assertSame($status, $e->getCode());
            $this->assertSame('Access denied for user | second message', $e->getMessage());
            $this->assertSame('["Access denied for user","second message"]', $e->responseBody);
        }
    }

    public static function errorResponses(): array
    {
        return [
            '400' => [400, ApiException::class],
            '401' => [401, UnauthorizedException::class],
            '403' => [403, AccessDeniedException::class],
            '404' => [404, NotFoundException::class],
            '406' => [406, NotAcceptableException::class],
            '422' => [422, UnprocessableEntityException::class],
            '500' => [500, ApiException::class],
        ];
    }

    public function test_it_unpacks_nested_json_error_messages(): void
    {
        // Response format of the demo system for /download_multiple (fictional data).
        $body = '["{\"TEST-T-B-0000004\":\"License could not be found - TEST-T-B-0000004\",\"TEST-T-C-0000005\":\"License cannot be requested - (TEST-T-C-0000005)\"}"]';
        $client = $this->client(new Response(406, ['Content-Type' => 'application/json'], $body));

        try {
            $client->downloadMultiple(['TEST-T-B-0000004', 'TEST-T-C-0000005']);
            $this->fail('Expected NotAcceptableException');
        } catch (NotAcceptableException $e) {
            $this->assertSame(
                'License could not be found - TEST-T-B-0000004 | License cannot be requested - (TEST-T-C-0000005)',
                $e->getMessage(),
            );
            $this->assertSame($body, $e->responseBody);
        }
    }

    public function test_it_prefixes_object_keys_that_are_not_part_of_the_message(): void
    {
        $client = $this->client(self::json(['firstname' => 'Missing value'], 406));

        $this->expectExceptionMessage('firstname: Missing value');
        $client->load('ABC-1');
    }

    public function test_it_uses_a_default_message_for_empty_error_bodies(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP 503');
        $this->client(new Response(503))->load('ABC-1');
    }

    public function test_it_throws_on_invalid_json(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->client(new Response(200, [], '<html>'))->load('ABC-1');
    }

    public function test_it_wraps_network_errors(): void
    {
        $client = $this->client(static function (RequestInterface $request): ResponseInterface {
            throw new class('Connection refused', $request) extends RuntimeException implements NetworkExceptionInterface {
                public function __construct(string $message, private readonly RequestInterface $request)
                {
                    parent::__construct($message);
                }

                public function getRequest(): RequestInterface
                {
                    return $this->request;
                }
            };
        });

        try {
            $client->load('ABC-1');
            $this->fail('Expected ConnectionException');
        } catch (ConnectionException $e) {
            $this->assertStringContainsString('Connection refused', $e->getMessage());
            $this->assertInstanceOf(NetworkExceptionInterface::class, $e->getPrevious());
        }
    }
}
