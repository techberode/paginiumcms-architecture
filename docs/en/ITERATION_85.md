# Iteration 85 — Request diagnostics (latency decomposition)

> **Status:** ⏳ planned (2026-08-19) — not started  
> **Priority:** 🟡 medium (ops / incident response; no product feature dependency)  
> **Wave:** Performance & observability (extends [It.71](ITERATION_71.md) Performance Guard + access logs)  
> **Depends on:** shipped Performance Guard (It.71), `RequestLoggingMiddleware`, `InstrumentedStorage`, `SessionManager`  
> **Prerequisite hotfix:** uncommitted **beta.58** session-lock + thumbnail work should ship first (CHANGELOG Unreleased) — otherwise diagnostics will still show symptoms without the fix in production  
> **Target release:** `v2.1.0-beta.58` (hotfix bundle) or `beta.59` if split

## Goal

Make slow-request incidents **self-explanatory** without manual curl experiments. After a regression like parallel SPA requests serializing on `PHPSESSID`, logs and DevTools must immediately distinguish:

| Pattern | Signal |
|---------|--------|
| **Blocking** (session lock, I/O wait, PHP logic) | high `duration_ms` + **small** `size_bytes` + high `session_lock_ms` |
| **Large payload** (full PNG, big JSON) | high `duration_ms` + **large** `size_bytes` + low `session_lock_ms` |
| **Flat-file bound** | high `storage_ms` relative to `duration_ms` |
| **APM store contention** | high `apm_lock_wait_ms` on sampled requests |

Extends It.71 counters with **durations** and adds **`size_bytes`** to production access logs.

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **85a** | `size_bytes` in `http_access` | 🟡 P1 | ⏳ | Response size in every access log entry |
| **85b** | `storage_ms` in APM | 🟡 P1 | ⏳ | `hrtime()` in `InstrumentedStorage` → sample + aggregator |
| **85c** | `session_lock_ms` | 🔴 P1 | ⏳ | Time blocked in `session_start()`; optional `sess-held` duration |
| **85d** | `Server-Timing` header | 🟡 P2 | ⏳ | Live phase breakdown in browser DevTools (settings/debug gated) |
| **85e** | `apm_lock_wait_ms` | 🔵 P3 | ⏳ | `flock` wait time in `PerformanceSampleStore::append()` |

Recommended delivery order (fastest value first; stop anywhere — each slice is independently useful):

```text
85a → 85b → 85c → 85d → 85e
```

**Friday resume tip:** implement **85a + 85b + 85c** in one session (~2–3 h); defer **85d/e** if time-constrained.

---

## Background (2026-08-19 incident)

Observed after It.84 blog sidebar + large media:

1. Performance Guard p95 jumped to ~1340 ms+ (ring buffer of 500 samples).
2. Root cause was **PHP session write lock** on parallel GETs sharing one cookie — not Docker network, not blog sidebar logic alone.
3. Secondary factors: **1.7 MB PNG** via `/storage/`, analytics counting media as pageviews, PG sampling `/storage/`.

Fixes are in CHANGELOG **Unreleased** (session release middleware, lazy session, storage stream/cache, thumbnails, PG/analytics skip). **It.85 does not replace those fixes** — it prevents the next blind hunt.

---

## Slice specifications

### 85a — `size_bytes` in `http_access`

| Piece | Detail |
|-------|--------|
| **Where** | `RequestLoggingMiddleware` → context → `AccessLogService::logRequest()` |
| **Resolution** | Prefer `Content-Length` header; fallback `Body::getSize()`; `null` if unknown (streamed/chunked) |
| **Log field** | `size_bytes: int\|null` in entry context (alongside existing `duration_ms`) |
| **Formatter** | Optional human hint in `ApplicationLogMessageFormatter` (e.g. `1.7 MB`) |
| **Tests** | `AccessLogServiceTest`, middleware unit test with mocked response |

**Interpretation cheat sheet:**

```text
duration_ms ≥ slowRequestMs  AND  size_bytes > 500_000   → investigate payload / CDN / thumbnails
duration_ms ≥ slowRequestMs  AND  size_bytes < 10_000    → investigate lock / storage / PHP
```

---

### 85b — `storage_ms` in InstrumentedStorage

| Piece | Detail |
|-------|--------|
| **Where** | `PerformanceContext` accumulates `storageReadNs` / `storageWriteNs`; `InstrumentedStorage` wraps each I/O with `hrtime(true)` |
| **APM sample** | Add `storage_ms` (and optionally keep existing `storage_reads` / `storage_writes` counts) |
| **Aggregator** | Extend `PerformanceAggregator::summary()` with totals / p95 of `storage_ms` where present |
| **Admin API** | Surface in `/api/admin/metrics` summary (backward compatible — old samples omit field) |
| **Tests** | `PerformanceContext` test, `InstrumentedStorage` test, update `PerformanceGuardMiddlewareTest` |

**Non-goals:** per-file paths in metrics (security / noise).

---

### 85c — `session_lock_ms`

| Piece | Detail |
|-------|--------|
| **Where** | `SessionManager::ensureSessionActive()` — measure wall time of `session_start()` (includes lock wait + session file read) |
| **Collector** | Request-scoped `RequestTimingCollector` or extend `PerformanceContext` |
| **APM sample** | `session_lock_ms: float` when session was opened this request; `0` or omit when lazy session never started |
| **Optional** | `session_held_ms` — from `session_start()` until `releaseWriteLock()` (proves lock held too long) |
| **Tests** | `SessionManagerTest` with mocked timing boundaries; document that true lock contention needs parallel integration test |

**Note:** This is the **primary** signal for the Aug 2026 incident. `apm_lock_wait_ms` (85e) measures a different lock (metrics JSON file).

---

### 85d — `Server-Timing` HTTP header

| Piece | Detail |
|-------|--------|
| **Where** | New `ServerTimingMiddleware` (outer stack, after handler) or extend `RequestLoggingMiddleware` |
| **Format** | W3C `Server-Timing`: e.g. `sess-lock;dur=2310, storage;dur=45, app;dur=12` |
| **Gating** | **Off by default in production.** Enable via `engine.performanceGuard.serverTiming: true` **or** `APP_DEBUG=true` |
| **Security** | No routes, paths, or user IDs in metric names; durations only |
| **FE** | No frontend changes required — Chrome/Firefox Network tab shows Server Timing automatically |
| **Tests** | Middleware test: header present when enabled, absent when disabled |

**Phases exposed (minimum):**

| Metric name | Source |
|-------------|--------|
| `sess-lock` | 85c |
| `storage` | 85b |
| `app` | total − sess − storage (residual) |

Stretch: `cache` (if cheap to expose from `CacheManager`).

---

### 85e — `apm_lock_wait_ms`

| Piece | Detail |
|-------|--------|
| **Where** | `PerformanceSampleStore::append()` — `hrtime()` before/after `flock(LOCK_EX)` |
| **APM sample** | `apm_lock_wait_ms: float` |
| **Use case** | Detect contention writing `data/metrics/apm-samples.json` under high concurrency |
| **Tests** | `PerformanceSampleStoreTest` — can simulate slow lock with test double or sequential appends |

Low priority unless PG sample rate is high and p95 append latency is suspected.

---

## Settings contract (incremental)

```yaml
engine:
  performanceGuard:
    # existing It.71 keys …
    serverTiming: false          # 85d — default off
logging:
  # existing keys …
  includeResponseSize: true      # 85a — default true when requestLogging enabled
```

Backward compatible: missing keys → safe defaults (`false` / `true`).

---

## Definition of Done

### 85a
- [ ] `size_bytes` in `http_access` context for API and `/storage/` responses with `Content-Length`
- [ ] `null` when size unknown (not `0`)
- [ ] PHPUnit + gate green

### 85b
- [ ] `storage_ms` on APM samples when PG enabled
- [ ] Aggregator exposes aggregate `storage_ms` (sum or avg — document choice)
- [ ] PHPUnit + gate green

### 85c
- [ ] `session_lock_ms` recorded when session starts
- [ ] Documented in [user/LOGGING.md](user/LOGGING.md) or ops note
- [ ] PHPUnit + gate green

### 85d
- [ ] `Server-Timing` header when setting/debug enabled
- [ ] Absent in default production config
- [ ] PHPUnit + gate green

### 85e
- [ ] `apm_lock_wait_ms` on samples
- [ ] PHPUnit + gate green

**Iteration complete when:** at least **85a–85c** shipped + gate green + CHANGELOG + this doc status → ✅.

---

## API / admin surface

| Surface | Change | Slice |
|---------|--------|-------|
| `GET /api/admin/metrics` | summary includes `storage_ms`, `session_lock_ms` aggregates | 85b, 85c |
| Admin → Logs | optional column/filter “slow + small body” | 85a (stretch) |
| HTTP responses | `Server-Timing` header | 85d |

No new public routes. No breaking JSON shapes — new optional fields only.

---

## Out of scope (It.85)

- Full distributed tracing / OpenTelemetry export
- Host-level metrics (remains It.46 remainder)
- Automatic remediation changes (It.71 `SafeRemediationService` untouched)
- Frontend APM dashboard redesign (optional small widget later)
- Correlation ID / `PHPSESSID` in logs (privacy)

---

## Related documents

| Doc | Role |
|-----|------|
| [ITERATION_71.md](ITERATION_71.md) | Performance Guard baseline |
| [user/LOGGING.md](user/LOGGING.md) | Access log semantics |
| [ISSUES.md](ISSUES.md) | ISS-158 (storage skewing p95) — reference when documenting |
| CHANGELOG Unreleased | beta.58 hotfix (session lock, thumbnails) — ship before/with 85 |

---

## Resume checklist (for Friday)

1. Commit & deploy **beta.58 hotfix** if still uncommitted (session release, thumbnails, layout).
2. `./scripts/iteration-gate.sh` — confirm green baseline.
3. Implement **85a** → run gate → commit slice.
4. Implement **85b + 85c** → gate → commit.
5. If tokens/time remain: **85d**, then **85e**.
6. Update this doc status + CHANGELOG under `2.1.0-beta.59` (or fold into beta.58 if same release).
