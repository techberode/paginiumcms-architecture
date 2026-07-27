# Production & demo deploy guide

> **Scope:** updating a running PaginiumCMS instance after a new commit or release tag.  
> **Not covered here:** first-time server setup (see `PRIVATE_DOMAIN_DEPLOY.md` on the server, gitignored) and [CRON.md](./CRON.md).

---

## Architecture (two instances)

| | **Production** | **Demo** |
|---|----------------|----------|
| Domain | `paginiumcms.com` | `demo.paginiumcms.com` |
| App root (typical) | `/var/www/paginiumcms.com` | `/var/www/paginiumcms-demo` |
| Docker stack dir | `/var/lib/docker/compose/paginiumcms` | `/var/lib/docker/compose/paginiumcms-demo` |
| PHP upstream (host nginx) | `127.0.0.1:8089` | `127.0.0.1:8091` |
| `DEMO_MODE` | `false` | `true` |
| Admin code deploy | ✅ planned (see §5) | ❌ SSH only (by design) |

Both instances share the same repo layout: git clone → `composer` → `frontend/dist` → Docker PHP via `stack.sh`.

**Demo-specific guide (login, CORS ISS-098, smoke tests):** [DEMO_DEPLOY.md](DEMO_DEPLOY.md)

---

## What is **not** a code deploy

Admin **GitHub** (`/github`) syncs **flat-file content** (pages, settings export) via `GitHubService` — export / import / sync of CMS data, **not** `git pull` of the application.

Do not use `/github` to upgrade PHP or React bundles.

---

## A. Regular commit deploy (production)

Use after every merge/push to `main` that should go live (e.g. `03ef2a0` — It.61).

### Preconditions

- Changes are on `origin/main` (pushed from dev machine).
- `./scripts/iteration-gate.sh` was green before push (mandatory project rule).

### On the server (SSH)

```bash
APP_ROOT=/var/www/paginiumcms.com
STACK_DIR=/var/lib/docker/compose/paginiumcms
BACKEND_PORT=8089

cd "$APP_ROOT"

# 1) Code
git fetch origin
git checkout main
git pull origin main
git log -1 --oneline   # verify expected commit

# 2) Backend deps
composer install --no-dev --optimize-autoloader

# 3) Frontend (required when FE/admin/public UI changed)
cd frontend
npm ci
npm run build:prod
cd ..

# 4) Restart PHP (OPcache + new PHP files)
"$STACK_DIR/stack.sh" restart php

# 5) Wait — 502 immediately after restart is normal (ISS-096)
sleep 8
curl -sf "http://127.0.0.1:${BACKEND_PORT}/api/health" | jq .

# 6) Optional diagnostics
php backend/bin/console content:diagnose --json | jq '.jobsDirWritable, .issues'
```

### Smoke (2 min)

- `https://paginiumcms.com/api/health` → 200
- Admin login → dashboard loads
- If iteration touched a feature — quick manual check (see release notes / `ITERATION_*.md`)

### Rollback (commit deploy)

```bash
cd "$APP_ROOT"
git log -5 --oneline
git checkout <previous-sha>
composer install --no-dev --optimize-autoloader
cd frontend && npm ci && npm run build:prod && cd ..
"$STACK_DIR/stack.sh" restart php
```

---

## B. Release deploy (tagged update)

A **release** = green gate + git tag + GitHub Release notes + production deploy + smoke.  
Use for beta milestones (`v2.1.0-beta.10`, …), not for every small commit unless you policy-tag often.

### B1. Developer machine (before server)

```bash
# 1) Gate (mandatory)
./scripts/iteration-gate.sh

# 2) Tag (example beta.10 after It.13 v3)
git tag -a v2.1.0-beta.10 -m "v2.1.0-beta.10 — It.13 v3 demo sandbox full trial"
git push origin v2.1.0-beta.10

# 3) GitHub Release (copy body from docs/developer/RELEASE.md § beta.10)
gh release create v2.1.0-beta.10 --title "v2.1.0-beta.10 — Demo sandbox full trial (It.13 v3)" --notes-file /tmp/release-notes.md
```

Update `CHANGELOG.md`, `docs/CONTINUATION.md`, and `docs/developer/RELEASE.md` in the same wave (or immediately before tag).

### B2. Production server (checkout tag)

Same as **§A**, but pin the ref:

```bash
cd /var/www/paginiumcms.com
git fetch origin --tags
git checkout v2.1.0-beta.10    # detached HEAD is OK on servers
composer install --no-dev --optimize-autoloader
cd frontend && npm ci && npm run build:prod && cd ..
/var/lib/docker/compose/paginiumcms/stack.sh restart php
sleep 8 && curl -sf http://127.0.0.1:8089/api/health
```

To return to tracking `main` later:

```bash
git checkout main && git pull origin main
```

### B3. Release smoke checklist

Use the checklist in `docs/developer/RELEASE.md` for that version, plus:

- [ ] `/api/health` 200
- [ ] Cron still runs (`scheduler:run` in crontab unchanged)
- [ ] Admin `/scheduler` — manual job run → outcome badge (not red 500 toast)
- [ ] New features from release notes

---

## C. Demo instance deploy (SSH only)

Demo **does not** need admin-triggered upgrades. Reset + seed is the main ops path; code updates are occasional.

```bash
APP_ROOT=/var/www/paginiumcms-demo
STACK_DIR=/var/lib/docker/compose/paginiumcms-demo
BACKEND_PORT=8091

cd "$APP_ROOT"
git fetch origin
git checkout main && git pull origin main
# or: git checkout v2.1.0-beta.10

composer install --no-dev --optimize-autoloader
cd frontend && npm ci && npm run build:prod && cd ..
"$STACK_DIR/stack.sh" restart php
sleep 8 && curl -sf "http://127.0.0.1:${BACKEND_PORT}/api/health"
```

Ensure demo env (`DEMO_MODE=true`, `APP_URL=https://demo.paginiumcms.com`) — see [ITERATION_13.md](../ITERATION_13.md), [DEMO_DEPLOY.md](DEMO_DEPLOY.md), and `docs/deploy/app.env.demo.example`.

**Login smoke (browser vs curl):** curl without `Origin` can succeed while the browser gets **401 with empty body**. That is CORS — fix `APP_URL` to the demo domain, then restart PHP:

```bash
grep -E '^APP_URL=' "$APP_ROOT/.env"
curl -sS -o /dev/null -w 'with Origin: HTTP %{http_code}\n' \
  -X POST "https://demo.paginiumcms.com/api/auth/login" \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://demo.paginiumcms.com' \
  -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}'
# Expect: HTTP 200 (or 401 JSON with wrong password — NOT empty 401)
```

After deploy: Admin → **Demo** → reset seed if you need a clean showcase.

---

## D. Helper script (optional)

From repo root **on the server**:

```bash
export APP_ROOT=/var/www/paginiumcms.com
export STACK_DIR=/var/lib/docker/compose/paginiumcms
export BACKEND_PORT=8089
export GIT_REF=origin/main          # or v2.1.0-beta.9

./scripts/deploy-instance-update.sh
```

See [scripts/deploy-instance-update.sh](../../scripts/deploy-instance-update.sh).

---

## E. Local / LAN frontend-only deploy

When PHP already runs on the server and you only need to push a new `frontend/dist` from your laptop:

```bash
DEPLOY_HOST=… DEPLOY_USER=… DEPLOY_PATH=/var/www/paginiumcms.com/frontend/dist/ \
  ./scripts/deploy-frontend-lan.sh
```

Full stack update still requires **§A** on the server (composer + PHP restart).

---

## F. Troubleshooting

| Symptom | Likely cause | Action |
|---------|--------------|--------|
| 502 right after restart | PHP container starting (ISS-096) | Wait 5–10 s, retry health |
| 502 persists | PHP crash / parse error | `stack.sh logs --tail=50 php` |
| Admin shows old UI | Stale `dist/` or browser cache | Re-run `npm run build:prod`, hard refresh |
| Demo login 401, empty response in DevTools | CORS — `APP_URL` ≠ public domain | Set `APP_URL=https://demo.paginiumcms.com`, `stack.sh restart php`; verify curl with `Origin` header (§C) |
| `demo:reset-if-due` Permission denied (`plugins.json`) | Host CLI/cron vs Docker `www-data` on demo storage | `chown user:www-data`, dirs `2775` — [DEMO_DEPLOY.md](DEMO_DEPLOY.md) § ISS-099 |
| `git pull` → already up to date | Commit not pushed | Push from dev, then fetch on server |

---

## G. Planned: Admin “System update” (production only)

**Goal:** SUPER_ADMIN triggers deploy from admin UI, backed by GitHub — **without** shell access for routine updates.

**Not in v1:** demo instance upgrade via admin (demo stays SSH + reset workflow).

### G1. UI (admin SPA)

New module: **Platform → System update** (`/platform/update`), SUPER_ADMIN only.

| Block | Content |
|-------|---------|
| Current version | `composer.json` version + `git describe` / `APP_VERSION` file written at deploy |
| Remote | Latest tag / latest `main` SHA from GitHub API |
| Changelog | Markdown snippet from release body (cached) |
| Actions | **Check for updates** · **Deploy latest main** · **Deploy tag …** (dropdown) |
| Log | Last 20 lines of deploy job (SSE or poll) |

### G2. Backend (new, not `GitHubService` content sync)

| Endpoint | Purpose |
|----------|---------|
| `GET /api/admin/system/update/status` | current ref, remote ref, pending, last job |
| `POST /api/admin/system/update/check` | fetch GitHub compare (token from settings) |
| `POST /api/admin/system/update/run` | enqueue deploy job `{ ref: "main" \| "v2.1.0-beta.9" }` |

Security baseline:

- `AuthMiddleware` + `RoleMiddleware(['SUPER_ADMIN'])` + `TwoFactorMiddleware`
- CSRF on POST
- Deploy token in `SettingsSchema` as `password` (encrypted at rest)
- No user-controlled shell — whitelist script only
- Audit log entry on every run

### G3. Execution model (recommended)

**Do not** run `git pull` inside the web request.

1. Admin POST → write job to flat-file queue (`data/jobs/` or dedicated `data/deploy/runs.json`)
2. CLI worker or cron invokes:

   ```bash
   php backend/bin/console system:deploy --ref=origin/main
   ```

3. Command runs **only** `/usr/local/bin/paginium-deploy-update` (root-owned wrapper) with fixed env:

   ```bash
   APP_ROOT=/var/www/paginiumcms.com STACK_DIR=… BACKEND_PORT=8089 GIT_REF=… \
     /path/to/repo/scripts/deploy-instance-update.sh
   ```

4. Job outcome: `completed` | `failed` | `skipped` (same pattern as It.62 scheduler)

Host hardening: wrapper in `sudoers` for `www-data` → script only, no arbitrary commands.

### G4. GitHub integration

| Setting (`settings` group `systemUpdate`) | Purpose |
|-------------------------------------------|---------|
| `githubOwner` / `githubRepo` | `techberode/paginiumcms-architecture` |
| `githubToken` | `password` — `repo` read + releases read |
| `defaultBranch` | `main` |
| `deployEnabled` | master switch (prod only; ignored when `DEMO_MODE=true`) |
| `allowDeployMain` | allow tracking branch deploy |
| `allowDeployTags` | allow tag-only deploy |

GitHub API (read-only for check):

- `GET /repos/{owner}/{repo}/commits/main` — latest SHA
- `GET /repos/{owner}/{repo}/releases/latest` — latest tag + body
- `GET /repos/{owner}/{repo}/compare/{base}...{head}` — commits behind/ahead

Optional webhook (future): `POST /api/webhooks/github/release` → auto-enqueue deploy on published release (HMAC secret in settings).

### G5. Demo policy

| Instance | Admin update button | Reason |
|----------|-------------------|--------|
| Production | ✅ when `deployEnabled` | Controlled upgrades |
| Demo | Hidden / disabled | Demo is resettable sandbox; SSH + tag pin is enough |

### G6. Suggested iteration

Document as **It.63 — Admin system update (prod)** — see [ITERATION_63.md](../ITERATION_63.md):

- MVP: status + manual CLI trigger from admin (job queue)
- v2: GitHub compare UI + one-click deploy
- v3: webhook on release publish

---

## Related docs

- [CRON.md](./CRON.md) — scheduler after deploy (unchanged)
- [NGINX_API.md](./NGINX_API.md) — host nginx (usually no restart on app deploy)
- [developer/RELEASE.md](../developer/RELEASE.md) — tag + GitHub Release copy-paste
- [developer/BETA_INFRA.md](../developer/BETA_INFRA.md) — gate before tag
- [ITERATION_62.md](../ITERATION_62.md) — scheduler prod hardening
