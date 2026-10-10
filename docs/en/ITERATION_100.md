# Iteration 100 — Admin load, cache tiers & single-instance concurrency (living)

> **Status:** 🔄 **ongoing** — never “done” in one deploy; continuous tuning on the `main` development line  
> **Priority:** 🟡 operational stability · admin UX under **≥3 concurrent operators** on **one instance**  
> **Type:** cross-cutting **evolution branch** (not a feature freeze milestone)  
> **Depends on:** [It.69](ITERATION_69.md) derived cache · [It.71](ITERATION_71.md) Performance Guard · logging / incident scan (It.7)  
> **Simulation runbook:** [developer/ADMIN_LOAD_SIMULATION.md](developer/ADMIN_LOAD_SIMULATION.md)

---

## 1. Mission

Keep **one self-hosted instance** responsive when several admins work at once: editor saves (P0) must not queue behind heavy dashboard aggregates (P2). Public site traffic stays on the existing content cache path; **It.100 focuses on `/api/admin/*` and admin SPA behaviour**.

This iteration **does not block** feature iterations (98, 99, …). Every new admin endpoint or panel must declare **priority tier** and cache segment (see §5).

---

## 2. Problem

| Symptom | Typical cause |
|---------|----------------|
| 3–5 s GET on dashboard open | Parallel cold reads (audit stats, jobs, APM, planner) on flat-file I/O |
| Sporadic slow saves | Disk + PHP workers busy serving P2 GET for multiple tabs/users |
| Log WARNING `http_access` | `slowRequestMs` (default 2000) — observability, not root fix |

Single-user tuning hides **multi-user interference** on one `data/` tree and shared PHP-FPM pool.

---

## 3. Priority & cache segments (target model)

| Tier | Examples | Cache segment | Freshness target |
|------|----------|---------------|------------------|
| **P0** | Auth, save/publish, upload, locks | none (always authoritative) | immediate |
| **P1** | Nav counts, dashboard overview KPIs, settings read | **A — hot** (short TTL / per-user FE stale) | tens of seconds |
| **P2** | Audit stats, jobs overview, APM summary, planner overview | **B — warm** (shared derived, Redis/file) | 1–5 min acceptable |
| **P3** | Log export, heavy reports | **C — cold** | async job only (future) |

**Dynamic policy (goal):** when concurrent admin load rises (e.g. **>3 active sessions** or FPM saturation), automatically **extend P2 TTL**, **defer P2 FE fetch**, and **never throttle P0**.

**Shipped foundation (Unreleased):** `AdminOverviewCacheService` (120 s fresh / 600 s stale **SWR**, no request-thread stampede lock) + FE `useAdminSecondaryQuery` + dashboard defer — static P2, not yet load-aware.

**100c (partial — Unreleased):** CLI `php backend/bin/console cache:warm-admin` pre-generates audit/jobs/APM/planner P2 segments; pair with cron (see [ADMIN_LOAD_SIMULATION](developer/ADMIN_LOAD_SIMULATION.md)). Docker nginx template enables **gzip** for JSON. **100d** covers cron-only documents + optional Redis job-run projections (see §4).

**Desk (ISS-198):** slow `GET /api/auth/me/desk` was a separate FPM hog (comment N+1 + double compose); fixed in tree — deploy with P2 cache + cron warm-up for full effect.

**Session lock (Unreleased):** global `SessionReleaseMiddleware` runs before auth opens the session; **`AuthMiddleware`** now calls `SessionManager::releaseWriteLock()` on authenticated GET/HEAD/OPTIONS so one tab’s `Promise.all` does not queue on a single `PHPSESSID` (see `admin-concurrency-probe.sh -s`).

---

## 4. Living roadmap (phases, repeatable)

| Phase | Focus | Exit signal |
|-------|--------|-------------|
| **100a** | Document tiers; cache P2 endpoints; FE secondary stale | Fewer repeated 5 s spikes on dashboard refresh |
| **100b** | Load hint (`normal` / `busy`) from PG + worker pressure → FE coordinator | P2 deferred under busy; P0 latency stable in probe (**partial — Unreleased:** `GET /api/admin/metrics/load-hint`, dashboard/planner defer + longer P2 stale when `busy`) |
| **100c** | P2 prewarm cron + SWR (no request-thread `rememberLocked`) | Miss storm reduced; stale served while cron/shutdown refresh ( **partial — Unreleased** ) |
| **100d** | Cron-owned P2 **documents** in Redis/file; optional Redis types for job runs + slimmer `/api/admin/jobs` | HTTP never cold-parses flat-file for P2; jobs GET no longer one ~148 KB monolith by default |
| **100e** | P3 async exports; admission control (503 + Retry-After) for optional GET | No multi-minute blocking requests |
| **100f** | Optional dedicated “synth sessions” metric on instance (count active admin sessions) | Policy rules use real session count, not only heuristics |

Phases may ship in any order; **each deploy only advances metrics** — none closes It.100.

### Phase 100d — P2 as pre-built documents (planned)

**Not in 100c.** Today: SWR + `cache:warm-admin` still allow a **cold miss** on HTTP (sync flat-file read with short coalescing). **100d** moves to a No-SQL-style model: overview payloads are **always** read as ready-made JSON documents from derived cache; **only** cron/worker writes them.

1. **Cron-only write path (recommended)**  
   - HTTP: **always return** what is in Redis/file (TTL owned by cron, e.g. 24 h or refresh every minute — not “recompute on expiry” in the request).  
   - No flat-file aggregation in the admin request path; invalidation triggers **background** rebuild, not blocking GET.  
   - Exit: concurrency probe shows P2 GET stable even with empty warm-up window (e.g. post-restart) once cron has run at least once; document miss policy (serve last snapshot vs 503) is explicit.

2. **Redis data shapes for jobs (optional, if SSOT allows)**  
   - Flat-file remains SSOT for job **definitions**; derived **runs / queue snapshots** may be projected into Redis **Hashes**, **Streams**, or paginated document keys.  
   - `GET /api/admin/jobs`: default response fetches **last N runs** (or paginated slices), not a single huge JSON blob (~148 KB).  
   - Mutations still invalidate/rebuild the derived projection; security baseline unchanged (authz on API, no new web-reachable Redis).

**Depends on:** Redis or shared file cache (It.69); **100c** cron habit on production. **Does not** replace flat-file SSOT — derived layer only.

---

## 5. Contract for future implementations

When adding or changing **admin** API or SPA data loading:

1. Label **P0 / P1 / P2 / P3** in PR description and, where non-obvious, a one-line docblock or route comment.
2. **P2+** must use derived cache (`AdminOverviewCacheService` or extend it) — no full-file scan on every request unless invalidated.
3. **P2+** FE should use `useAdminSecondaryQuery` or defer (`useDeferredAfterPaint`), not blocking mount on heavy GET.
4. Mutations **invalidate** the correct overview cache keys (same pattern as audit → `invalidateAuditStats()`).
5. Run **[admin concurrency probe](../../scripts/admin-concurrency-probe.sh)** on staging (or production read-only profile) when touching P1/P2 paths.
6. Note impact in `CHANGELOG.md` under **[Unreleased]** if behaviour or defaults change.

---

## 6. Observability (existing + It.100)

| Signal | Where |
|--------|--------|
| Slow HTTP | `logging.slowRequestMs` → `http_access` WARNING |
| Latency budgets | `engine.performanceGuard` → breaches + dashboard |
| Log alerts | `monitoring.notifyLogWarnings` + `LogIncidentScanner` (scheduler) |
| Cache | Settings → Engine → cache stats; Redis/file derived only |
| Synthetic load | [ADMIN_LOAD_SIMULATION.md](developer/ADMIN_LOAD_SIMULATION.md) |

---

## 7. Multi-user testing without ten browsers

Real-time on **production** is possible with a **read-only synthetic probe**: N parallel authenticated GETs against a fixed P1/P2 route set, rate-limited, off-peak, using a **dedicated low-privilege admin** account. This does **not** replace PHPUnit/Vitest; it measures **instance behaviour under concurrency**.

See runbook for cookie bootstrap, safety caps, and interpreting results next to Performance Guard samples.

---

## 8. Non-goals (It.100)

- Multi-instance federation or horizontal auto-scaling product features.
- Mandatory Redis on shared hosting (file/memory cache remains valid).
- Replacing Performance Guard or external APM (Prometheus, etc.).

---

## Related

[ITERATION_69.md](ITERATION_69.md) · [ITERATION_71.md](ITERATION_71.md) · [ITERATION_85.md](ITERATION_85.md) (`size_bytes` + slow request triage) · [CACHE_OPERATIONS.md](runbooks/CACHE_OPERATIONS.md)
