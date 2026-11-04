# Changelog

All notable changes to `lims-php/client` will be documented in this file

## 3.0.0 - 2026-11-04

### Breaking changes

- Requires PHP 8.3 or higher (PHP 8.1 and 8.2 are no longer supported)
- Removed the dependency on guzzlehttp/guzzle. The package now only requires the PSR-18/PSR-17/PSR-7 interfaces
- `lookup()`: `$licenseNumberOrganisation` is now a `string` instead of a `bool`
- All exceptions extend the new `LimsException`, API errors extend the new `ApiException` (with `$statusCode` and `$responseBody`)
- Network errors throw a `ConnectionException` instead of a generic `\Exception`
- Removed the unused `MissingOrganisationIdException`

### Added

- Built-in curl based PSR-18 client as fallback, no additional package required
- Automatic detection of an installed PSR-18 client via `php-http/discovery` (optional)
- Constructor parameters `testMode`, `httpClient`, `requestFactory`, `streamFactory` and `timeout`
- `downloadMultiple()` for aggregated license print templates
- `migrationGet()` (returns the new `Migration` model), `migrationAccept()` and `migrationDecline()`
- `lookupOrganisations()` and `lookupAllOrganisations()` (endpoint `/lookup_organisations`) with the new `Organisation` model
- `lookupTrainingCourses()` and `lookupAllTrainingCourses()` (endpoint `/lookup_training_courses`) with the new `TrainingCourse` model
- `stats()` for statistics of DOSB training courses (endpoint `/stats`)
- Enums `FederalState`, `OrganisationCategory` and `AgeGroup` for the new filters
- Validation of the `limit` parameter (max. 1000 licenses, 20000 license numbers, 100 organisations or training courses)
- Updated API documentation in `docs/lims-api.md` (as of October 2026)
- `isTestMode()` and `endpoint()`
- `toArray()` and `isset()` support for models
- Unit tests and a GitHub Actions workflow for PHP 8.3 to 8.5
- German README with an English translation (`README-en.md`)

### Changed

- Default timeout of the built-in HTTP client raised to 60 seconds, because some API calls take more than 20 seconds
- Error messages of the API are unpacked if they contain nested JSON (e.g. `download_multiple`)

### Fixed

- `migrationRequest()` stopped with a debug call, it now sends the request and returns the status message
- `download()` with `urlOnly` returned the file instead of the URL, because the API only accepts `file_url_only` as string `"1"`
- Implicitly nullable parameters, which are deprecated since PHP 8.4
- Casting of model attributes with the value `"0"`
- Warning when a required field is missing in `create()`

## 2.1.0 - 2025-03-22

- Add input validation for `create()`, `correct()` and `extend()` (`LimsValidator`, `LicenseValidator`)
- Add `setMinYears()` / `setMaxYears()` for the birthdate validation
- Add `download()` again (`dina4`, `card`, `signet`, optionally only the download URL), it was missing since the 2.0 rewrite
- Add `InvalidDownloadFormatException`
- Add `license_holder_uuid` to the license model
- Set a default request timeout of 30 seconds
- Fix `NotFoundException` being thrown with status code 403 instead of 404
- Update README

## 2.0.0 - 2024-03-30

- Complete rewrite using guzzlehttp/guzzle instead of cURL
- Requires PHP 8.1 or higher
- Add `create()`, `correct()` and `extend()` (endpoint `/request`) and `delete()`
- `lookup()` uses named parameters instead of an array
- Models `License`, `CreatedLicense`, `UpdatedLicense` and `DeletedLicense`
- Separate exceptions per HTTP status code (401, 403, 404, 406, 422)
- `download()` is not available in this version

## 1.2.0 - 2023-06-03

- Requires PHP 8.0 or higher
- Add `download()` to load license print templates (`dina4`, `card`, `signet`), optionally only the download URL
- Add `InvalidDownloadFormatException`
- Add request timeout (30 seconds)
- Add `license_holder_uuid` to the license properties

## 1.0.0 - 2020-05-16

- initial release
- `lookup()` and `load()` via cURL
- Switch between live and demo API with `isLive()` / `isDemo()`
