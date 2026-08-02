---
title: Local Development Environment
description: Reproducible Docker and native PaginiumCMS setup with safe bootstrap and troubleshooting
icon: material/docker
---

# Local Development Environment

> This guide targets the **`v2.1.0-beta.*`** release family. Always verify exact runtime versions, ports, and scripts in the concrete tag's `composer.json`, `package.json`, `docker-compose.yml`, `.env.example`, and CI.

## 1. Supported workflows

| Profile | Good for | Not intended for |
|---|---|---|
| Docker Compose | fast clone-to-login, beta smoke, isolated dependencies | pretending to be full production HA |
| Native backend + Vite | daily PHP/React development and debugging | an Internet-facing production deployment |
| Local production build | verifying nginx/PHP-FPM and a static SPA bundle | editing through Vite HMR |

## 2. Prerequisites

Recommended host tools:

- Git,
- Docker Engine + Compose plugin for the container profile,
- or PHP 8.5+, Composer 2, and Node.js 22+ for native development,
- `curl`, `unzip`, and a shell compatible with project scripts,
- enough space for `vendor`, `node_modules`, storage, test temp, and builds.

Verification:

```bash
git --version
docker version
docker compose version
php -v
composer --version
node --version
npm --version
```

No SQL server is required. Redis is an optional future/derived capability, not a condition for baseline local development.

## 3. Clone and safe first-run

```bash
git clone <repository-url> paginiumcms
cd paginiumcms
git switch <release-tag-or-branch>
chmod +x scripts/first-run.sh
```

Set a custom bootstrap account before execution:

```bash
export FIRST_ADMIN_EMAIL='developer@example.test'
export FIRST_ADMIN_NAME='Local Developer'
export FIRST_ADMIN_PASSWORD='Use-A-Unique-Strong-Password-Here'
./scripts/first-run.sh
```

When the concrete artifact supports frontend installation through first-run:

```bash
INSTALL_FRONTEND=1 ./scripts/first-run.sh
```

Never reuse a bootstrap password from a screenshot or old guide on a public instance. Change it after the first login and test 2FA for staff workflows.

## 4. What first-run should do

The concrete script is authoritative. Expected safe contract:

1. create `.env` from the example only when missing,
2. generate a real `APP_KEY` when not configured,
3. create the allowed storage tree,
4. install backend dependencies,
5. create the first `SUPER_ADMIN` only when no user exists,
6. run content diagnostics/rebuild of derived layers,
7. optionally install frontend dependencies,
8. avoid writing secrets to persistent logs.

Do not change `APP_KEY` after encrypted data exists. Losing the key can mean losing TOTP/settings secrets.

## 5. Docker Compose profile

Typical backend start:

```bash
docker compose up -d
docker compose ps
docker compose logs --tail=100 nginx php
curl --fail --silent http://127.0.0.1:8080/api/health
```

Backend plus development frontend when the compose file defines a `dev` profile:

```bash
docker compose --profile dev up -d
curl --fail --silent http://127.0.0.1:3025/
```

Production frontend build when available:

```bash
docker compose --profile build run --rm frontend-build
```

Ports `8080` and `3025` are conventions of the source snapshot, not permanent guarantees. Resolve conflicts with a local override rather than changing tracked compose solely for one workstation.

### Local override

Example untracked `docker-compose.override.yml`:

```yaml
services:
  nginx:
    ports:
      - "18080:80"
  frontend:
    ports:
      - "13025:3025"
```

Then:

```bash
docker compose up -d
curl http://127.0.0.1:18080/api/health
```

## 6. Native development

Backend dependencies and bootstrap:

```bash
composer install
./scripts/first-run.sh
```

Terminal 1 — backend development server:

```bash
cd backend/public
php -S 127.0.0.1:8080
```

Terminal 2 — frontend:

```bash
cd frontend
npm ci
npm run dev
```

The Vite proxy should route `/api` to the local backend according to current configuration. `php -S` and Vite are development servers only; do not use them as public production infrastructure.

## 7. Minimal local `.env`

Key groups:

```dotenv
APP_ENV=development
APP_DEBUG=true
APP_KEY=<generated-secret>
DEMO_MODE=false
TWO_FACTOR_REQUIRED=false
SESSION_STRICT=false
```

Notes:

- `APP_DEBUG=true` only locally,
- `TWO_FACTOR_REQUIRED=false` is a development exception; staff production should require 2FA,
- `SESSION_STRICT=false` may help with a local reverse proxy, but is not automatically a secure production profile,
- use `DEMO_MODE=true` only in an isolated demo instance and never with real data,
- never commit `.env`.

Verify exact names and defaults in `.env.example`.

## 8. Health and smoke test

After startup:

```bash
curl -i http://127.0.0.1:8080/api/health
curl -i http://127.0.0.1:8080/api/settings/public
```

Manually verify:

1. bootstrap account login,
2. dashboard without `500`,
3. CSRF token acquisition and one safe mutation,
4. creation and reload of a draft page,
5. logout and rejection of a protected endpoint,
6. logs without secrets or unexpected stack traces.

## 9. Quality gate on a local clone

Backend:

```bash
composer test
composer stan
composer cs
composer audit
```

Frontend:

```bash
cd frontend
npm ci
npm run type-check
npm run lint
npm run lint:api-barrel
npm test -- --run
npm run build
npm audit --audit-level=critical
```

Project gate:

```bash
cd ..
./scripts/iteration-gate.sh
```

Run the baseline before the first change. Otherwise you cannot know whether a failure came from your work or was already present in the branch.

## 10. Content diagnostics

For empty content, a broken index, or after a migration test:

```bash
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --json
php backend/bin/console content:diagnose --fix
```

Use `--fix` only after reading diagnostics and, for important local data, creating a backup. Index/cache are rebuildable; do not delete authoritative content as the first troubleshooting step.

## 11. Resetting the local environment

Decide what you intend to reset:

| Target | Safe procedure |
|---|---|
| Containers | `docker compose down` |
| Containers + ephemeral volumes | `docker compose down -v` only when the volume contains no needed SSOT |
| Frontend dependencies | remove `frontend/node_modules`, then `npm ci` |
| Backend dependencies | remove `vendor`, then `composer install` |
| Index/cache | use the diagnostic/rebuild command |
| All local content | manually, only after backup and path verification |

Do not put `rm -rf backend/storage` into a generic “fix everything” alias. Such aliases have a suspicious talent for running in exactly the wrong terminal.

## 12. Common problems

### Port already in use

```bash
ss -ltnp | grep -E ':8080|:3025'
```

Use a local compose override or a different Vite port.

### `401` after a successful login request

Check cookie domain/path, `SameSite`, origin, proxy, session volume, and system time. With split FE/API ports, verify CORS/credentials and CSRF flow.

### `403` on a mutation

Distinguish:

- missing or stale CSRF token,
- role/permission denial,
- Path ACL denial,
- WAF/reverse proxy block.

Check response `content-type` and body first; it may not be JSON.

### Storage is not writable

```bash
ls -ld backend/storage backend/storage/*
id
```

In Docker, verify container UID/GID. Correct ownership and the smallest required mode, not `777`.

### Frontend cannot call the API

Check the Vite proxy, `VITE_API_URL`, same-origin assumptions, and the backend bind address/port.

### Plugin frontend did not appear after import

PHP runtime import/activation and the Vite frontend bundle are separate. Frontend plugin source may require rebuild and redeployment.

## 13. Local security

- do not use real production secrets,
- point test webhooks and OAuth to local/fake providers,
- do not expose development ports to the WAN,
- avoid binding to `0.0.0.0` without a reason,
- redact logs before attaching them to an issue,
- do not keep a production backup on a laptop without encryption and a valid reason,
- run dependency audits regularly.

## 14. Next steps

After successful clone-to-login:

1. [DEVELOPMENT.md](DEVELOPMENT.md) — daily workflow,
2. [CODING_STANDARDS.md](CODING_STANDARDS.md) — code rules,
3. [TESTING.md](TESTING.md) — test architecture,
4. [CONTRIBUTING.md](CONTRIBUTING.md) — PR contract,
5. [BETA_INFRA.md](BETA_INFRA.md) — clean-clone and release readiness.
