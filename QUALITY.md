# Code Quality

- Style: PSR-12 via PHPCS; auto-fix via PHP-CS-Fixer (dry-run first).
- Static Analysis: PHPStan (level TBD), PHPMD for code smells.
- Metrics: PHPLOC reports for visibility.
- Docs link checks: Lychee via scripts/guarded (Docker or npx fallback).
- All quality gates run in CI before merge (planned).
