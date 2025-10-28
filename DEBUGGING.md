# Debugging Guide

- Logs: `docker-compose logs -f php`, `docker-compose logs -f nginx`.
- Database: use `check-*.php` scripts to validate schema/connection.
- Headers in CLI: tests use `@runInSeparateProcess` to avoid warnings.
- Deprecations: watch for PHP 8.2 notices (e.g., `strip_tags(null)`), add guards in tests to surface.
- Email logs: `storage/email-log.txt`.
