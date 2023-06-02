# Changelog

All notable changes to `lims-php/client` will be documented in this file

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
