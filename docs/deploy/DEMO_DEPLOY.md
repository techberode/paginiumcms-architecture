---
title: Demo instance
description: Isolated demo deployment, public login, CORS, storage bootstrap, automatic reset, smoke tests, and security boundaries
icon: material/test-tube
---

# Demo instance

> Demo is a publicly resettable presentation profile. It is not staging with production data and must not share production storage, secrets, Git credentials, or backup destinations.

## 1. Reference architecture

```text
https://demo.paginiumcms.com
→ host nginx (demo vhost, frontend/dist)
→ 127.0.0.1:8091
→ separate Docker Compose project
→ /var/www/paginiumcms-demo
→ isolated demo storage
```

The retained stack uses:

```text
APP_ROOT=/var/www/paginiumcms-demo
COMPOSE_PROJECT_NAME=paginiumcms-demo
BACKEND_PORT=8091
DEMO_MODE=true
```

## 2. Security model

The demo must assume that a visitor:

- knows the published demo credentials,
- can change content within the allowed demo scope,
- will try unusual requests and uploads,
- may leave toxic or sensitive content,
- can race the automatic reset.

Therefore the demo:

- does not use production OAuth/SMTP/Git/S3/AI credentials,
- has no production backups or private media,
- disables Developer Mode and Code Editor,
- has no admin-triggered code deployment,
- uses rate limiting, WAF, logging, and periodic reset,
- clearly states that data can be deleted.

## 3. Public demo account

The source documentation uses a seeded `demo@paginiumcms.com` account and a public demo password. This is an intentionally public identity for demo storage only. Never reuse the password in production, staging, or a personal account.

If implementation moves to runtime-generated or environment credentials, documentation and the public demo info endpoint must change together. The API must not return the password or secret.

## 4. Required `.env`

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.paginiumcms.com

VITE_DEBUG=false
VITE_API_URL=
VITE_PUBLIC_URL=https://demo.paginiumcms.com

DEVELOPER_MODE=false
DEV_UNLOCK_SECRET=

DEMO_MODE=true
DEMO_PUBLIC_URL=https://demo.paginiumcms.com
DEMO_AUTO_RESET_MINUTES=60

TRUSTED_PROXIES=127.0.0.1,::1
SESSION_LIFETIME=14400
SESSION_STRICT=false
```

Adapt `TRUSTED_PROXIES` to actual proxy hops. The template is a reference, not Docker-network auto-discovery.

## 5. First-run storage bootstrap

After a clean clone or complete reset, the demo data tree must exist before the first request. Start with diagnostics:

```bash
cd /var/www/paginiumcms-demo
/var/lib/docker/compose/paginiumcms-demo/stack.sh \
  exec -T -u www-data php \
  php backend/bin/console content:diagnose --json
```

Then run the supported seed/reset command for the exact release:

```bash
/var/lib/docker/compose/paginiumcms-demo/stack.sh \
  exec -T -u www-data php \
  php backend/bin/console demo:reset-if-due
```

When health and login return `500` and the demo data tree is missing, follow [ISS-102](../ISSUES.md#iss-102). Do not invent empty JSON files without the proper schema.

## 6. Deploying a demo release

```bash
APP_ROOT=/var/www/paginiumcms-demo
STACK_DIR=/var/lib/docker/compose/paginiumcms-demo
RELEASE_REF=v2.1.0-beta.23

cd "$APP_ROOT"
git fetch origin --tags --prune
git checkout --detach "$RELEASE_REF"

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
cd frontend && npm ci && npm run build:prod && cd ..

"$STACK_DIR/stack.sh" config --quiet
"$STACK_DIR/stack.sh" up -d --build
```

Run seed/reset after deployment when needed. Demo reset is a data operation, not a substitute for code deployment.

## 7. CORS and [ISS-098](../ISSUES.md#iss-098)

Typical symptom:

```text
curl without Origin → login endpoint responds
browser with Origin → 401, empty body, or text/html
```

First verify:

```bash
grep -E '^(APP_URL|DEMO_PUBLIC_URL)=' /var/www/paginiumcms-demo/.env
```

Required:

```text
APP_URL=https://demo.paginiumcms.com
```

Browser-like smoke:

```bash
curl -i -X POST https://demo.paginiumcms.com/api/auth/login \
  -H 'Origin: https://demo.paginiumcms.com' \
  -H 'Content-Type: application/json' \
  -d '{"email":"invalid@example.com","password":"invalid"}'
```

The important result is a correct JSON response and CORS behavior, not successful login with invalid credentials.

## 8. Mandatory smoke tests

```bash
curl -fsS https://demo.paginiumcms.com/api/health
curl -fsSI https://demo.paginiumcms.com/
curl -fsSI https://demo.paginiumcms.com/.well-known/security.txt
curl -fsSI https://demo.paginiumcms.com/feed.xml
```

Also verify:

- public demo info without a password,
- quick login only when demo mode is enabled,
- creation/editing of allowed demo content,
- disabled Developer Mode and code deployment,
- seed reset,
- next-reset indication,
- account access limited to demo data.

## 9. Automatic reset

Reference cron:

```cron
*/15 * * * * flock -n /run/lock/paginium-demo-reset.lock /var/lib/docker/compose/paginiumcms-demo/stack.sh exec -T -u www-data php php backend/bin/console demo:reset-if-due >> /var/log/paginiumcms/demo-reset.log 2>&1
```

`DEMO_AUTO_RESET_MINUTES=60` defines the due interval; cron only checks periodically. Reset must be idempotent, audited, and safe when racing a user request.

## 10. Storage permissions and [ISS-099](../ISSUES.md#iss-099)

When reset fails with `Permission denied`, inspect the user, group ownership, setgid directories, and mount:

```bash
cd /var/www/paginiumcms-demo
find backend/storage -maxdepth 4 -printf '%M %u:%g %p\n' | head -80
/var/lib/docker/compose/paginiumcms-demo/stack.sh exec -T php id
```

Reference repair:

```bash
sudo chown -R deploy-user:www-data backend/storage
sudo find backend/storage -type d -exec chmod 2775 {} \;
sudo find backend/storage -type f -exec chmod 0664 {} \;
```

Then verify writes inside the container as `www-data`. Do not use `777` or change ownership of application code without a reason.

## 11. Production separation

Demo and production require separate:

- checkouts,
- Compose project names and host ports,
- `.env` and APP_KEY,
- storage/data/media/logs/backups,
- scheduler locks and logs,
- cookies/domains,
- OAuth redirect URIs and webhooks, when enabled at all.

Check:

```bash
/var/lib/docker/compose/paginiumcms/stack.sh ps
/var/lib/docker/compose/paginiumcms-demo/stack.sh ps
```

A shared bind mount between production and demo is a release blocker.

## 12. Demo content and privacy

The demo UI must tell users:

- not to submit personal or confidential data,
- content is automatically deleted,
- demo is not intended for production operation,
- activity may be logged for security.

Reset must remove test accounts, comments, uploads, drafts, jobs, and other demo artifacts according to the release schema. Its summary should show before/after counts for every category.

## 13. What does not help

- disabling CORS without understanding Origin flow,
- `Access-Control-Allow-Origin: *` with credentialed sessions,
- sharing production `.env`,
- running reset as root while web runs as another user without shared permissions,
- creating missing JSON with `echo '{}'` without schema,
- resetting data without a lock during writes,
- enabling Developer Mode on the public demo vhost.

## 14. Incident and recovery

When demo is abused:

1. enable maintenance or disable the demo vhost,
2. preserve relevant sanitized audit/log evidence,
3. rotate any credentials that may have been exposed,
4. discard demo storage and restore a clean seed,
5. verify separation from production,
6. run smoke and security tests,
7. record the incident and corrective action.

## 15. Related documents

- [DEPLOY.md](./DEPLOY.md)
- [NGINX_API.md](./NGINX_API.md)
- [CRON.md](./CRON.md)
- [BETA_TESTER.md](../user/BETA_TESTER.md)
- [SECURITY.md](../developer/SECURITY.md)
