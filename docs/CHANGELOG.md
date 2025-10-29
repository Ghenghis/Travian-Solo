# Changelog
All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [Unreleased]
- Documentation baseline improvements under `docs/`
- Link hygiene configuration and scoping
- Guarded cleanup script enhancements (Windows Docker quoting, Stage7 dry-run)

## [2025-10-28] - Documentation and Guardrails
### Added
- README (root) refreshed with quick start, structure, and quality automation
- `docs/DEPLOYMENT.md` with dev/prod steps, rollback, verification
- Quality tooling: PHPCS, PHP-CS-Fixer, PHPStan, PHPMD, PHPLoc, PHPCPD in composer
- `.php-cs-fixer.dist.php` with PSR-12 and common rules

### Changed
- `scripts/guarded/guarded-cleanup.ps1` improved Docker quoting and added Stage7 (dry-run)
- `lychee.toml` configuration to accept expected statuses and exclude unstable endpoints

### Fixed
- Windows PowerShell quoting issues for Docker and commit messages by using args and `-F` files
