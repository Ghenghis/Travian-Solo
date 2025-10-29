# Deployment Guide

- Environment: Windows-first, Dockerized (php, nginx, mysql, redis)
- Strategy: Non-destructive, gated by tests and link checks
- Tools: docker-compose, guarded scripts, PHPUnit

## Prerequisites
- Docker Desktop for Windows
- Clone repository on Windows path without spaces
- `.env` configured (see examples in docs and check scripts)

## Services
- php: runs application + composer + PHPUnit
- nginx: serves public endpoints
- mysql: global and world databases
- redis: optional cache/session

## Local Development (Dev)
1. Start stack
   ```bash
   docker-compose up -d
   docker-compose ps
   ```
2. Install composer deps (inside container)
   ```bash
   docker-compose exec php composer install --no-interaction --no-progress
   ```
3. Validate
   ```bash
   docker-compose exec php vendor/bin/phpunit --colors=always --testdox
   ```
4. Link hygiene (optional)
   ```powershell
   pwsh -NoProfile -File .\scripts\guarded\guarded-cleanup.ps1 -Stage Stage4 -Apply
   ```

## Production Deployment (VPS)
1. Provision host
   - OS updates, firewall, Docker, docker-compose
   - Create non-root deploy user
2. Secrets and config
   - Populate `.env` with secure secrets
   - Configure nginx site and SSL (Let's Encrypt)
3. Start stack
   ```bash
   docker-compose up -d --build
   docker-compose logs -f nginx
   ```
4. Database migration/seed
   - Import global schema and world schemas
   - Verify connection via included check scripts
5. Health check
   - `/v1/health/check` returns HTTP 200 (nginx -> php -> app)

## Rollback
- Keep previous image tags; switch compose file to prior tag and `docker-compose up -d`
- Maintain DB snapshots (external backups) before schema changes

## Verification Checklist
- Unit tests: `docker-compose exec php vendor/bin/phpunit --testdox`
- Link checks: guarded Stage 4
- Nginx access/error logs clean
- DB connectivity for global + each world
- Email provider configured (activation, recovery)

## Troubleshooting
- Connection refused: service not started or port conflict
- 502 Bad Gateway: php-fpm not healthy; check php logs
- Permission denied: volume ownership; adjust UID/GID or mount options
- Slow Windows volumes: prefer bind mounts for code only, named volumes for DB/data

## CI/CD (Planned)
- GitHub Actions workflow to run: link checks, lint, static analysis, tests
- Artifact: test reports and quality metrics
- Gates: block merge on failures

## Security
- Non-root containers where possible
- Minimal exposed ports
- Secrets via env or secret store (not committed)
- Regular image updates and vulnerability scans
