# Changelog

All notable changes to this project will be documented in this file.

---

## v0.2.0 - 2026-05-10

### Added

- Added field exposure policies for fillable, request rules, resource visibility, model hidden fields, and write-only fields
- Added expanded default sensitive column handling for tokens, OTP fields, API keys, client secrets, private keys, and recovery codes
- Added automatic model `$hidden` generation for sensitive fields
- Added hashed password casts while keeping passwords writable for store and update flows
- Added schema metadata for indexes, unique indexes, foreign keys, enums, and check constraints
- Added validation inference for unique indexes, foreign keys, enum/check values, email fields, and UUID fields
- Added optional `--routes` and `--api-routes` generation for `routes/web.php`
- Added optional `--tests` Pest endpoint smoke test generation

---

## v0.1.0 - 2026-05-08

### Added

- Initial package release
- Added `api:from-table` Artisan command
- Added Model generator
- Added StoreRequest generator
- Added UpdateRequest generator
- Added StoreData generator
- Added UpdateData generator
- Added StoreAction generator
- Added UpdateAction generator
- Added API Resource generator
- Added API Controller generator
- Added fillable inference
- Added cast inference
- Added validation rule inference
- Added `--dry-run` option
- Added `--force` option
- Added publishable config
- Added publishable stubs

---

## Future v0.3.0

### Planned

- Resource generator
- Service generator
- DTO generator
