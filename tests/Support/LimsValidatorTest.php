<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Tests\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use LimsPhp\Lims\Client;
use LimsPhp\Lims\Exceptions\InvalidInputDataException;
use LimsPhp\Lims\Support\LicenseValidator;
use LimsPhp\Lims\Support\LimsValidator;

final class LimsValidatorTest extends TestCase
{
    private Client $client;

    private LimsValidator $validator;

    protected function setUp(): void
    {
        $this->client = new Client('user', 'secret');
        $this->validator = new LimsValidator($this->client);
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
            'birthdate'          => strtotime('-30 years'),
            'gender'             => 'u',
            'street'             => 'Waldstraße 1',
            'postal'             => '10115',
            'city'               => 'Berlin',
            'issue_date'         => strtotime('2024-01-01 12:00'),
            'training_course_id' => 123,
            'issue_place'        => 'Berlin',
            'honor_code'         => 0,
            'first_aid'          => 1,
        ];
    }

    public function test_a_valid_license_passes(): void
    {
        $license = self::validLicense();
        $this->validator->validateRequest($license, 'create');

        $this->assertSame(0, $license['honor_code']);
        $this->assertSame(1, $license['first_aid']);
    }

    public function test_booleans_are_converted_to_integers(): void
    {
        $license = ['honor_code' => true, 'first_aid' => false];
        $this->validator->validateRequest($license, 'correct');

        $this->assertSame(['honor_code' => 1, 'first_aid' => 0], $license);
    }

    public function test_all_required_fields_are_checked_on_create(): void
    {
        foreach (LicenseValidator::required('create') as $field) {
            $license = self::validLicense();
            unset($license[$field]);

            try {
                $this->validator->validateRequest($license, 'create');
                $this->fail("Missing `{$field}` was not detected");
            } catch (InvalidInputDataException $e) {
                $this->assertStringContainsString($field, $e->getMessage());
            }
        }
    }

    public function test_required_fields_for_create_do_not_contain_ids(): void
    {
        $this->assertNotContains('lid', LicenseValidator::required('create'));
        $this->assertNotContains('license_number_dosb', LicenseValidator::required('create'));
        $this->assertContains('lid', LicenseValidator::required('correct'));
    }

    public function test_partial_data_is_allowed_on_correct(): void
    {
        $license = ['firstname' => 'Erika', 'license_number_dosb' => 'ABC-1'];
        $this->validator->validateRequest($license, 'correct');

        $this->assertSame('Erika', $license['firstname']);
    }

    public function test_empty_values_are_rejected_on_correct(): void
    {
        $this->expectException(InvalidInputDataException::class);
        $license = ['firstname' => '', 'license_number_dosb' => 'ABC-1'];
        $this->validator->validateRequest($license, 'correct');
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidData')]
    public function test_invalid_values_are_rejected(array $data): void
    {
        $this->expectException(InvalidInputDataException::class);
        $this->validator->validateRequest($data, 'correct');
    }

    public static function invalidData(): array
    {
        return [
            'gender'               => [['gender' => 'x']],
            'birthdate string'     => [['birthdate' => '1980-01-01']],
            'birthdate in future'  => [['birthdate' => strtotime('+1 day')]],
            'birthdate too old'    => [['birthdate' => strtotime('-130 years')]],
            'mail'                 => [['mail' => 'no-mail']],
            'issue date'           => [['issue_date' => '2024-01-01']],
            'first issue date'     => [['first_issue_date' => '2024-01-01']],
            'valid until'          => [['valid_until' => '2024-01-01']],
            'training course id'   => [['training_course_id' => '123']],
            'training course zero' => [['training_course_id' => 0]],
            'honor code'           => [['honor_code' => 2]],
            'honor code date'      => [['honor_code_date' => 'today']],
            'first aid'            => [['first_aid' => 'yes']],
            'first aid date'       => [['first_aid_date' => 'today']],
            'organisation id'      => [['organisation_id' => '999']],
            'lid'                  => [['lid' => -1]],
            'empty street'         => [['street' => '']],
            'empty academic title' => [['academic_title' => '']],
        ];
    }

    public function test_the_age_range_can_be_configured(): void
    {
        $license = ['birthdate' => strtotime('-10 years')];

        $this->client->setMinYears(16);

        $this->expectException(InvalidInputDataException::class);
        $this->expectExceptionMessage('between 16 and 120 years');
        $this->validator->validateRequest($license, 'correct');
    }
}
