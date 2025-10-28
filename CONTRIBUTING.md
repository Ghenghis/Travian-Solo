# Contributing Guide

- Branch from `cleanup/structure-and-docs` or feature branches.
- Use conventional commits (feat:, fix:, chore:, docs:, test:, refactor:).
- Run tests before pushing: `docker-compose exec php vendor/bin/phpunit --colors=always --testdox`.
- Run non-destructive checks: `scripts/guarded/guarded-cleanup.ps1 -Stage Stage1`.
- Lint/style (to be enabled): PHPCS PSR-12, PHP-CS-Fixer, PHPStan.
- Open PRs with summary, before/after checks, and test output.
