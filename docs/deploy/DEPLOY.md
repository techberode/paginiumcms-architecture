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
| Hybrid | same topology | same API | disk SSOT | Redis, Git publish | ⏳ It.68–70 |
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
| **Allow deploy from semver tags** | on | Tag deploy (`v2.1.0-beta.63`) |
| **Docker stack directory** | `/var/lib/docker/compose/paginiumcms` | Passed as `STACK_DIR` — **PHP restart** |
| **Backend health port** | `8089` | Post-deploy health check |
| **GitHub owner/repo/token** | … | Remote version check (Dashboard auto-check) |

Without **stack directory**, deploy may pull code but skip `stack.sh up -d --force-recreate` → old PHP/opcache keeps running (ISS-152).

### Dashboard banner (SUPER_ADMIN)

On load, the dashboard **automatically checks GitHub** for a newer release. When an update is available:

- shows version + **Deploy {tag}** when all blockers are green,
- shows **Configure deploy** with a blocker list when something is missing,
- links to **Platform → System update** for details.

### Verify after admin deploy

```bash
curl -s http://127.0.0.1:8089/api/health | jq '.data.version // .version'
```

If version is stale but files updated → set stack directory in settings and redeploy, or run SSH deploy with `STACK_DIR=…`.

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

## 15. Admin “System update”

Admin-triggered application updates remain a planned capability, not a current safe production mechanism. They must not reuse the content `GitHubService` or execute as an unrestricted web shell.

The future contract requires:

- a production-only feature flag,
- SUPER_ADMIN plus fresh 2FA confirmation,
- repository allow-list and branch/tag policy,
- an out-of-process privileged deploy runner,
- backup, maintenance mode, locking, and audit,
- a sanitized log stream,
- immutable release reference and checksum,
- automatic health/smoke checks and a rollback boundary.

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

## 17. Related documents

- [RELEASE.md](../developer/RELEASE.md) — release gate and decision
- [INSTALLATION.md](../user/INSTALLATION.md) — first installation
- [DEPLOYMENT_MODES.md](../architecture/DEPLOYMENT_MODES.md) — hosting profiles
- [NGINX_API.md](./NGINX_API.md) — reverse proxy
- [CRON.md](./CRON.md) — scheduler and worker
- [DEMO_DEPLOY.md](./DEMO_DEPLOY.md) — demo profile
