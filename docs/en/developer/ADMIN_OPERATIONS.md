# Admin operations — load, cache, and host context

> **Scope:** self-hosted instances with **multiple admins** or heavy dashboard use (Iteration **100**, living).  
> **Audience:** operators and maintainers — not end editors.

PaginiumCMS admin SPA loads **P0** (auth, saves) and **P2** (audit stats, jobs, APM, planner) on the same PHP-FPM pool and often the same `PHPSESSID`. It.100 reduces interference without adding SQL or mandatory Redis.

---

## Priority tiers (contract)

| Tier | Examples | Rule |
|------|----------|------|
| **P0** | Login, content save, upload, locks | Never defer or cache authoritative writes |
| **P1** | Nav counts, dashboard KPIs | Short TTL / per-user FE stale |
| **P2** | Audit stats, jobs overview, APM summary, planner | Derived cache + deferred FE fetch |
| **P3** | Log export, heavy reports | Future: async only |

Full model: [ITERATION_100.md](../ITERATION_100.md) §3.

---

## Production checklist (single VPS)

1. **Cron** — every minute (Docker or host):

   ```bash
   php backend/bin/console scheduler:run
   php backend/bin/console worker:process
   ```

   See [deploy/CRON.md](../deploy/CRON.md).

2. **P2 cache warm (It.100c)** — enable **Jobs → Warm admin P2 overview cache** (`admin-cache-warm`), **or**:

   ```bash
   php backend/bin/console cache:warm-admin
   ```

   Warms audit, jobs (full + dashboard slice), APM (incl. load hint + host metrics view), planner.

3. **Host snapshot (It.82d, optional)** — Settings → Engine: enable host metrics + token; enable **Jobs → Host metrics snapshot** (`host-metrics-collect`). Dashboard Performance Guard shows load/RAM/disk. [HOST_METRICS.md](HOST_METRICS.md)

4. **Session lock** — shipped: `SessionReleaseMiddleware` + `AuthMiddleware` releases write lock on authenticated GET/HEAD/OPTIONS so parallel dashboard XHR do not serialize on one session.

5. **Frontend (shipped on `main`)** — dashboard P2 fetches run **sequentially** with stagger; `useAdminLoadHint()` extends React Query stale time when level is `busy`.

6. **Load hint + host (It.100b)** — Settings → Engine → **Admin busy: 1-min load average ≥** (`adminLoadHintHostLoad1Min`, default **0** = off). When host metrics snapshot is fresh and load avg ≥ threshold, API returns `load_hint.level = busy` with reason `host_load_1m` (works even if Performance Guard is off).
7. **Load hint + sessions (It.100f)** — **Admin busy: active sessions ≥** (`adminLoadHintActiveSessionsMin`, default **0**). Counts recent authenticated admin requests (session touch in `AuthMiddleware`). Response includes `active_admin_sessions`.
8. **P2 cron-only HTTP (It.100d)** — **P2 cache: cron-only HTTP reads** (`adminP2CacheCronOnly`). Enable only with **admin-cache-warm** cron; cold miss returns warming placeholders (`_cache.status = miss`), no flat-file parse on GET.
9. **P3 exports (It.100e)** — **Defer heavy exports when busy** (`adminP3AdmissionWhenBusy`, default on). Sync log export GET → **503** + `Retry-After` when busy; **POST** `/api/admin/logs/export` queues async job and returns **202** with `artifact_id` + poll/download URLs.

6. **nginx** — enable **gzip** for `application/json` (large `/api/admin/jobs`). Reference: `docker/nginx/default.conf`.

---

## Backend building blocks

| Component | Role |
|-----------|------|
| `AdminOverviewCacheService` | P2 SWR: 120 s fresh, 600 s stale window; **HTTP never sync-rebuilds** when any snapshot exists (expired → serve + shutdown refresh) |
| `AdminOverviewCacheWarmer` / `cache:warm-admin` | Cron/CLI rebuild of P2 documents |
| `AdminLoadHintResolver` | `GET /api/admin/metrics/load-hint` → `normal` / `busy` |
| `GET /api/admin/jobs?recent_runs=N` | Dashboard uses 30; cap 500 |
| `scripts/admin-concurrency-probe.sh` | Read-only synthetic concurrency test |

---

## Load simulation (staging / careful prod)

[ADMIN_LOAD_SIMULATION.md](ADMIN_LOAD_SIMULATION.md) — cookie bootstrap, route set, interpreting results next to Performance Guard (session-lock p95, storage p95).

---

## Upload security (It.99, separate but ops-relevant)

Polyglot marker scan and optional raster re-encode: [UPLOAD_POLYGLOT.md](UPLOAD_POLYGLOT.md).  
Media **auto-compress on upload** (GD) is Settings → Media — reduces disk size; independent of It.99 `reencodeRasterUploads`.

---

## Cache layer (public + derived)

Content/public HTTP cache: [runbooks/CACHE_OPERATIONS.md](../runbooks/CACHE_OPERATIONS.md). Admin P2 envelopes use the same `CacheManager` driver (`auto` / Redis / file).

---

## Related

- [ITERATION_100.md](../ITERATION_100.md) · [ITERATION_71.md](../ITERATION_71.md) · [ISS-198](../../ISSUES.md#iss-198) (desk perf)
