# Release `v2.1.0-beta.91` — Redis cache, breadcrumbs, media folders, ops docs

> **Date:** 2026-09-22  
> **Tag:** `v2.1.0-beta.91`  
> **Previous:** [`v2.1.0-beta.90`](../../CHANGELOG.md#release-2-1-0-beta-90)

---

## One-line summary

Optional It.69 Redis derived cache in Docker prod stack, `[latest-articles]` ticker and public breadcrumbs, media folder ops, Origin probe fixes, and nginx-based homelab docs for CMS AI / LibreTranslate.

---

## Production — Redis (first time on this host)

1. Copy **`docs/deploy/docker-compose.prod.yml`** to **`$STACK_DIR/docker-compose.prod.yml`**. The `redis` service must include **`profiles: !reset []`** (base repo `docker-compose.yml` uses profile `cache` for local dev — without reset, Redis never starts in prod).
2. Rebuild PHP (ext-redis): `stack.sh build php` — not `up` alone.
3. Start stack: `stack.sh up -d` — confirm `stack.sh ps` shows **redis** running.
4. Admin: **Settings → Hybrid Engine** — cache probe should show Redis **available**; `engine.cacheDriver=auto` uses Redis when reachable (SSOT stays flat-file).

Classic installs without Docker can ignore Redis; file/memory cache remains the fallback.

---

## Deploy

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.91 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm health version → `2.1.0-beta.91`.
