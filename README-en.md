# lims-php

[![Tests](https://github.com/TobyMaxham/lims-php/actions/workflows/tests.yml/badge.svg)](https://github.com/TobyMaxham/lims-php/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/lims-php/client/v)](https://packagist.org/packages/lims-php/client)
[![Total Downloads](https://poser.pugx.org/lims-php/client/downloads)](https://packagist.org/packages/lims-php/client)
[![PHP Version Require](https://poser.pugx.org/lims-php/client/require/php)](https://packagist.org/packages/lims-php/client)
[![License](https://poser.pugx.org/lims-php/client/license)](https://packagist.org/packages/lims-php/client)

[🇩🇪 Deutsch](README.md) · 🇬🇧 English

A lightweight, framework-agnostic PHP client for the **LiMS API** (license management system) of the
[DOSB Wissensnetz](https://wissensnetz.dosb.de), the knowledge network of the German Olympic Sports Confederation.
Create, correct, extend, fetch and delete licenses, download license print templates, handle license
migrations between organisations and fetch organisations, training courses and statistics – with a single class.

> [!NOTE]
> This package is **not an official DOSB product** and is not affiliated with the DOSB or the operator of the
> LiMS API. It only connects to the documented interface and is maintained independently.
> The API and its documentation may change at any time, independently of this package.

> [!TIP]
> The LiMS API and its official documentation are only available in German. The German [README](README.md)
> is the primary documentation of this package; this file is a translation.

## Contents

- [Features](#features)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Credentials and demo environment](#credentials-and-demo-environment)
- [Create, correct and extend licenses](#create-correct-and-extend-licenses)
- [Load and look up licenses](#load-and-look-up-licenses)
- [Delete licenses](#delete-licenses)
- [Download license print templates](#download-license-print-templates)
- [License migrations](#license-migrations)
- [Organisations and training courses](#organisations-and-training-courses)
- [Statistics](#statistics)
- [Error handling](#error-handling)
- [Using your own HTTP client](#using-your-own-http-client)
- [Usage with Laravel](#usage-with-laravel)
- [Upgrading from 2.x to 3.0](#upgrading-from-2x-to-30)
- [Testing](#testing)

## Features

- ✅ **All LiMS API endpoints**: `request`, `load`, `lookup`, `delete`, `download`, `download_multiple`, all migration endpoints as well as `lookup_organisations`, `lookup_training_courses` and `stats`
- ✅ **No mandatory HTTP client dependency**: works with the built-in curl client or any [PSR-18](https://www.php-fig.org/psr/psr-18/) client (Guzzle, Symfony HttpClient, …)
- ✅ **Validation before sending**: invalid license data is detected before a request is sent to the API
- ✅ **Meaningful exceptions** per HTTP status code with a common base class
- ✅ **Automatic paging** through all organisations and training courses
- ✅ **Live and demo system** with a single switch
- ✅ Tested with PHPUnit on PHP 8.3 to 8.5

## Installation

```bash
composer require lims-php/client
```

**Requirements**

- PHP `^8.3`
- `ext-curl` **or** any PSR-18 HTTP client

If the curl extension is enabled (which is the case for almost every PHP installation), the package works right
after `composer require` – without Guzzle or any other additional package. See
[Using your own HTTP client](#using-your-own-http-client).

## Quick start

```php
use LimsPhp\Lims\Client;
use LimsPhp\Lims\Exceptions\LimsException;

$lims = new Client(username: 'my-api-user', password: 'secret');

try {
    $license = $lims->load('TEST-T-A-0000001');

    echo $license->firstname.' '.$license->lastname;
    echo 'Valid until: '.date('Y-m-d', $license->valid_until);
} catch (LimsException $e) {
    // network, API or validation error
}
```

## Credentials and demo environment

API credentials (and access to the demo environment) are provided by the support of the
[DOSB Wissensnetz](https://wissensnetz.dosb.de/schnellstart-lims-anbindung).

> [!TIP]
> The username is **not** your e-mail address. In most cases it is the part of the e-mail address before the `@`.

Test your integration on the **demo system** first. There you can create and change licenses of your
organisation without affecting production data. The demo system is reset from time to time.

```php
// Use the demo system right away …
$lims = new Client('my-api-user', 'secret', testMode: true);

// … or switch later
$lims->setTestMode();       // demo:  https://demo-dosb.wissensnetz.eu/api/lims
$lims->setTestMode(false);  // live:  https://wissensnetz.dosb.de/api/lims
```

> [!WARNING]
> IDs of training courses (`training_course_id`) and organisations (`organisation_id`) may differ between the
> demo and the live system. Check them when switching to the live system.

## Create, correct and extend licenses

All three methods use the `/request` endpoint. The data is validated first (required fields, date fields as
timestamps, gender `m`/`w`/`u`, e-mail, …). Invalid data throws an `InvalidInputDataException` and **no**
request is sent.

```php
// Create a new license
$license = $lims->create([
    'organisation_id'    => 999,
    'firstname'          => 'Max',
    'lastname'           => 'Mustermann',
    'birthdate'          => strtotime('1980-01-01 12:00'),
    'gender'             => 'm',
    'street'             => 'Waldstraße 1',
    'postal'             => '10115',
    'city'               => 'Berlin',
    'issue_date'         => strtotime('2025-01-01 12:00'),
    'training_course_id' => 123,
    'issue_place'        => 'Berlin',
    'honor_code'         => true,
    'first_aid'          => true,
]);

$license->lid;                 // 1000001
$license->license_number_dosb; // "TEST-T-A-0000001"

// Correct an existing license – by DOSB license number or LiMS id (lid)
$lims->correct(['firstname' => 'Erika', 'gender' => 'w'], 'TEST-T-A-0000001');
$lims->correct(['street' => 'Bergweg 2'], 1000001);

// Extend an existing license – new issue date and new validity date
$lims->extend([
    'issue_date'  => strtotime('2026-11-04 12:00'),
    'valid_until' => strtotime('2030-11-03 12:00'), // issue date + validity period - 1 day
], 'TEST-T-A-0000001');
```

> [!WARNING]
> Also pass `valid_until` when extending a license. Without this field the API applies the new issue date, but
> the validity date stays unchanged. The validity period of a training course (`validity_period`) is available
> via [`lookupTrainingCourses()`](#organisations-and-training-courses).

> [!IMPORTANT]
> Date fields are passed as **unix timestamps**. The API documentation recommends using **12:00 noon** instead
> of midnight to avoid daylight saving time issues.

Further notes:

- `extend()` must not change `organisation_id`, `first_issue_date` or `training_course_id`, `correct()` must not change the `organisation_id`.
- For an **import of existing licenses** additionally pass `first_issue_date`. For a new license only pass `issue_date`.
- The allowed age range for `birthdate` defaults to 0 to 120 years and can be changed:

```php
$lims->setMinYears(16);
$lims->setMaxYears(100);
```

All fields are described in the [API documentation](docs/lims-api.md#felddefinitionen) (German).

## Load and look up licenses

```php
// Load a license with all fields
$license = $lims->load('TEST-T-A-0000001');
$license->toArray(); // all fields as array

// Look up licenses – all parameters are optional
$licenses = $lims->lookup(
    organisationId: 9004,
    offset: 0,
    limit: 100,                        // max. 1000 (max. 20000 with idsOnly)
    validationStatus: true,            // true = valid, false = expired licenses
    isObscured: false,                 // true = only anonymised licenses
    licenseNumberOrganisation: 'V-123' // license number of the organisation
);

foreach ($licenses as $license) {
    echo $license->license_number_dosb;
}

// Only fetch the DOSB license numbers
$numbers = $lims->lookup(organisationId: 9004, idsOnly: true); // ['TEST-T-A-0000001', …]
```

> [!NOTE]
> The API returns some fields differently than they are sent when creating a license. `gender`, for example, is
> returned as `männlich`/`weiblich` instead of `m`/`w`. Anonymised licenses (`is_obscured`) contain empty names and
> a shortened postal code. License numbers are passed through unchanged, even if they contain spaces or tabs (in
> legacy data). This is the only way to load them again.

## Delete licenses

```php
$deleted = $lims->delete('TEST-T-A-0000001');
$deleted->lid;
$deleted->license_number_dosb;
```

## Download license print templates

`dina4` and `card` are returned as PDF, `signet` as JPG.

```php
// Single license
$pdf = $lims->download('TEST-T-A-0000001');           // dina4
$pdf = $lims->download('TEST-T-A-0000001', 'card');
$jpg = $lims->download('TEST-T-A-0000001', 'signet');

file_put_contents('license.pdf', $pdf);

// Only the download URL instead of the file
$url = $lims->download('TEST-T-A-0000001', 'dina4', urlOnly: true);

// Multiple licenses in one PDF (dina4 or card)
$pdf = $lims->downloadMultiple(['TEST-T-A-0000001', 'TEST-T-A-0000002'], 'card');
```

> [!NOTE]
> Expired or anonymised licenses can not be downloaded, the API responds with HTTP 406 (`NotAcceptableException`).
> For the format `signet` the API (as of November 2026) sometimes returns an empty file. In that case an
> `InvalidResponseException` is thrown, with `urlOnly: true` you still get a download URL.

## License migrations

Licenses can be moved to another organisation within the same umbrella organisation (e.g. between two regional
associations of a national federation). The process has two sides:

1. The **target organisation** requests the license (`migrationRequest`).
2. The **current organisation** sees the request (`migrationGet`) and accepts or declines it.

```php
// 1. Target organisation: request the license (organisationId = own organisation, i.e. the target)
$target = new Client('api-user-target', 'secret');
$status = $target->migrationRequest('TEST-T-C-0000007', organisationId: 9003);
// "Ihre Anfrage wurde weitergeleitet." or, with permission for a direct migration, "The license has been migrated."

// 2. Current organisation: fetch open requests for its own licenses …
$current = new Client('api-user-current', 'secret');

foreach ($current->migrationGet(organisationId: 9002) as $migration) {
    $migration->migration_id;        // 1000007 (equals the lid of the license)
    $migration->license_number_dosb; // "TEST-T-C-0000007"
    $migration->organisation_id;     // 9003 – the requesting target organisation

    // … and accept or decline them
    $current->migrationAccept($migration->migration_id);  // "The license has been migrated successfully."
    // $current->migrationDecline($migration->migration_id); // "Migrate request has been declined."
}
```

> [!NOTE]
> - The current organisation can not start a migration (HTTP 403), and only it can accept or decline.
> - Only one request per license can be open, a second one throws an `AccessDeniedException`.
> - If there are no open requests, the API responds with HTTP 404 and `migrationGet()` throws a `NotFoundException`.
> - The official API documentation does not clearly describe the direction of a migration. The process above has
>   been tested on the demo system (as of November 2026). Status messages are partly German, partly English.

## Organisations and training courses

These lookups provide the ids you need for `organisation_id` and `training_course_id`.
The API returns at most 100 items per request. The `…All` methods page through all results automatically.

```php
use LimsPhp\Lims\Enums\FederalState;
use LimsPhp\Lims\Enums\OrganisationCategory;

// Look up organisations – all filters are optional
$organisations = $lims->lookupOrganisations(
    offset: 0,
    limit: 100,
    federalState: FederalState::Niedersachsen,               // or 'Niedersachsen'
    organisationParentId: 9001,                              // only direct sub-organisations
    organisationCategory: OrganisationCategory::Spitzenverband, // SV, LSB, VmbA, UG
);

foreach ($organisations as $organisation) {
    echo $organisation->organisation_id.': '.$organisation->name;
}

// All sub-organisations of an organisation, without paging yourself
$all = $lims->lookupAllOrganisations(organisationParentId: 9001);

// Look up DOSB training courses
$courses = $lims->lookupTrainingCourses(
    assignedOrganisationId: 9001, // assigned to this organisation
    level: 1,                     // license level
    trainingCourse: 'T-C/B',      // e.g. T-C/B, ÜL-B, VM-C
    sport: 'Kanu',
);

foreach ($courses as $course) {
    echo "{$course->id}: {$course->title} (valid for {$course->validity_period} years)";
}

// All training courses of an organisation
$all = $lims->lookupAllTrainingCourses(assignedOrganisationId: 9001);
```

The enums `FederalState` and `OrganisationCategory` are available for the federal state and the organisation
category. Plain strings work as well.

## Statistics

Statistics of DOSB training courses for a period, grouped by training course and gender:

```php
use LimsPhp\Lims\Enums\AgeGroup;

$stats = $lims->stats(
    startDate: new DateTimeImmutable('2025-01-01 12:00'),
    endDate: new DateTimeImmutable('2025-12-31 12:00'),
    // optional filters:
    birthdate: AgeGroup::From19To26,     // age group 1–5, relative to the start date
    postal: '301',                       // postal code area, max. 3 digits
    gender: 'w',
    trainingCourse: 'T-B/L',
    federalState: FederalState::Niedersachsen,
    organisationId: 9001,
);

// ['T-B/L' => ['new' => ['m' => '30', 'w' => '30', 'u' => '10', 'g' => '70'], 'extend' => [...], 'valid' => [...]], ...]
// new = new licenses, extend = extensions, valid = valid licenses, g = total (values as strings)
```

> [!NOTE]
> Further filters: `trainingCourseId`, `level`, `sport`, `profile`, `focus`, `discipline`. According to the API
> documentation `trainingCourseId` should be used alone, combined with other detail filters the result may be 0.
> Dates are sent as unix timestamps, like all date fields of the API.

## Error handling

All exceptions extend `LimsPhp\Lims\Exceptions\LimsException`. API errors (`ApiException` and its
subclasses) additionally provide `$statusCode` and `$responseBody`.

| Exception                        | Meaning                                                      |
|----------------------------------|--------------------------------------------------------------|
| `UnauthorizedException`          | 401 – invalid credentials                                    |
| `AccessDeniedException`          | 403 – access denied (e.g. missing permission)                |
| `NotFoundException`              | 404 – license or migration request not found                 |
| `NotAcceptableException`         | 406 – e.g. missing field, invalid license or training course |
| `UnprocessableEntityException`   | 422 – license data was rejected by the API                   |
| `ApiException`                   | Any other API error status (base class of the above)         |
| `InvalidResponseException`       | The API response could not be processed                      |
| `ConnectionException`            | Network error (DNS, timeout, connection refused, …)          |
| `InvalidInputDataException`      | Invalid input data, no request was sent                      |
| `InvalidDownloadFormatException` | Invalid download format                                      |

```php
use LimsPhp\Lims\Exceptions\ApiException;
use LimsPhp\Lims\Exceptions\InvalidInputDataException;
use LimsPhp\Lims\Exceptions\NotFoundException;

try {
    $lims->extend(['issue_date' => strtotime('2027-01-01 12:00')], 'TEST-T-A-0000001');
} catch (InvalidInputDataException $e) {
    // fix the data
} catch (NotFoundException $e) {
    // license does not exist
} catch (ApiException $e) {
    error_log("LiMS error {$e->statusCode}: {$e->getMessage()}");
}
```

## Using your own HTTP client

The package only requires the PSR HTTP interfaces ([PSR-18](https://www.php-fig.org/psr/psr-18/),
[PSR-17](https://www.php-fig.org/psr/psr-17/), [PSR-7](https://www.php-fig.org/psr/psr-7/)). If no client is
passed, it is chosen automatically:

1. a PSR-18 client that is already installed in your project, if [`php-http/discovery`](https://github.com/php-http/discovery) is installed (e.g. Guzzle or Symfony HttpClient),
2. otherwise the built-in lightweight curl client (timeout: 60 seconds, configurable via `timeout:`).

For your own configuration (proxy, logging, retries, timeouts) or in tests, pass the client and factories yourself:

```php
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;

$factory = new HttpFactory();

$lims = new Client(
    username: 'my-api-user',
    password: 'secret',
    httpClient: new Guzzle(['timeout' => 60]),
    requestFactory: $factory,
    streamFactory: $factory,
);
```

With Symfony HttpClient:

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$factory = new Psr17Factory();

$lims = new Client('my-api-user', 'secret', httpClient: new Psr18Client(), requestFactory: $factory, streamFactory: $factory);
```

Only change the timeout of the built-in curl client. Some calls like `lookup()` can take more than 20 seconds
on the API, a timeout that is too short results in a `ConnectionException`:

```php
$lims = new Client('my-api-user', 'secret', timeout: 120);
```

## Usage with Laravel

The client is framework-agnostic and can be registered in a service provider, for example:

```php
// app/Providers/AppServiceProvider.php
use LimsPhp\Lims\Client;

public function register(): void
{
    $this->app->singleton(Client::class, fn () => new Client(
        username: config('services.lims.username'),
        password: config('services.lims.password'),
        testMode: (bool) config('services.lims.test_mode', false),
    ));
}
```

```php
// config/services.php
'lims' => [
    'username'  => env('LIMS_USERNAME'),
    'password'  => env('LIMS_PASSWORD'),
    'test_mode' => env('LIMS_TEST_MODE', false),
],
```

Afterwards you can inject `Client` anywhere via dependency injection.

## Upgrading from 2.x to 3.0

Version 3 is a major release with the following breaking changes:

- **PHP 8.3** is the new minimum version. PHP 8.1 and 8.2 are no longer supported (end of life 2025-12-31 and 2026-12-31).
- **Guzzle is no longer a dependency.** If you want to keep using Guzzle, install it yourself and pass it (see above) or additionally install `php-http/discovery`.
- The constructor has new optional parameters: `testMode`, `httpClient`, `requestFactory`, `streamFactory`, `timeout`.
- `lookup()`: `$licenseNumberOrganisation` is now a `string` (the license number of the organisation) instead of a `bool`.
- All exceptions extend `LimsException` (instead of `\Exception`). API exceptions provide `$statusCode` and `$responseBody`.
- Network errors throw a `ConnectionException` instead of a generic `\Exception`.
- `MissingOrganisationIdException` was removed (it was unused).
- `migrationRequest()` now works and returns the status message of the API. `migrationGet()` returns `Migration` objects.

## API documentation

The starting point for the integration is the page
[Schnellstart LiMS-Anbindung](https://wissensnetz.dosb.de/schnellstart-lims-anbindung) (German) in the DOSB Wissensnetz.
It links the current interface documentation as PDF and the acceptance protocol for using the interface.

A copy of the documentation (as of 2026-10-26) is included in [docs/lims-api.md](docs/lims-api.md).

> [!NOTE]
> The former documentation at `gitlab.ghostthinker.de` is no longer available. References to it in the copy are
> part of the original document. The direct link to the PDF in the Wissensnetz may change, so always use the
> quick start page.

## Testing

```bash
composer install
composer test
```

The tests run without credentials and without a connection to the real API.

## Changelog

Please see the [CHANGELOG](CHANGELOG.md) for all changes.

## Security

Please do not report security issues via the issue tracker, send an e-mail to [git@maxham.net](mailto:git@maxham.net) instead.

## License

MIT. See [LICENSE.md](LICENSE.md).
