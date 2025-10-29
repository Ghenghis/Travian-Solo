# Architecture Overview

- Production PHP lives under `sections/` (do not move in cleanup passes).
- Tests under `tests/` (PHPUnit).
- Docker configs under `docker/` and `docker-compose.yml`.
- Scripts and utilities in `scripts/` and root test scripts.
- Storage/logs in `storage/`.
- Databases: global DB + world DBs (e.g., `travian_testworld`).
- Core flows: registration (global), activation (world), login; middleware: RateLimiter; utilities: Security.
