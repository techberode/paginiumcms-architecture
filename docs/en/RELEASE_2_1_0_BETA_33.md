# Release `v2.1.0-beta.33` — Deploy pipeline & AppVersion from git

> **Date:** 2026-08-11  
> **Tag:** `v2.1.0-beta.33`  
> **Previous:** [`v2.1.0-beta.32`](../../CHANGELOG.md#release-2-1-0-beta-32)  
> **Canonical changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-33)

## Summary

Fixes production deploy from admin UI (npm missing in PHP container) and makes `/api/health` version reflect the checked-out git tag instead of a stale constant.

---

## Deploy on server

```bash
cd /var/www/paginiumcms.com
git fetch origin --tags
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.33 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  ./scripts/deploy-instance-update.sh
```

Or rebuild PHP image after pull:

```bash
cd /var/lib/docker/compose/paginiumcms
./stack.sh up -d --build --force-recreate php
```

Verify: `curl -s http://127.0.0.1:8089/api/health | jq .data.version` → `2.1.0-beta.33`
