# Cache operations runbook

> **Scope:** Iteration 69 unified cache layer (Classic profile: memory + file).  
> **Source of truth:** flat files under `data/` — cache is always derived and safe to delete.

---

## Architecture summary

| Layer | Role |
|-------|------|
| **Memory** | Per-worker hot layer (zero disk I/O on repeat reads) |
| **File** | Persistent cache under `data/cache/` (Classic default) |
| **Redis** | Optional; **not installed in It.69** — `engine.cacheDriver=redis` falls back to `auto` |

Invalidation uses **generation counters** (lists/feeds) plus **tag registry** (`content:pages:list`, `content:page:{slug}`, …). Writes call `ContentCacheService::invalidatePage()` / `invalidateArticle()` after a successful SSOT save.

HTTP validators (`ETag`, `Last-Modified`, `304`) apply to anonymous public GET routes when `engine.httpValidatorsEnabled` is true:

- `GET /api/settings/public`
- `GET /api/pages`, `GET /api/pages/{slug}`
- `GET /api/articles`, `GET /api/articles/{slug}`

Authenticated admin reads receive `Cache-Control: private, no-store`.

---

## Verify health

1. **Admin:** Settings → Hybrid Engine → Cache probe (driver, latency, HTTP validators state).
2. **API stats:** `GET /api/admin/cache/stats` (requires admin auth) — file entry count, list generations, hit/miss counters.
3. **Manual ETag check:**

```bash
ETAG=$(curl -sI http://localhost:8080/api/settings/public | awk -F': ' '/^ETag:/ {print $2}' | tr -d '\r')
curl -i -H "If-None-Match: $ETAG" http://localhost:8080/api/settings/public
# Expected: HTTP 304, empty body
```

Use the **exact** `ETag` value from the first response — not a placeholder.

---

## Redis unavailable / not installed

| Setting | Behaviour |
|---------|-----------|
| `cacheDriver: auto` | Memory + file chain (default) |
| `cacheDriver: file` | File only |
| `cacheDriver: redis` | Normalized to `auto`; probe reports Redis as unavailable |

**No action required** on Classic hosts without Redis. Content is always read from flat files on cache miss.

When Redis is added in a future iteration, explicit `redis` mode with a down broker should surface probe diagnostics; fallback policy will be documented in release notes.

---

## Stale cache suspected

Symptoms: published content not visible, old list after publish, feeds out of date.

1. Confirm the write succeeded (audit log, file mtime under `data/content/`).
2. **Admin purge:** Settings → Cache (or `POST /api/admin/cache/purge` with scope `content`).
3. **CLI:** `php backend/bin/console cache:purge-content` (if available in deployment).
4. Re-test public GET; ETag must change after content change.

Generation bump on publish is automatic — manual purge is only needed after incidents or cache bugs.

---

## Full cache delete / rebuild

Safe on Classic: deleting `data/cache/*.cache` does **not** lose content.

1. Stop traffic or accept brief miss storm (stampede protection: `rememberLocked` + flock).
2. Delete cache files or run admin purge scope `all`.
3. Warm critical routes (`/api/settings/public`, `/api/pages`, `/api/articles`).
4. Monitor hit/miss metrics in admin stats.

---

## Failure scenarios

| Scenario | Expected behaviour |
|----------|-------------------|
| Cache write fails after SSOT write | Old cache may serve until TTL/invalidation; SSOT remains correct |
| Invalidation fails | Generation bump + tag delete are best-effort; purge content scope |
| Redis timeout (future) | Short timeout; fall back to file per engine policy |
| Corrupt cache file | Miss → rebuild from SSOT; delete offending `.cache` file |

---

## Related

- [ITERATION_69.md](../ITERATION_69.md) — Definition of Done
- [HYBRID_ENGINE.md](../architecture/HYBRID_ENGINE.md) — HE-2 wave
- It.45 / It.49 — absorbed into It.69 (reference designs only)
