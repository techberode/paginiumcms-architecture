# Query index — optional SQLite projection (It.92)

> **Status:** ✅ **It.92 complete** in tree (September 2026)  
> **Canonical iteration spec:** [ITERATION_92.md](../ITERATION_92.md)  
> **Immutable rule:** [NOSQL_MANDATE.md](./NOSQL_MANDATE.md) — files remain SSOT

---

## Why this iteration is a pivot (not “we added SQL”)

PaginiumCMS has always been honest about **files as the contract**: Markdown pages, JSON settings, flat users, portable backups. That does not change.

What *does* change with **It.92** is the **Hybrid Engine maturity story**:

| Before It.92 | After It.92 (when enabled) |
|--------------|----------------------------|
| Every catalog query loads and filters **`content.json` in PHP** | Same SSOT writes; **optional** SQLite answers list/search/facet queries |
| Growth hits **CPU + full-file read** on admin lists and public blog index | **Indexed reads** (WAL + FTS5) for catalog routes only |
| Performance Guard could only suggest **cache purge** | Guard can **suggest** enabling the derived index from real p95 signals |
| “No SQL” read as “never SQLite” | “No SQL **as SSOT**”; SQLite is explicitly **disposable**, like cache |

**Restaurant metaphor:** the kitchen still writes orders on paper (SSOT). The query index is a **duplicate ticket rail** hung above the pass — if it falls down, cooks still read the original tickets (`content.json` + rebuild).

---

## What SQLite is used for (and what it is not)

### In scope for It.92

- **Content catalog operations:** paginated lists, filters (status, locale, tags, categories), admin search, public blog listing, editorial calendar slices that already use the JSON index.
- **Location:** `data/index/content.sqlite` next to `data/index/content.json`.
- **Activation:** SUPER_ADMIN, Settings → Hybrid Engine — run **Rebuild** first, then **Activate sqlite**. `verifyActivation()` checks an existing `content.sqlite` (readable, integrity, JSON parity); it does **not** rebuild during activate. Activating without a rebuilt file returns **422** and keeps `queryIndexDriver=json`.

### Explicitly out of scope

- Storing **page body**, users, sessions, comments, settings, or plugin state in SQLite.
- **GET by slug** for a single published document (still read the Markdown/JSON file).
- PostgreSQL/MySQL, shared SQLite on NFS, plugin-defined SQL schemas, automatic enable via Performance Guard `automatic` mode.

---

## Where enabling SQLite leads (operator outcomes)

1. **Large sites (thousands of entries)** — Admin content lists and public `/api/articles`-style catalogs stay responsive without raising PHP memory for a full JSON decode on every request.
2. **Search** — FTS5 over title/slug/excerpt/tags (bounded fields already in `ContentIndexEntry`), with bound parameters only (no user SQL). SQL uses the FTS **table name** in `MATCH` (e.g. `entries_fts MATCH :match`), not a table alias — SQLite rejects `alias MATCH`. User input is tokenized for FTS (drops `OR`/`AND`/`NOT`, quotes, numeric-only tokens); it is never passed as raw FTS grammar (injection like `"Hello" OR 1=1` matches only `Hello`).
3. **Operations** — Missing or corrupt `.sqlite` → **runtime JSON fallback** (settings may still say `sqlite` until operator or auto-fallback changes it), **throttled monitoring alert**, health warning, **rebuild CLI** (`query-index:rebuild`). Backups may omit `.sqlite`; restore + rebuild is supported.
4. **Safety** — SSOT write **always succeeds first**; SQLite sync failure = **incident**, not rolled-back article. Same pattern as cache lag.
5. **Product positioning** — Paginium stays **No-SQL SSOT** for compliance and GitOps; SQLite is an **optional performance layer** documented in the mandate, not a hidden database migration.

---

## Layer diagram

```text
                    ┌─────────────────────────────┐
                    │  Markdown / JSON documents   │  ← SSOT (only authority)
                    └──────────────┬──────────────┘
                                   │ write (OCC, locks)
                                   ▼
                    ┌─────────────────────────────┐
                    │   ContentIndexService      │
                    │   → data/index/content.json │  ← derived (always)
                    └──────────────┬──────────────┘
                                   │ optional dual-write (92c)
                                   ▼
                    ┌─────────────────────────────┐
                    │   SqliteQueryIndex (92b)     │
                    │   → content.sqlite + FTS5    │  ← derived (optional)
                    └──────────────┬──────────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │  QueryIndexInterface        │  ← callers (lists/search)
                    │  JsonQueryIndex | SQLite    │
                    └──────────────┬──────────────┘
                                   ▼
                    ┌─────────────────────────────┐
                    │  It.69 cache + HTTP validators │
                    └─────────────────────────────┘
```

**Default path today:** `QueryIndexFactory` → `JsonQueryIndex` → existing `ContentIndexService` (**92a**).

### Operator workflow (sqlite)

1. Engine settings → **Rebuild query index** (or `php backend/bin/console query-index:rebuild`).
2. Confirm health / admin status shows SQLite file + parity counts.
3. **Activate sqlite** — probe must pass; on failure fix directory permissions or rerun rebuild.
4. Optional: enable runtime watch; use **Activate json** or delete `content.sqlite` to return to JSON-only reads.

---

## Settings (Engine group)

| Key | Default | Meaning |
|-----|---------|---------|
| `queryIndexDriver` | `json` | `json` \| `sqlite` (sqlite only after probe in 92c) |
| `queryIndexAdviseEnabled` | `true` | Performance Guard may emit `query_index_sqlite` |
| `queryIndexAdviseMinEntries` | `2000` | Do not recommend on tiny catalogs |
| `queryIndexAdviseListP95Ms` | `0` | `0` = use `performanceGuardLatencyMsWarning` |
| `queryIndexRuntimeWatchEnabled` | `true` | When driver is `sqlite`, run per-request watch (middleware + factory) |
| `queryIndexAutoFallbackOnFailure` | `false` | If `true`, set `queryIndexDriver=json` after a detected failure (alert + audit log) |
| `queryIndexFailureAlertCooldownSeconds` | `900` | Min seconds between duplicate incident types (`query_index.sqlite_failure`) |

### Runtime watch (alert-first)

When `queryIndexDriver=sqlite` and `queryIndexRuntimeWatchEnabled=true`, `QueryIndexWatchMiddleware` (stacked like Performance Guard) and `QueryIndexFactory` call `QueryIndexFailureHandler`:

- Detects: missing/unreadable file, PDO unavailable, integrity fail, JSON/SQLite entry parity lag, query-time fallback.
- Sends **`query_index.sqlite_failure`** via `IncidentNotifier` (requires **`monitoring.alertsEnabled`** and configured channels — same as other ops incidents).
- **Default:** alert only; catalog reads already use `JsonQueryIndex` / `FallbackQueryIndex` so the public site stays up.
- **Optional:** `queryIndexAutoFallbackOnFailure=true` persists `queryIndexDriver=json` and emits **`query_index.auto_fallback`**.

**Never** auto-set `queryIndexDriver=sqlite` from Guard `automatic` remediation (same rule as Redis).

---

## Security and plugins

- No arbitrary SQL API for admin UI or plugins ([It.89](../ITERATION_89.md) does not grant SQL).
- PDO + bound parameters; `PRAGMA` allow-list; path under `data/index/` with traversal checks.
- `data/` and `cache/` remain non–web-reachable.

---

## Implementation slices (reference)

| Slice | Deliverable |
|-------|-------------|
| **92a** | `QueryIndexInterface`, `JsonQueryIndex`, `QueryIndexFactory` (JSON only) |
| **92b** | `SqliteQueryIndex`, schema, FTS5, rebuild |
| **92c** | Settings switch, dual-write, fail-open to JSON |
| **92d** | Guard advisor `query_index_sqlite` |
| **92e** | Admin UI, health checker, CLI |
| **92f** | Tests, mandate cross-links, runtime watch, SK/EN admin copy, CHANGELOG on ship |

---

## Related documents

- [HYBRID_ENGINE.md](./HYBRID_ENGINE.md) — layered model  
- [STORAGE.md](./STORAGE.md) §8 — index contract  
- [ITERATION_71.md](../ITERATION_71.md) — Performance Guard  
- [ITERATION_19.md](../ITERATION_19.md) — original JSON index  
- [ITERATION_68.md](../ITERATION_68.md) — storage abstraction  
