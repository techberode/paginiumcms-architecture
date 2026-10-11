# Release `v2.1.0-beta.98` — Admin load (It.100), host metrics (It.82d), upload polyglot (It.99)

> **Date:** 2026-10-11  
> **Tag:** `v2.1.0-beta.98`  
> **Previous:** [`v2.1.0-beta.97`](RELEASE_2_1_0_BETA_97.md)

---

## One-line summary

Ships **Iteration 100** admin load tiers (P2 SWR cache, load-hint, cron warm, cron-only HTTP option, async log export under busy, active session metric), **It.82d** host metrics snapshot, **It.99** upload polyglot hardening, desk/CSRF/monitoring fixes, and the **ADMIN_OPERATIONS** ops hub.

---

## Operator notes

1. Rebuild frontend after deploy (`npm run build:prod` or deploy script).
2. **Cron (recommended):** enable Jobs → **Warm admin P2 overview cache** (`admin-cache-warm`) and optionally **Host metrics snapshot** (`host-metrics-collect`). See [CRON.md](../deploy/CRON.md) · [ADMIN_OPERATIONS.md](developer/ADMIN_OPERATIONS.md).
3. **Engine settings (optional):** `adminLoadHintHostLoad1Min`, `adminLoadHintActiveSessionsMin`, `adminP2CacheCronOnly` (only with warm cron), `hostMetricsEnabled` + ingest token on production hosts.
4. **Upload security:** review [UPLOAD_POLYGLOT.md](developer/UPLOAD_POLYGLOT.md) and `reencodeRasterUploads` if you accept admin uploads from many operators.

---

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.98 ./scripts/deploy-instance-update.sh
```

Confirm health / Origin version → `2.1.0-beta.98` (or git describe on tagged checkout).

---

## Smoke

- [ ] Dashboard opens — P2 widgets load (sequential/stagger); no multi-second cold spikes when warm cron ran.
- [ ] `GET /api/admin/metrics/load-hint` → `level`, `reasons`, `active_admin_sessions`.
- [ ] `./scripts/admin-concurrency-probe.sh -h` (read-only) completes on staging.
- [ ] Log export — sync download; under busy load-hint, POST export may return **202** and FE polls artifact (toast “prepared in background”).
- [ ] `./scripts/iteration-gate.sh` green on the release commit.

---

## Documentation hub

| Topic | Doc |
|-------|-----|
| It.100 living iteration | [ITERATION_100.md](ITERATION_100.md) |
| Production checklist | [ADMIN_OPERATIONS.md](developer/ADMIN_OPERATIONS.md) |
| Cache / warm | [CACHE_OPERATIONS.md](runbooks/CACHE_OPERATIONS.md) |
| Host metrics | [HOST_METRICS.md](developer/HOST_METRICS.md) |
| Polyglot uploads | [UPLOAD_POLYGLOT.md](developer/UPLOAD_POLYGLOT.md) |
| Media compression | [MEDIA_UPLOAD.md](developer/MEDIA_UPLOAD.md) |
