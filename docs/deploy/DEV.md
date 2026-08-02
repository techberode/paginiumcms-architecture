---
title: Local and LAN development
description: Development backend, Vite frontend, Docker alternative, LAN proxy, environment reset, and security boundaries
icon: material/laptop
---

# Local and LAN development

> PHP and Vite development servers are intended only for local or trusted LAN environments. They are not production web servers or public sandboxes.

## 1. Prerequisites

The reference development stack uses:

- PHP 8.5+ and Composer,
- Node.js according to `frontend/package.json` and its lockfile,
- Git,
- optional Docker Compose,
- writable test storage,
- separate test secrets and accounts.

Verify the runtime first:

```bash
php -v
composer --version
node --version
npm --version
git status --short
```

Versions in the release tag and CI take precedence over general documentation.

## 2. Clean clone setup

```bash
git clone <repository-url> paginiumcms
cd paginiumcms
composer install
cp .env.example .env

cd frontend
npm ci
cd ..
```

Use development-only secrets. Do not copy production `.env`, production `APP_KEY`, SMTP passwords, or OAuth tokens into development.

## 3. Backend development server

Reference command retained from the source documentation:

```bash
cd backend/public
php -S 127.0.0.1:8080
```

For LAN testing the backend may bind to `0.0.0.0:8080`, but only behind a host firewall on a trusted network:

```bash
php -S 0.0.0.0:8080
```

The built-in PHP server:

- does not provide production TLS,
- is not a production process manager,
- must not be Internet-facing,
- can behave differently from nginx plus container/FPM,
- is suitable for rapid development, not release acceptance.

## 4. Vite frontend

```bash
cd frontend
npm run dev
```

The frontend uses a proxy for `/api` to preserve a same-origin development flow. Verify the exact port and proxy target in the Vite configuration of the current commit.

Retained LAN mode:

```bash
npm run dev:lan
```

Typical topology:

```text
browser → LAN nginx :8081
nginx /api → PHP :8080
nginx / → Vite :3025 + HMR WebSocket
```

## 5. Development `.env`

Example for a trusted LAN profile:

```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://192.168.10.26:8081
DEVELOPER_MODE=true
DEV_UNLOCK_SECRET=replace-with-local-random-value
CORS_ALLOWED_ORIGINS=http://192.168.10.26:8081,http://192.168.10.20:3025
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

`TRUSTED_PROXIES` is not a list of clients. It contains only proxies trusted to supply `X-Forwarded-*` headers.

Restart the backend after changing `.env`. Vite variables are loaded at startup/build time, not dynamically at runtime.

## 6. Docker development alternative

When the repository contains a base `docker-compose.yml`, use the version from the exact tag:

```bash
docker compose config --quiet
docker compose up -d --build
docker compose ps
```

A development bind mount improves iteration speed but is not proof of an immutable production artifact. For diagnostics compare:

```bash
docker compose exec -T php php -v
docker compose exec -T php pwd
docker compose exec -T php id
```

## 7. CLI and scheduler

From the repository root:

```bash
php backend/bin/console content:diagnose --json
php backend/bin/console scheduler:run
php backend/bin/console worker:process
```

Under Docker, run CLI inside the container when host PHP or user identity differs from the web runtime:

```bash
docker compose exec -T -u www-data php \
  php backend/bin/console content:diagnose --json
```

## 8. Testing workflow

Fast local cycle:

```bash
vendor/bin/phpunit <target>
cd frontend && npm test -- <target>
```

Before a pull request or release candidate run the documented full gate. The complete local log is stored outside the project. CI must display only sanitized output without TOTP secrets, QR payloads, tokens, or passwords.

## 9. Static LAN test

`nginx-paginium-test.conf` serves the production `dist` on an HTTP LAN port and proxies the API:

```bash
cd frontend
npm ci
npm run build:prod
```

Deploy `dist` using the local script or atomically into:

```text
/var/www/paginium-test/dist
```

Smoke:

```bash
curl -fsS http://192.168.10.26:8081/api/health
curl -fsSI http://192.168.10.26:8081/feed.xml
```

This profile is closer to production than Vite, but without TLS it still does not validate HSTS, secure cookies, or HTTPS-only behavior.

## 10. LAN HMR proxy

`nginx-paginium-dev.conf` exposes one `:8081` URL while nginx proxies both React/Vite and the API. Benefits:

- the browser uses one origin,
- HMR WebSocket passes through nginx,
- the API is not tested through a manually opened second origin,
- routing more closely resembles production.

Do not expose this vhost to the WAN. HMR and Developer Mode provide capabilities unsuitable for a public network.

## 11. Session, CORS, and proxy behavior

During HTTP LAN testing the application must not set a `Secure` cookie unless the request is HTTPS. When nginx terminates HTTPS, it must send the correct `X-Forwarded-Proto`, and the backend must trust only that proxy.

Diagnostics:

```bash
curl -i http://192.168.10.26:8081/api/health
curl -i -X OPTIONS http://192.168.10.26:8081/api/auth/login \
  -H 'Origin: http://192.168.10.26:8081' \
  -H 'Access-Control-Request-Method: POST'
```

Do not solve credentialed admin CORS with `*`.

## 12. Safe development reset

Distinguish:

```text
cache/index rebuild
≠ dependency reinstall
≠ test storage reset
≠ deletion of user content SSOT
```

Before a destructive reset print the exact path and verify that it belongs to the test environment. Do not use wildcard cleanup by a generic email domain against production data.

## 13. Troubleshooting

| Symptom | Likely cause | Check |
|---|---|---|
| nginx `502` | backend is not listening on LAN address/port | `ss -ltnp`, curl from nginx host |
| SPA returns HTML for `/api` | missing or incorrectly ordered `/api/` location | [NGINX_API.md](./NGINX_API.md) |
| login 401 with empty body | APP_URL/CORS/proxy mismatch | Origin test and backend log |
| Developer unlock 403 | flag exists only on frontend host | backend `.env` and restart |
| HMR cannot connect | WebSocket headers/clientPort | nginx dev config and Vite config |
| permission denied | host/container identity mismatch | `id`, mount, setgid permissions |
| stale frontend | build or cache issue | asset hashes, hard reload |

## 14. Related documents

- [LOCAL_SETUP.md](../developer/LOCAL_SETUP.md)
- [DEVELOPMENT.md](../developer/DEVELOPMENT.md)
- [TESTING.md](../developer/TESTING.md)
- [NGINX_API.md](./NGINX_API.md)
- [DEPLOY.md](./DEPLOY.md)
