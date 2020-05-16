# A PHP interface for the DOSB LiMS API

This PHP interface simplifies interaction with the DOSB LiMS API,
serving as a convenient wrapper around its functionality.

At the moment, the package supports loading and looking up licenses.

**Note: This package is not an official package from DOSB.**
The package was developed by [TobyMaxham](https://github.com/TobyMaxham/) and is not affiliated with DOSB.


## Requirements

- PHP 7.4 or higher
- ext-curl
- ext-json


## Documentation

This is a PHP interface for the DOSB LiMS API. It is a simple wrapper around the API to make it easier to use.
The full DOSB LiMS API documentation can be found at https://gitlab.ghostthinker.de/pub/lims_connect
A copy of the (German) API documentation is included in [docs/lims-api.md](docs/lims-api.md).


## Installation

You can install the package via composer:

``` bash
composer require lims-php/client
```

## Usage

``` php
use LimsPhp\Lims\Client;

$client = new Client($username, $password);

// You can change the API to the demo environment
// $client->isDemo();


// Retrieval of licenses based on optional parameters
// (organisation_id, offset, limit, validation_status, is_obscured, ids_only, license_number_organisation)

/** @var \LimsPhp\Lims\Models\License[] $licenses */
$licenses = $client->lookup([
    'organisation_id' => 1234,
    'limit'           => 5,
]);


// Request to load all fields of a license based on license_number_dosb

/** @var \LimsPhp\Lims\Models\License|null $license */
$license = $client->load('XXX-X-X-0123456');

echo $license->firstname;
echo $license->license_number_dosb;
```

If the API does not respond with HTTP status 200, a
`LimsPhp\Lims\Exceptions\InvalidApiResponse` is thrown.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Security

If you've found a bug regarding security please mail [git@maxham.de](mailto:git@maxham.de) instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
