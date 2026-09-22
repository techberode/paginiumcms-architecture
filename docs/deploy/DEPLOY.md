---
title: Production and demo deployment
description: Safe and reproducible PaginiumCMS deployment, release update, smoke test, rollback, and operational evidence
icon: material/server-network
---

# Production and demo deployment

> This document covers deployment of the **`v2.1.0-beta.*`** family to an already prepared server. Initial operating-system, DNS, TLS, and user bootstrap belongs in a separate server runbook. The release decision and the 21-step gate are defined in [RELEASE.md](../developer/RELEASE.md).

## 1. Deployment contract

Deployment is not merely `git pull`. A successful deployment means that:

- the exact commit or annotated tag is known,
- authoritative flat-file data and restore-critical secrets are backed up,
- backend dependencies and the frontend build belong to the same commit,
- PHP runtime, web server, scheduler, and worker use the same storage root,
- health, login, authorization, and feature smoke checks pass after restart,
- rollback or roll-forward is ready,
- a short deployment record captures commit, time, result, and anomalies.

Canonical release identity:

```text
repository + commit SHA + tag + artifact SHA-256
```

## 2. Supported profiles

| Profile | Public frontend | PHP API | SSOT | Optional services | Status |
|---|---|---|---|---|---|
| Classic single-node | host nginx + `frontend/dist` | Docker or PHP-FPM | local disk | cron/worker | ✅ current baseline |
| Demo | separate vhost and stack | separate port | isolated demo storage | automatic reset | ✅ supported |
| Hybrid | same topology | same API | disk SSOT | Redis (It.69), Git publish | ✅ Redis in prod compose; Git publish It.70 |
| Git-headless | static or API frontend | editor/API node | disk/repository checkout SSOT | queue + build hook | ⏳ target profile |

Profiles are configurations of one product. They do not authorize moving authoritative content into an SQL database.

## 3. Recommended directory topology

```text
/var/www/paginiumcms.com/                  # production checkout
/var/www/paginiumcms-demo/                 # demo checkout
/var/lib/docker/compose/paginiumcms/        # production stack wrapper
/var/lib/docker/compose/paginiumcms-demo/   # demo stack wrapper
/var/backups/paginiumcms/                   # backup outside web root
/var/log/paginiumcms/                       # scheduler/worker/deploy logs
```

Reference ports preserved from the source configuration:

| Instance | Host nginx upstream | Docker project |
|---|---:|---|
| production | `127.0.0.1:8089` | `paginiumcms` |
| demo | `127.0.0.1:8091` | `paginiumcms-demo` |

Ports and paths are deployment-specific. They are a retained reference profile, not a universal requirement.

## 4. What is not an application deployment

The GitHub admin section synchronizes flat-file content through the application integration layer. It is not a mechanism for:

- pulling application code,
- updating Composer/NPM dependencies,
- building the React bundle,
- restarting PHP or Docker,
- migrating server configuration.

Local save, Git content publish, frontend build, and production deployment are separate states.

## 5. Pre-deployment conditions

Before every production deployment verify:

```bash
cd /var/www/paginiumcms.com
git status --short
git rev-parse HEAD
git remote -v
```

Required conditions:

- the working tree has no undocumented local changes,
- the release gate and manual review are closed,
- GitHub CI belongs to the deployed SHA,
- `.env`, storage, and uploads are not overwritten by checkout,
- there is enough space for build, backup, and temporary files,
- nginx passes `nginx -t`,
- the stack passes `docker compose config --quiet`,
- backup and restore have been practically tested for this profile.

## 6. Production deployment of a tagged release

The preferred production model pins an immutable tag:

```bash
APP_ROOT=/var/www/paginiumcms.com
STACK_DIR=/var/lib/docker/compose/paginiumcms
BACKEND_PORT=8089
RELEASE_REF=v2.1.0-beta.23

cd "$APP_ROOT"
git fetch origin --tags --prune
git checkout --detach "$RELEASE_REF"
git rev-parse HEAD

composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader

cd frontend
npm ci
npm run build:prod
cd ..

"$STACK_DIR/stack.sh" config --quiet
"$STACK_DIR/stack.sh" up -d --build
```

The production compose merge (`docs/deploy/docker-compose.prod.yml`) includes a **Redis** service and sets `REDIS_HOST=redis` on PHP. The prod file must **`profiles: !reset []`** on `redis` — the base `docker-compose.yml` tags Redis with profile `cache` for local dev; without reset, **`stack.sh up -d` never starts Redis** and the admin probe reports *extension loaded but connection to redis:6379 failed*. After copying the prod override, run **`stack.sh build php`** (ext-redis), then **`stack.sh up -d`**. Verify **Settings → Hybrid Engine → cache probe** (`redisCache: available`). See [CACHE_OPERATIONS.md](../en/runbooks/CACHE_OPERATIONS.md).

`npm ci` and the Composer lockfile must belong to the same tag. A server must not repair dependencies using ad-hoc `npm update` or `composer update`.

## 7. Commit deployment for staging or rapid beta updates

Deployment from `origin/main` is acceptable for staging or an intentionally managed beta workflow:

```bash
cd /var/www/paginiumcms.com
git fetch origin --prune
git checkout main
git pull --ff-only origin main
```

Use `--ff-only`; an automatic merge on the production server is not a release process. Record the resulting SHA. A stable production release should prefer a tag or verified artifact.

## 8. Restart and transient 502 responses

Restart the relevant service after PHP code or configuration changes:

```bash
/var/lib/docker/compose/paginiumcms/stack.sh restart php
```

A short `502 Bad Gateway` during startup may match known [ISS-096](../ISSUES.md#iss-096). Use a bounded health loop rather than assuming a fixed wait:

```bash
for attempt in $(seq 1 30); do
  if curl -fsS "http://127.0.0.1:8089/api/health" >/dev/null; then
    echo "Backend ready"
    break
  fi
  sleep 2
  if [ "$attempt" -eq 30 ]; then
    echo "Backend health timeout" >&2
    exit 1
  fi
done
```

## 9. Mandatory smoke test

Minimum smoke after every deployment:

```bash
curl -fsS https://paginiumcms.com/api/health
curl -fsSI https://paginiumcms.com/
curl -fsSI https://paginiumcms.com/.well-known/security.txt
curl -fsSI https://paginiumcms.com/feed.xml
```

Manually verify:

- admin login and CSRF flow,
- dashboard without 5xx responses,
- one public document,
- authorization of a least-privileged account,
- upload or storage route when affected,
- scheduler/worker when jobs changed,
- the feature named in release notes.

A `200` health response alone does not prove login, the frontend bundle, or SSOT writes.

## 10. Frontend-only update

When only the frontend changed and the API contract remains compatible:

```bash
cd /var/www/paginiumcms.com/frontend
npm ci
npm run build:prod
```

Recommended safe swap:

```text
build into temporary directory
→ validate index.html and assets
→ atomically replace dist
→ smoke test
```

Overwriting `dist/` in place can expose an incomplete HTML/hashed-asset combination during the build.

## 11. Storage ownership and process identity

The web process, scheduler, worker, and deploy script must use the same storage tree. Do not fix ownership with blanket `chmod 777`.

Reference model from the source deployment:

```bash
sudo chown -R deploy-user:www-data backend/storage
sudo find backend/storage -type d -exec chmod 2775 {} \;
sudo find backend/storage -type f -exec chmod 0664 {} \;
```

Adapt the deploy user to the server. Verify writes under the same identity used by the PHP container:

```bash
/var/lib/docker/compose/paginiumcms/stack.sh \
  exec -T -u www-data php \
  sh -lc 'touch backend/storage/.deploy-write-test && rm backend/storage/.deploy-write-test'
```

## 12. Deploy permissions bootstrap (SSH user + Docker `www-data`)

Production uses **two writers** on the same git checkout:

| Identity | Typical actions |
|----------|-----------------|
| SSH deploy user | `git fetch`, `deploy-instance-update.sh`, `composer`, `npm` |
| `www-data` in PHP container | storage, scheduler, admin UI deploy, theme/extension imports |

If `www-data` creates a file the deploy user cannot move or overwrite, `DEPLOY_FORCE=1` fails with `Permission denied` (for example an untracked `README.md` under `backend/resources/views/themes/` after a theme import).

**This is not caused by a new CMS release tag.** It is caused by mixed ownership on the checkout tree.

### One-time bootstrap (recommended on every new server or `APP_ROOT`)

Run **once** on the host (sudo required), not inside the PHP container:

```bash
APP_ROOT=/var/www/paginiumcms.com ./scripts/bootstrap-deploy-permissions.sh
sudo usermod -aG www-data "$(whoami)"   # if not already in group www-data
```

Then start a **new SSH session** (or `newgrp www-data`).

The script sets checkout owner to the current user, group `www-data`, directories `2775` (setgid), files `664`, and prepares `backend/storage/app/deploy-cache` for Composer/npm caches used by deploy.

Then bootstrap the **stack wrapper** (separate path on the host — see §12.5):

```bash
STACK_DIR=/var/lib/docker/compose/paginiumcms APP_ROOT=/var/www/paginiumcms.com \
./scripts/bootstrap-stack-permissions.sh
```

### When you do **not** need to re-run bootstrap

- Every new beta tag or iteration (`beta.62`, `beta.63`, …) — run `./scripts/deploy-instance-update.sh` only.
- New git-tracked code pulled by deploy — normal checkout.
- New files under `backend/storage/` when storage already follows the shared model.

### When to re-run bootstrap (or fix ownership)

| Situation | Action |
|-----------|--------|
| New server or fresh clone at a new path | Run bootstrap once on that `APP_ROOT` |
| Deploy fails: `mv: … Permission denied` during `pre-checkout-backup` | `ls -la` on the path; `sudo rm` or `sudo chown deploy-user:www-data` on the blocker; re-run deploy |
| Someone ran `sudo chown root:…` or another user on the checkout | Re-run bootstrap |
| Repeated orphan files outside `storage/` with owner `www-data` only | Re-run bootstrap; ensure deploy user is in group `www-data` |

### Quick unblock (single orphan file)

```bash
cd /var/www/paginiumcms.com
ls -la backend/resources/views/themes/clean-journal/README.md   # example
sudo rm -f backend/resources/views/themes/clean-journal/README.md
# or: sudo chown "$(whoami):www-data" path/to/blocker

DEPLOY_FORCE=1 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  GIT_REF=v2.1.0-beta.62 ./scripts/deploy-instance-update.sh
```

See also [ISS-094](../ISSUES.md#iss-094) (scheduler storage) and [ISS-099](../ISSUES.md#iss-099) (demo CLI vs `www-data`).

## 12.5 Admin UI deploy (Platform → System update + Dashboard banner)

Admin deploy uses the same `scripts/deploy-instance-update.sh` as SSH, but PHP must know the **host stack path** to restart containers after checkout.

### Required settings (SUPER_ADMIN → Settings → System update)

| Setting | Example | Purpose |
|---------|---------|---------|
| **Enable admin deploy** | on | Allows `POST /api/admin/system/update/run` |
| **Allow deploy from semver tags** | on | Tag deploy (`v2.1.0-beta.63`, **`v2.1.0-hotfix.1`**, **`v2.1.0-fix.1`**) — see [RELEASE §4.1](../en/developer/RELEASE.md#41-operational-patch-tags-production-ui-deploy-no-main-checkout) |
| **Docker stack directory** | `/var/lib/docker/compose/paginiumcms` | Passed as `STACK_DIR` — **PHP restart** |
| **Backend health port** | `8089` | Post-deploy health check |
| **GitHub owner/repo** | `techberode/paginiumcms-architecture` | Remote release compare (API) |
| **GitHub deploy key (recommended)** | see below | **`git fetch` for admin UI** without PAT in CMS settings |
| **GitHub token** (settings or env) | optional if deploy key works | API release check; HTTPS git when no deploy key |
| **Remote version check interval (hours)** | `0` (default) | **Does not schedule background GitHub polling.** `0` = manual **Recheck** only (dashboard banner + Platform → System update). When **> 0**, the banner shows a **stale** hint after that many hours since the last successful compare — it still does **not** auto-call GitHub on a timer. Max **168**. |
| **`GITHUB_DEPLOY_TOKEN` in php `.env`** | `ghp_…` | Alternative to settings token |
| **`GITHUB_DEPLOY_SSH_KEY_PATH` in php service** | `/run/secrets/github_deploy_key` | Private key file mounted read-only into PHP container |

**Webhook secret ≠ GitHub token.**

### GitHub deploy key (recommended for admin UI)

One-time on the host (any org member with repo admin can add the public key on GitHub):

```bash
cd /var/www/paginiumcms.com
SECRETS_DIR=/var/lib/paginiumcms/secrets ./scripts/bootstrap-github-deploy-key.sh
```

1. Paste the printed **public** key into GitHub → repository → **Settings → Deploy keys** (read-only).
2. `stack.sh` auto-mounts the key via `docs/deploy/docker-compose.deploy-key.yml` when the host file exists (PHP sees `/run/secrets/github_deploy_key`). If fetch fails after a recreate, run `STACK_DIR=/var/lib/docker/compose/paginiumcms ./scripts/ensure-php-deploy-key-mount.sh` on the host — that copies the new wrapper and remounts the key. Do **not** store the private key under `/var/lib/docker/compose/paginiumcms`.
3. **Rebuild** the PHP image, then recreate (recreate alone keeps the old image without `ssh`): `"$STACK_DIR/stack.sh" build php && "$STACK_DIR/stack.sh" up -d --force-recreate php`.
4. In admin → **System update → Verify connection** — **ssh in PHP** and **Git fetch** should be OK without saving a PAT in Settings. Do **not** type `v2.1.0-beta.12` — that is an old placeholder; use the latest tag from **Check remote**.

**GitHub token in settings** remains useful for **Check remote / release API** on private repos; deploy itself can work with deploy key only.

Without **stack directory**, deploy may pull code but skip `stack.sh up -d --force-recreate` → old PHP/opcache keeps running (ISS-152).

### Host stack wrapper (`stack.sh`)

The stack directory must contain an executable **`stack.sh`**. Admin **deploy readiness** runs in PHP-FPM as **`www-data`** and calls `is_executable()` — a plain `sudo cp` + `chmod 750` leaves **`root:root`**, which blocks www-data and shows **`stack_script_missing`** even when `docker compose exec php ls` (as root) looks fine.

**Recommended (one-time per server, or after every `sudo cp` of stack.sh):**

```bash
STACK_DIR=/var/lib/docker/compose/paginiumcms \
APP_ROOT=/var/www/paginiumcms.com \
./scripts/bootstrap-stack-permissions.sh
```

The script installs `stack.sh` when missing, sets `root:www-data` + `750` on the stack directory and wrapper, and prints a www-data smoke command.

Manual equivalent:

```bash
sudo cp "$APP_ROOT/docs/deploy/stack.sh" "$STACK_DIR/stack.sh"
sudo chown root:www-data "$STACK_DIR" "$STACK_DIR/stack.sh"
sudo chmod 750 "$STACK_DIR" "$STACK_DIR/stack.sh"
```

The host path is invisible to PHP unless mounted. In `docs/deploy/docker-compose.prod.yml`, uncomment (and adjust) the optional volume on the **`php`** service (not nginx):

```yaml
- /var/lib/docker/compose/paginiumcms:/var/lib/docker/compose/paginiumcms:ro
```

Then recreate PHP: `"$STACK_DIR/stack.sh" up -d --force-recreate php`.

Readiness blockers:

| Blocker | Meaning |
|---------|---------|
| `stack_dir_missing` | Settings/env `stackDir` empty |
| `stack_dir_not_visible` | Path set but not mounted into PHP container |
| `stack_script_missing` | Directory visible but `stack.sh` missing or not executable **for www-data** — run `bootstrap-stack-permissions.sh` |
| `github_token_missing` | No GitHub SSH auth in PHP and no deploy token — run `bootstrap-github-deploy-key.sh` or set `GITHUB_DEPLOY_TOKEN` |
| `ssh_binary_missing` | Deploy key is mounted but the PHP image has no `ssh` — **`stack.sh build php`** then recreate (not recreate alone) |
| `github_deploy_ssh_key_invalid` | `GITHUB_DEPLOY_SSH_KEY_PATH` is set and `ssh` exists but `ssh -T git@github.com` fails — wrong key, not added on GitHub, or bad mount permissions |
| `github_deploy_ssh_key_unreadable` | Env path is set but the file is missing or not readable **inside** PHP — remount with `ensure-php-deploy-key-mount.sh` |
| `github_token_unreadable` | Token stored in `settings.json` but decrypt yields empty — fix **`APP_KEY`**, re-save token |

### Dashboard banner (SUPER_ADMIN)

The **Dashboard → Overview** banner (`SystemUpdateBanner`) is shown only for **SUPER_ADMIN** on non-demo instances. It shares the same check/deploy flow as **Platform → System update** (`useSystemUpdateFlow`).

#### How remote version discovery works (since **2.1.0-beta.90** follow-up)

| Layer | Behavior |
|-------|----------|
| **Once per browser session** | On the **first** mount of the banner in that tab session, the UI calls `GET /api/admin/system/update/check` (plus status). Tracked in `sessionStorage` (`paginium:system-update-session-auto:v1`). **Logout clears** the session flag so the next login can run one auto-check again. |
| **localStorage cache** | Last successful compare is stored (`paginium:system-update-check-cache:v1`) so revisiting the dashboard within the same browser shows the previous result without waiting. |
| **Manual Recheck** | **Recheck** always forces a new GitHub compare (respects SUPER_ADMIN + 2FA on the API). |
| **`remoteCheckIntervalHours`** | Default **`0`** = no periodic auto polling. Values **> 0** only control when the banner shows a **“last check is older than your interval”** hint — **not** a cron-like GitHub poll. |
| **Dismiss (X)** | Hides the banner until the next full page load (session auto-check does not re-run in the same session). |

When compare data is available:

- **Update available** — shows tag + **Deploy {tag}** when deploy readiness is green and admin deploy is enabled,
- **Blockers** — **Configure deploy** links to Settings with `DeployBlockersList`,
- **Current / unknown** — copy explains outcome; unknown usually means GitHub token, owner, or repo misconfiguration.

#### Logging and monitoring noise

- Slow but successful **`/api/admin/system/update/check`** responses are logged as **INFO** in application access logs (not WARNING), so log scanners should not treat a long GitHub compare as an incident by default.
- Do not point uptime monitors at the check endpoint unless you accept GitHub rate limits and intentional SUPER_ADMIN-only auth.

### Verify after admin deploy

```bash
curl -s http://127.0.0.1:8089/api/health | jq '.data.version // .version'
```

If version is stale but files updated:

1. Confirm checkout tag on the host: `git -C "$APP_ROOT" describe --tags --exact-match`.
2. Inside PHP as **www-data**, if `git -C /var/www/html describe` prints **dubious ownership**, health/API version falls back to `AppVersion::VERSION` even on the correct tag. Set `GIT_CONFIG_*` → `safe.directory=/var/www/html` on the **php** service (see `docs/deploy/docker-compose.prod.yml`) **and** rebuild PHP so FPM passes env (`docker/php/zz-paginium-fpm.env.conf`, `clear_env = no`). Compose env alone is **not** enough on stock `php-fpm` (`clear_env=yes` strips `GIT_CONFIG_*` from workers). Do **not** use `git config --global` as www-data — `HOME=/var/www` is not writable. **beta.80+** also passes `safe.directory` on the git CLI (`GitCli`), so version works even without FPM env.
3. If `curl …/api/health | jq` fails with **parse error**, print raw output first (`curl -sS -D- …`) — often **502** for ~30s after recreate; retry after nginx health is green.
4. Otherwise check **stack directory** in settings and redeploy, or SSH deploy with `STACK_DIR=…`.
5. If **health/version** matches the tag but the **admin UI looks like an older build** (missing menu items, old Kanban/chat), the deploy likely updated PHP/git only. Run the full deploy path including **`cd frontend && npm ci && npm run build:prod`** (see `scripts/deploy-instance-update.sh`). See [ISS-175](../ISSUES.md#iss-175).

## 12.6 Common production symptoms (ops)

| Symptom | Likely cause | What to do |
|---------|----------------|------------|
| Many **WARNING** access logs: `GET /api/auth/me/desk` **429** | Several UI components polled desk independently; global rate limit (60/min/IP/path) on older builds | Deploy **beta.90+** desk inbox fix (`DeskInboxProvider`, single poll). Until then, reduce open admin tabs. |
| Monitor e-mails for desk **500/429** | Same as above + scanner treating WARNING as incidents | Deploy fix; tune log incident scanner; 429 is INFO on current builds. |
| **Log export** (txt/zip/pdf) fails silently or toast only | Uncaught export error (often **missing `ext-zip`** on non-Docker PHP), or JSON error body parsed as blob on older FE | `docker compose exec php php -m \| grep -i zip` or host `php -m`; rebuild PHP image from `docker/php/Dockerfile`. Admin UI should return **503** with message on current builds ([ISS-176](../ISSUES.md#iss-176)). |
| Dashboard banner hammers GitHub every navigation | Misread of old docs — current UI is **one check per session** + manual Recheck | Set **Remote version check interval** to `0` unless you want stale hints only. |
| Deploy fails: **Need to specify how to reconcile divergent branches** | Local `main` on the server diverged from GitHub; old script used `git pull` | On host: `git fetch origin && git checkout main && git reset --hard origin/main`, then re-run deploy. **`deploy-instance-update.sh`** now uses `git reset --hard origin/<branch>` for `GIT_REF=origin/*` (no merge on server). Prefer release **tags** for production. |

## 13. Upgrade, backup, and rollback

Before deployment create:

- an application backup using the supported backup mechanism,
- an out-of-release copy of `.env` and secret material,
- a record of the current SHA/tag,
- a storage volume or filesystem snapshot when appropriate.

Rollback to a previous tag:

```bash
cd /var/www/paginiumcms.com
git fetch origin --tags
git checkout --detach <previous-tag>
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
cd frontend && npm ci && npm run build:prod && cd ..
/var/lib/docker/compose/paginiumcms/stack.sh up -d --build
```

If the new version changed authoritative data incompatibly, checking out code is insufficient. Use the documented restore or a forward fix. Every future migration must state backward compatibility and its rollback boundary.

## 14. Docker autostart

The production override uses `restart: unless-stopped`, which restarts containers after host boot when the Docker daemon starts automatically. Verify:

```bash
systemctl is-enabled docker
/var/lib/docker/compose/paginiumcms/stack.sh ps
/var/lib/docker/compose/paginiumcms/stack.sh config | grep -n 'restart:'
```

This covers the operational part of [ISS-119](../ISSUES.md#iss-119). `depends_on` does not prove application readiness; readiness is established by health and smoke checks.

## 15. Admin “System update” (current vs future)

**Shipped today (It.63, §12.5):** SUPER_ADMIN can **compare** GitHub releases and **enqueue** tag deploy via `POST /api/admin/system/update/run` when **Enable admin deploy** is on, readiness is green, and the privileged `system-deploy` job is registered. Execution is the allow-listed **`scripts/deploy-instance-update.sh`** bridge — not arbitrary shell from the web request thread.

**Still required for safe production use:**

- backup before deploy (banner confirms destructively),
- correct **`stackDir`** / deploy key / token,
- post-deploy health and smoke (§12.5 verify),
- rollback plan (§13).

**Not in scope / deferred (It.63 v4):** Grav-like onboarding wizard, live progress stream, and fully unattended customer self-update without operator review. Admin deploy must not reuse the content **`GitHubService`** sync path or become an unrestricted web shell.

## 16. Deployment evidence

After a successful deployment retain outside the web root:

```text
timestamp
instance/profile
tag + commit SHA
artifact checksum
backup identifier
nginx/docker validation
smoke results
scheduler/worker result
reviewer/deploy owner
anomalies and disposition
rollback reference
```

The record may be short, but it must establish what was deployed.

## 16.5 Component playground (It.95)

The admin `/playground` route is **off by default**. SUPER_ADMIN enables it in Settings → Component playground.

- Enabling adds CodeSandbox bundler hosts to **admin CSP** (`connect-src` / `frame-src`). Public pages stay fail-closed until that setting is on.
- Preview code never runs in PHP. Do **not** `npm install` user URLs inside the PHP container.
- Optional org packages belong at **frontend build** (`npm ci` with a deploy token), not at runtime.
- Private design-system **Git import** (It.95d) is an explicit SUPER_ADMIN action (`POST /api/admin/playground/import-git`). It fetches a zipball through `OutboundUrlGuard`, rejects Zip-Slip and PHP, and scans with `CodePolicyEngine`. There is **no** auto-update cron.
- `DEMO_MODE` keeps the playground off.

## 17. Related documents

- [RELEASE.md](../developer/RELEASE.md) — release gate and decision
- [INSTALLATION.md](../user/INSTALLATION.md) — first installation
- [DEPLOYMENT_MODES.md](../architecture/DEPLOYMENT_MODES.md) — hosting profiles
- [NGINX_API.md](./NGINX_API.md) — reverse proxy
- [CRON.md](./CRON.md) — scheduler and worker
- [DEMO_DEPLOY.md](./DEMO_DEPLOY.md) — demo profile
