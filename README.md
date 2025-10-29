# Travian Solo Game Server

Production-ready PHP backend with Dockerized services, PHPUnit test suite, and enterprise-grade cleanup and documentation.

## Overview

- PHP 8.2 runtime (Dockerized)
- MySQL, Redis, Nginx via docker-compose
- Tests: PHPUnit (unit/integration), all green
- Quality guardrails: PHPCS (PSR-12), PHP-CS-Fixer, PHPStan, PHPMD, PHPLoc
- Non-destructive cleanup scripts under `scripts/guarded`

## Quick Start

```bash
# Start services
docker-compose up -d

# Run unit tests
docker-compose exec php vendor/bin/phpunit --colors=always --testdox
```

## Project Structure

- `sections/` Production PHP code (do not move during cleanup)
- `tests/` PHPUnit tests
- `docker/` Docker configuration
- `scripts/` Utilities and guarded cleanup scripts
- `storage/` Runtime storage (e.g., email logs)
- `docs/` Documentation (includes future `docs/AI` add-ons)

## Testing

```bash
docker-compose exec php vendor/bin/phpunit --colors=always --testdox
```

Header-emitting tests run in isolated processes to avoid CLI header warnings.

## Quality and Automation

Composer scripts (after `composer install`):

```bash
composer lint          # PHPCS PSR-12
composer lint:fix      # PHPCS auto-fix
composer fixer         # PHP-CS-Fixer dry-run
composer fixer:apply   # PHP-CS-Fixer apply
composer stan          # PHPStan static analysis
composer md            # PHPMD code smells (phpmd.xml)
composer loc           # PHPLoc metrics
composer dup           # PHPCPD duplicates
```

Guarded cleanup (DRY-RUN by default):

```powershell
pwsh -NoProfile -File .\scripts\guarded\guarded-cleanup.ps1 -Stage Stage1       # inventory
pwsh -NoProfile -File .\scripts\guarded\guarded-cleanup.ps1 -Stage Stage4       # link checks
pwsh -NoProfile -File .\scripts\guarded\guarded-cleanup.ps1 -Stage Stage5 -Apply # tests
pwsh -NoProfile -File .\scripts\guarded\guarded-cleanup.ps1 -Stage Stage7       # repair (dry-run)
```

## Documentation

- See `docs/README.md` and the detailed guides under `docs/`
- The `docs/AI/` folder is reserved for future AI integration and remains unchanged during cleanup

## License

See `LICENSE`.