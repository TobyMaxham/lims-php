# Changelog

All notable changes to `tobymaxham/php-lims` will be documented in this file

## 2.0.0 - 2024-03-30

- Complete rewrite using guzzlehttp/guzzle instead of cURL
- New namespace `TobiasMaxham\PhpLims` (breaking change)
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
