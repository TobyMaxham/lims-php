# A PHP interface for the DOSB LiMS API

This PHP interface simplifies interaction with the DOSB LiMS API, 
serving as a convenient wrapper around its functionality.

At the moment, the package supports all API endpoints for licenses 
that are available at the time of writing *(2024-03-30)*.

**Note: This package is not an official package from DOSB.**
The package was developed by [TobyMaxham](https://github.com/TobyMaxham/) and is not affiliated with DOSB.

### Laravel

Looking for a Laravel package? Check out [tobymaxham/laravel-lims](https://github.com/TobyMaxham/laravel-lims) for a Laravel wrapper around this package.


## Requirements

- PHP 8.1 or higher
- guzzlehttp/guzzle

I use guzzlehttp/guzzle for the HTTP requests instead of PHP's built-in cURL functions.
This is because Guzzle is more powerful and easier to use.

If you are using PHP 8.0 or lower, just write me an email and I will provide
you with a compatible version of the package.
But I recommend updating your PHP version to the latest stable release.


## Documentation

This is a PHP interface for the DOSB LiMS API. It is a simple wrapper around the API to make it easier to use.
The full DOSB LiMS API documentation can be found at https://gitlab.ghostthinker.de/pub/lims_connect
A copy of the (German) API documentation is included in [docs/lims-api.md](docs/lims-api.md).


## Upgrading from 1.x

Version 2 is a complete rewrite. The namespace changed from `TobyMaxham\Lims` to `TobiasMaxham\PhpLims`,
`isDemo()` was replaced by `setTestMode()` and `lookup()` now uses named parameters instead of an array.
`download()` is not available in this version yet.
The cURL based version is still available as tag `v1.2.0`.


## Installation

You can install the package via composer:

``` bash
composer require tobymaxham/php-lims
```

## Usage

### Basic usage

``` php
use TobiasMaxham\PhpLims\Client;

$client = new Client($username, $password);

// You can change the API to the demo environment
// $client->setTestMode();


// Call to create or update a license.
// Note: This Interface does not validate the input data. Please refer to the API documentation for the correct data format.

/** @var \TobiasMaxham\PhpLims\Models\CreatedLicense $license */
$license = $client->create($licenseData);  // Attributes: $license_number_dosb, $lid

/** @var \TobiasMaxham\PhpLims\Models\UpdatedLicense $license */
$license = $client->correct($licenseData, $licenseNumberDosb);
$license = $client->extend($licenseData, $licenseNumberDosb);


// Request to load all fields of a license based on license_number_dosb

/** @var \TobiasMaxham\PhpLims\Models\License $license */
$license = $client->load($licenseNumberDosb);


// Retrieval of licenses based on optional parameters

/** @var array|\TobiasMaxham\PhpLims\Models\License[] $license */
$license = $client->lookup(organisationId: $organisationId, offset: $offset, limit: $limit, validationStatus: $validationStatus, isObscured: $isObscured, idsOnly: $idsOnly, licenseNumberOrganisation: $licenseNumberOrganisation);


// Call to delete a license based on the DOSB license number.

/** @var \TobiasMaxham\PhpLims\Models\DeletedLicense $deletedLicense */
$deletedLicense = $client->delete($licenseNumberDosb); // Attributes: $license_number_dosb, $lid


// Request to transfer a license to another target association.
// ...

```

## Methods

### Request a license

Call to create or update a license.

Endpoint: **/request** <br>
Documentation: [#lizenz-anfordern](https://gitlab.ghostthinker.de/pub/lims_connect#lizenz-anfordern)

The input data is not validated by the interface. Please refer to the API documentation for the correct data format.<br>
There is a distinction between creating and updating a license. The `create` method generates a new license, while the `correct` and `extend` methods modify an existing one.<br>

Currently, within the interface, there is no functional difference between `correct` and `extend`—both update the license. However, in the API endpoint, the distinction matters: if license data is included in your request, the license will be automatically extended.

``` php
// Use the create method to create a new license
$license = $client->create([
    'organisation_id'    => 999,
    'firstname'          => 'John',
    'lastname'           => 'Doe',
    'birthdate'          => strtotime('1980-01-01'),
    'gender'             => 'u',
    'street'             => 'Waldstrasse 1',
    'postal'             => 11011,
    'city'               => 'Berlin',
    'issue_date'         => strtotime('2021-01-01'),
    'training_course_id' => 123,
    'issue_place'        => 'Berlin',
    'honor_code'         => 0,
    'first_aid'          => 0,
]); // Attributes: $license_number_dosb, $lid


// Use the correct method to update an existing license
$license = $client->correct([
    'firstname' => 'Max',
    'lastname'  => 'Mustermann',
    'gender'    => 'm',
], 'XXX-X-X-0123456'); // Attributes: $license_number_dosb, $lid

// Use the extend method to extend the license
$license = $client->extend([
    'street'     => 'Weg 1',
    'issue_date' => strtotime('2023-01-01'),
], 'XXX-X-X-0123456'); // Attributes: $license_number_dosb, $lid
```


### Downloading licenses

Not yet implemented:

/download
/download_multiple

### Migrating licenses

Not yet implemented:


/migration_request
/migration_get
/migration_decline
/migration_accept



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Security

If you've found a bug regarding security please mail [git@maxham.de](mailto:git@maxham.de) instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
