# Release `v2.1.0-beta.92` — Admin status UX, getting-started probes, upload compression

> **Date:** 2026-09-22  
> **Tag:** `v2.1.0-beta.92`  
> **Previous:** [`v2.1.0-beta.91`](../../CHANGELOG.md#release-2-1-0-beta-91)

---

## One-line summary

Dashboard getting-started checklist reads persisted settings (not stale public cache), shared admin status badges across ops panels, optional GD auto-compress on media upload, and prod Redis compose/stack hardening carried from beta.91 follow-up.

---

## Operator notes

### Getting started checklist

- **Site name step** uses `GET /api/admin/dashboard/overview` → `getting_started.siteName` from flat-file settings (plus description/branding fallbacks).
- SMTP step uses the same payload (`getting_started.mail`) — no longer inferred from missing public settings.
- Copy points to **Settings → General → Site name** (`general.siteName`).

### Media upload compression

- **Settings → Media / DAM:** `autoOptimizeOnUpload` (default **on**), `autoOptimizeMaxEdgePx` (default **3840**, `0` = compress only), `jpegQuality` / `webpQuality` (60–95).
- Requires **PHP GD**; manual **Optimize** in Media Library uses the same engine.
- Runbook: [CACHE_OPERATIONS.md](runbooks/CACHE_OPERATIONS.md#media-upload-compression-disk-not-http-cache).

### Redis (prod stack follow-up)

If Redis stayed in `created` state after beta.91:

1. Ensure **`docs/deploy/docker-compose.prod.yml`** on the host includes `profiles: !reset []`, `ports: !reset []`, and bind mount `${STACK_DIR}/redis-data:/data`.
2. Run `./stack.sh config` then `./stack.sh up -d`; PHP must use hostname **`redis`**, not `127.0.0.1:6379`.

See [DEPLOY.md](../../deploy/DEPLOY.md) and beta.91 release notes.

---

## Deploy

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.92 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm health / dashboard update banner version → `2.1.0-beta.92`.

---

## Test plan (smoke)

- [ ] Dashboard **Getting started** — after saving a custom site name, step completes without refresh loop failures.
- [ ] **Media** — upload a large JPEG; size decreases when GD available; badges show GD + auto-compress state.
- [ ] **Settings → Media** — probe panel shows GD and storage driver status.
- [ ] **Settings → Engine / Agent / Translation** — status badges on probes; notifications/webhooks/scheduler use badges.
- [ ] Optional: Redis probe green when `redis` container running and `engine.cacheDriver=auto`.
