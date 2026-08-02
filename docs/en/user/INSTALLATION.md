---
title: Installation
description: Safe PaginiumCMS deployment with Docker, a native PHP stack, or a production reverse proxy
icon: material/server
---

# Installing PaginiumCMS

> **Goal:** run the backend API, production frontend, and authoritative flat-file storage without creating an SQL database. This guide targets the **`v2.1.0-beta.*`** release family.

## 1. Choose a deployment profile

| Profile | Use | Note |
|---|---|---|
| Docker / local beta | quick smoke testing and isolated evaluation | fewest host dependencies |
| Native development | backend and frontend development | PHP and Vite dev servers are not production web servers |
| Production single-node | nginx/Apache + PHP-FPM + static frontend | recommended base for a self-hosted VPS |
| Hybrid / Git-headless | future or partial It.68–77 profile | enable only capabilities confirmed by release notes |

## 2. Requirements

Verify exact limits in `composer.json`, `package.json`, `.env.example`, and the release artifact. Current beta documentation assumes:

| Component | Requirement |
|---|---|
| PHP | 8.5+ with `json`, `mbstring`, `zip`, `curl`, `fileinfo`, and `openssl`/`sodium` as required by the build |
| Composer | 2.x |
| Node.js | 22+ only when building the frontend locally |
| Web server | nginx or Apache with PHP-FPM in production |
| Disk | writable storage paths and capacity for versions, media, logs, and backups |
| TLS | HTTPS for every internet-facing or staff deployment |

PaginiumCMS does not use SQL as authoritative storage. Redis, an index, or a future external media driver are optional derived capabilities and must never hold the only copy of content.

## 3. Before installation

1. Download or check out an exact release tag.
2. Verify the checksum/signature when published with the release.
3. Confirm that the archive contains no foreign `.env`, runtime logs, or user data.
4. Decide where authoritative data and backups will live.
5. Back up an existing instance before upgrading; never run first-run blindly over production data.

## 4. Option A — Docker

Typical beta start:

```bash
unzip paginiumcms-beta.zip
cd paginiumcms
chmod +x scripts/first-run.sh
./scripts/first-run.sh
docker compose up -d
curl --fail --silent http://127.0.0.1:8080/api/health
```

Run a development frontend only through a profile declared by the concrete `docker-compose.yml`, for example:

```bash
docker compose --profile dev up -d
```

Ports may change between release artifacts. Treat Compose mappings and `.env.example` as authoritative, not an old screenshot or a hardcoded URL in an issue.

## 5. Option B — native development

```bash
composer install
chmod +x scripts/first-run.sh
./scripts/first-run.sh
```

Backend development server:

```bash
php -S 127.0.0.1:8080 -t backend/public
```

Frontend:

```bash
cd frontend
npm ci
npm run dev
```

Do not expose `php -S` or the Vite dev server as an internet-facing production stack. They do not replace proper TLS, PHP-FPM process management, upload limits, security headers, and reverse-proxy rules.

## 6. Production build

Backend dependencies:

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

Frontend:

```bash
cd frontend
npm ci
npm run build:prod
```

The production web server must:

- serve frontend `dist/` as an SPA with an `index.html` fallback,
- route `/api` to the PHP front controller,
- expose only supported media/storage URLs,
- route feed, sitemap, and robots endpoints according to the release contract,
- block direct web access to `data`, logs, backups, versions, secrets, and source files.

See [deployment documentation](../deploy/NGINX_API.md) and the [Storage contract](../architecture/STORAGE.md).

## 7. First-run and bootstrap account

Depending on the release, `first-run.sh` may:

- create `.env` from `.env.example`,
- generate `APP_KEY`,
- prepare storage directories,
- create a bootstrap administrator,
- run diagnostics and safe migrations.

Pass custom bootstrap data through supported environment variables:

```bash
export FIRST_ADMIN_EMAIL='admin@example.test'
export FIRST_ADMIN_PASSWORD='replace-with-a-unique-password'
export FIRST_ADMIN_NAME='Primary administrator'
./scripts/first-run.sh
unset FIRST_ADMIN_PASSWORD
```

Do not leave the exported password in shell history or CI logs. When a release creates a known development password, replace it during the first login before exposing the host.

## 8. Critical `.env` variables

| Variable | Production rule |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | canonical HTTPS URL |
| `APP_KEY` | generate securely, back up as a secret, and do not rotate after encrypted data exists without migration |
| `TRUSTED_PROXIES` | only real proxy addresses/CIDRs supported by the implementation |
| `TWO_FACTOR_REQUIRED` | enable for staff according to policy |
| `DEMO_MODE` | `false` outside the dedicated demo instance |
| session/cookie settings | Secure, HttpOnly, and an appropriate SameSite profile for the topology |

Never copy `.env` into a public ZIP or screenshot.

## 9. File permissions

The web/PHP user needs write access only to approved runtime and storage paths. Source code and configuration should remain read-only wherever runtime writing is not required.

A typical model uses a deployment user as owner, the PHP-FPM group, and group-write permissions on selected directories. `chmod -R 777` is a security defect, not a fix.

## 10. Cron and workers

Scheduled publishing, backups, notifications, or queue jobs work only when required by the concrete release and when workers are configured. Typical example:

```cron
* * * * * cd /var/www/paginiumcms && php backend/bin/console scheduler:run >/dev/null 2>&1
* * * * * cd /var/www/paginiumcms && php backend/bin/console worker:process >/dev/null 2>&1
```

Do not copy commands blindly. Verify them with `php backend/bin/console list`, deployment docs, and logs. Prevent concurrent execution when a command has no internal lock.

## 11. Post-deployment verification

```bash
curl --fail --show-error https://cms.example.test/api/health
php backend/bin/console content:diagnose
```

Then verify:

- login and CSRF/session flow,
- mandatory 2FA,
- draft creation and publication of test content,
- media upload and public rendering,
- audit and request-log writes,
- backup creation and readability,
- 404/403 responses for forbidden storage paths,
- cron/worker heartbeat when enabled.

## 12. Upgrade and rollback

1. read release notes and breaking changes,
2. create a consistent backup of content, settings, keys, and extension data,
3. stop write traffic or enable maintenance,
4. deploy new code outside the active release directory,
5. run supported migrations/diagnostics,
6. build the frontend from the same release,
7. perform a smoke test,
8. only then switch the symlink or traffic.

A code rollback may be unsafe after a data migration. Restore storage only from a verified compatible backup.

## 13. Troubleshooting

| Symptom | Check |
|---|---|
| `/api/*` returns HTML | broken proxy or SPA fallback intercepted the API |
| login ends with 401/CSRF | cookie domain/SameSite/Secure, proxy scheme, session storage |
| public images return 404 | media route/proxy and allowed public path |
| empty lists or index failure | `content:diagnose`, permissions, authoritative files; cache/index are rebuildable |
| writes fail with Permission denied | owner/group of runtime paths; do not fix with 777 |
| wrong client IP in logs or WAF | `TRUSTED_PROXIES` and proxy headers |
| scheduled jobs do not run | cron, worker, lock, working directory, and PHP binary |

After a successful installation continue with [First steps](FIRST_STEPS.md).
