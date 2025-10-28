# Enterprise Cleanup and Documentation Action Plan

> Non-destructive. Repeatable. Verified. CI-ready. Windows/Docker friendly.

This plan guides a safe, enterprise-grade cleanup for the codebase and documentation. It is designed to avoid breaking links or runtime behavior while making the repository professional, consistent, and production-ready.

- All steps default to DRY-RUN where possible.
- Changes happen in a short-lived branch + PR with full test gating.
- Each stage has clear exit criteria and verification.

---

## Guiding Principles

- [ ] Non-destructive first: never move/refactor production code paths until covered by tests.
- [ ] Always branch, tag, and record diffs; never force-push to main.
- [ ] Prefer configuration and automation over manual edits.
- [ ] Verify at each stage: run unit tests, link checks, static analysis.
- [ ] Keep Docker + Windows compatibility in all scripts.

---

## Stage 0 — Safety Snapshot (branch, tag, backups)

- [ ] Create branch: `cleanup/structure-and-docs`
- [ ] Tag current baseline: `pre-cleanup-YYYYMMDD`
- [ ] Verify tests pass now:
  - Docker: `docker-compose exec php vendor/bin/phpunit --colors=always --testdox`
- [ ] Optional: backup docs folder(s) to `docs/_legacy-YYYYMMDD/` (no deletions yet)

Exit criteria: green test run, branch and tag created, no uncommitted changes.

---

## Stage 1 — Repository Inventory and Mapping

- [ ] Generate a file inventory (tracked files):
  - `git ls-tree -r HEAD --name-only > repo-files.txt`
- [ ] Map directories and responsibilities:
  - `sections/` (production code; DO NOT MOVE unless strictly needed)
  - `tests/` (unit/integration tests)
  - `docker/` (infra)
  - `storage/` (logs, email logs)
  - `docs/` (to be created/normalized)
  - `scripts/` (repo utilities/automation)
- [ ] Identify non-production assets in root to relocate to `docs/` or `scripts/` (defer actual moves to Stage 11).

Exit criteria: `repo-files.txt` committed; inventory reviewed.

---

## Stage 2 — Documentation Baseline

Create/update these docs with accurate, current information (no "100% complete" claims unless verified):

- [ ] `README.md` (purpose, quickstart, stack, Docker commands, test commands)
- [ ] `CONTRIBUTING.md` (branch model, commit conventions, code review checklist)
- [ ] `CODE_OF_CONDUCT.md`
- [ ] `SECURITY.md` (vuln reporting, support windows)
- [ ] `ARCHITECTURE.md` (folders, modules, data flow; note that production PHP lives under `sections/` and is not moved)
- [ ] `TESTING.md` (PHPUnit usage, how to run, coverage, integration tests)
- [ ] `DEPLOYMENT.md` (Docker compose, environment variables, secrets guidance)
- [ ] `QUALITY.md` (linting, static analysis, code metrics, thresholds)
- [ ] `DEBUGGING.md` (logs, error handling, developer hints, common errors)
- [ ] `CHANGELOG.md` (Keep a Changelog format + Semantic Versioning)

Recommended structure:
- Keep all long-form docs under `docs/` and keep `README.md` concise with links to detail pages.

Exit criteria: Docs compile, links pass link-check (Stage 4), and reflect current status (no misleading claims).

---

## Stage 3 — Code Style & Linting (No Behavior Changes)

- [ ] Add `.editorconfig` (tabs/spaces, indent size, charset, EOLs)
- [ ] Add `.gitattributes` (normalize line endings; enforce text/lf for PHP/MD/JSON/YAML)
- [ ] Composer dev tools (proposed):
  - `squizlabs/php_codesniffer` (PHPCS, PSR-12)
  - `friendsofphp/php-cs-fixer`
  - `phpstan/phpstan` (level 4 to start)
  - `phpmd/phpmd` (ruleset: cleancode,codesize,controversial,design,naming,unusedcode)
  - `phploc/phploc` (metrics)
- [ ] Composer scripts (examples, add once approved):
```jsonc
{
  "scripts": {
    "lint": "phpcs --standard=PSR12 sections tests",
    "lint:fix": "phpcbf --standard=PSR12 sections tests",
    "fixer": "php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff",
    "fixer:apply": "php-cs-fixer fix --config=.php-cs-fixer.dist.php",
    "stan": "phpstan analyse sections tests --memory-limit=1G",
    "md": "phpmd sections text phpmd.xml",
    "test": "vendor/bin/phpunit --colors=always --testdox"
  }
}
```
- [ ] Add `.php-cs-fixer.dist.php` and `phpmd.xml` with agreed rules
- [ ] First run is DRY-RUN only (`lint`, `fixer`), review diff, then `lint:fix` minimal changes

Exit criteria: Coding standards enforced with minimal/no diffs; tests still pass.

---

## Stage 4 — Link Hygiene (Docs + Code Include Paths)

Markdown links:
- [ ] Add link checker (choose one):
  - Node: `markdown-link-check` (Windows-friendly)
  - Or Docker: `lycheeverse/lychee`
- [ ] Create `docs/link-exclude.txt` for intentionally offline links
- [ ] Check all docs: `npx markdown-link-check -q "**/*.md"`

PHP include/require audit (non-destructive):
- [ ] Grep for include/require usage
- [ ] Verify targets exist relative to runtime `BASE_PATH`
- [ ] Prepare `tools/audit-php-includes.php` (dry-run) to resolve/include paths and report misses (to be added once approved)

Exit criteria: All doc links valid; no missing includes reported.

---

## Stage 5 — Test Gating (Green Baseline)

- [ ] Run full PHPUnit (Docker):
  - `docker-compose exec php vendor/bin/phpunit --colors=always --testdox`
- [ ] Optional coverage (if/when xdebug enabled in php container)
- [ ] Ensure 0 failures; any cleanup change must keep green.

Exit criteria: Green tests before/after cleanup patches.

---

## Stage 6 — CI Pipeline (GitHub Actions)

- [ ] Add `.github/workflows/ci.yml` (jobs: composer install, phpcs, phpstan, phpunit, link check)
- [ ] Cache Composer dir for speed
- [ ] Gate PR merges on CI pass

Exit criteria: CI runs on PR, prevents regressions.

---

## Stage 7 — Automated Repair Scripts (Dry-Run First)

- [ ] `scripts/repair/fix-all.ps1` (Windows) and `scripts/repair/fix-all.sh` (Linux/macOS):
  - Run phpcs/phpcbf (PSR-12)
  - Run php-cs-fixer (configurable rules)
  - Normalize EOLs via `.gitattributes` then `git add --renormalize .`
  - Markdown link check (report-only)
  - Provide `-WhatIf` / `--dry-run` mode by default
- [ ] Composer scripts as a single entry point: `composer fix:all`

Exit criteria: One-command repair with dry-run, no functional changes, tests still green.

---

## Stage 8 — UI Event Handling System Audit (If applicable)

- [ ] Search for inline handlers (`onclick=`, etc.) and replace with delegated `addEventListener`
- [ ] Ensure consistent event naming and teardown to avoid leaks
- [ ] Add small guidelines in `QUALITY.md` for UI events

Exit criteria: No fragile inline events; consistent patterns.

---

## Stage 9 — Enhanced Debugging & Developer Feedback

- [ ] Centralize logging (consistent `error_log` or Monolog if desired)
- [ ] Document `APP_ENV` behaviors (testing/staging/production)
- [ ] Add `DEBUGGING.md` with common error signatures and suggested fixes (e.g., headers in CLI, deprecations like `strip_tags(null)`)

Exit criteria: Reproducible debug steps with actionable guidance.

---

## Stage 10 — Code Quality Metrics & Thresholds

- [ ] Run `phploc` for metrics
- [ ] Run `phpmd` and capture report to `reports/`
- [ ] Set initial, realistic thresholds in `QUALITY.md` (track over time)

Exit criteria: Baseline metrics recorded; trend tracking enabled.

---

## Stage 11 — Final Verification and Safe Tidying

- [ ] Re-run: lint, stan, md links, phpunit
- [ ] Move only non-code artifacts to `docs/` or `scripts/` as planned in Stage 1 (NO production code moves under `sections/` unless tests cover and team approves)
- [ ] Update any doc links after moves
- [ ] Open PR with summary, diffs, and verification checklist

Exit criteria: PR approved with green CI; no runtime changes; repository is organized and professional.

---

## Automated Repair Examples (Non-destructive)

- Header tests: Run in separate process to avoid CLI header warnings (already applied in tests)
- Duplicate test names: Guard by renaming with clear suffixes
- Null sanitization warnings: Add tests to detect, propose code fix PR separately (no change in cleanup pass)

---

## Do/Don’t Summary

- Do:
  - Use branches + PRs
  - DRY-RUN first for any fixer
  - Keep production paths (`sections/…`) stable
  - Verify with phpunit + link check every stage
- Don’t:
  - Move or rename production PHP files casually
  - Commit large formatter diffs without review
  - Change behavior in a cleanup-only PR

---

## Quick Command Reference

- Tests: `docker-compose exec php vendor/bin/phpunit --colors=always --testdox`
- PHPCS lint: `docker-compose exec php vendor/bin/phpcs --standard=PSR12 sections tests`
- PHPCS fix: `docker-compose exec php vendor/bin/phpcbf --standard=PSR12 sections tests`
- PHP-CS-Fixer (dry-run): `docker-compose exec php php-cs-fixer fix --dry-run --diff`
- PHPStan: `docker-compose exec php vendor/bin/phpstan analyse sections tests --memory-limit=1G`
- Markdown links (Node): `npx markdown-link-check -q "**/*.md"`
- Markdown links (Docker): `docker run --rm -v "%cd%":/data lycheeverse/lychee:latest --no-progress --verbose --exclude-file docs/link-exclude.txt /data`

---

## Acceptance Checklist (Sign-off)

- [ ] All docs updated and accurate (no misleading completeness claims)
- [ ] Lint/static analysis configured and passing (or failing only on allowed warnings)
- [ ] All links valid (docs + code includes)
- [ ] All tests green before/after cleanup
- [ ] CI enforces standards on PRs
- [ ] No production behavior changes made during cleanup

---

This plan adheres to the highest standards: non-destructive process, automated verification, and clear documentation—suitable for enterprise environments and long-term maintainability.
