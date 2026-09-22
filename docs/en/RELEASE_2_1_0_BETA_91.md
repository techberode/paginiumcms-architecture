# Release `v2.1.0-beta.91` — Redis cache, breadcrumbs, media folders, ops docs

> **Date:** 2026-09-22  
> **Tag:** `v2.1.0-beta.91`  
> **Previous:** [`v2.1.0-beta.90`](../../CHANGELOG.md#release-2-1-0-beta-90)

---

## One-line summary

Optional It.69 Redis derived cache in Docker prod stack, `[latest-articles]` ticker and public breadcrumbs, media folder ops, Origin probe fixes, and nginx-based homelab docs for CMS AI / LibreTranslate.

---

## Production — Redis (first time on this host)

1. Deploy this tag so `docs/deploy/docker-compose.prod.yml` on the server includes the **redis** service and PHP `REDIS_HOST=redis`.
2. Rebuild PHP (ext-redis): `stack.sh build php` — not `up` alone.
3. Start stack: `stack.sh up -d` (redis + php with healthcheck dependency).
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
