# Iteration 92 — Hybrid Engine query index (SQLite derived) + Performance Guard advisor

> **Status:** ✅ **complete** (September 2026) — slices **92a–92f** in tree; gate green  
> **Pivot explainer (operators + devs):** [architecture/QUERY_INDEX.md](architecture/QUERY_INDEX.md)  
> **Priority:** 🟡 P1 for large catalogs; Classic JSON index remains the default  
> **Wave:** Hybrid Engine (extends [It.68](ITERATION_68.md) storage, [It.19](ITERATION_19.md) `content.json`, [It.71](ITERATION_71.md) Performance Guard)  
> **Depends on:** It.68 `StorageInterface`, It.71 Performance Guard samples/remediation, existing `ContentIndexService`  
> **Does not replace:** Markdown/JSON SSOT, `data/index/content.json` as the Classic index, Redis/cache (It.69), It.89 plugin capabilities

## Goal

Add an **optional derived query engine** so listings, filters, and search stay fast when the published catalog grows — **without** making SQL the source of truth.

1. **Files stay authoritative.** Pages, articles, users, settings, and security state remain JSON/Markdown. SQLite is a disposable projection, same class as `content.json` and cache.
2. **Default is unchanged.** Classic mode keeps the JSON index. Missing `pdo_sqlite` or a missing DB file must not take the CMS down.
3. **Activation mirrors Performance Guard.** Off by default. The administrator enables it in Settings → Engine after a capability probe. Performance Guard **recommends** enablement from load signals; **`automatic` remediation must never turn SQLite on** (same rule as Redis).
4. **One kitchen, two indexes.** `ContentIndexService` writes SSOT files first, then updates the active query driver. A failed SQLite update is an incident, not a rolled-back article.

This is **not** “Paginium becomes a SQL CMS.” It is Hybrid Engine HE query-index: gumový obal, oceľové jadro.

---

## What already exists (reuse — do not fork)

| Piece | Location | Role in 92 |
|-------|----------|------------|
| Content index | `ContentIndexService`, `data/index/content.json` | JSON driver; listing/search/pagination today |
| Index contract | [STORAGE.md](architecture/STORAGE.md) §8 | rebuildable, atomic, no secrets |
| Storage writes | It.68 `StorageInterface` | SSOT still goes through files |
| Performance Guard | `PerformanceGuardMiddleware`, `PerformanceSampleStore`, `SafeRemediationService` | latency/I/O samples + `suggest` / `automatic` |
| Guard settings | `engine.performanceGuard*` | pattern for enable + remediationMode |
| Capability probe | `engine.capabilityProbeEnabled`, cache probe | pattern for `pdo_sqlite` + rebuild probe |
| Health | `StorageChecker` and engine diagnostics | add query-index checker |

---

## Layer in the Hybrid Engine

```text
SSOT write (Markdown/JSON) → ContentIndexService upsert
        ↓
  JSON index (always written in Classic; kept as rebuild source)
        ↓ optional
  SQLite query index (listings / search / distinct tags)
        ↓
  Cache (It.69) → HTTP response
```

| Layer | Source of truth? | May delete? |
|-------|------------------|-------------|
| Documents | ✅ | only via CMS/backup |
| `content.json` | ❌ derived | ✅ rebuild |
| `content.sqlite` | ❌ derived | ✅ rebuild |
| Cache / Redis | ❌ derived | ✅ purge |

Public **GET by slug** stays a file read. SQLite is for **catalogs** (admin lists, public blog index, search, tag/category facets).

---

## Settings (Engine group)

Disabled / JSON by default. Keys follow Guard style (`engine.*` in `SettingsSchema`).

```yaml
engine:
  queryIndex:
    driver: json          # json | sqlite
    adviseEnabled: true   # Guard may emit SQLite recommendation
    adviseMinEntries: 2000
    adviseListP95Ms: 0    # 0 = use performanceGuardLatencyMsWarning
```

| Key | Default | Rule |
|-----|---------|------|
| `queryIndexDriver` | `json` | `json` always valid. `sqlite` only after probe (`pdo_sqlite`, writable `data/index/`, successful rebuild). |
| `queryIndexAdviseEnabled` | `true` | Independent of Guard `enabled`; advisor no-ops if Guard sampling is off (no samples). |
| `queryIndexAdviseMinEntries` | `2000` | Do not nag small sites. |
| `queryIndexAdviseListP95Ms` | `0` | `0` means “use Guard warning budget”. |
| `queryIndexRuntimeWatchEnabled` | `true` | Per-request watch when driver is `sqlite`; throttled `query_index.sqlite_failure` alerts |
| `queryIndexAutoFallbackOnFailure` | `false` | If `true`, persist `queryIndexDriver=json` after failure (plus alert) |
| `queryIndexFailureAlertCooldownSeconds` | `900` | Dedupe window for SQLite failure incidents |

**Never auto-flip `queryIndexDriver` to `sqlite`.** Optional auto-flip **to `json`** only when `queryIndexAutoFallbackOnFailure` is enabled (operator choice). Not in `automatic` remediation, not in health repair, not on deploy.

Disable path: set driver back to `json`; SQLite file may remain and be deleted by rebuild/purge. CMS must boot if the file is missing.

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `QueryIndexInterface` | `query`, `search`, `listDistinctTags`, `listDistinctCategories`, `count`; no raw SQL from callers |
| `JsonQueryIndex` | Adapter over current `ContentIndexService` in-memory filter (default) |
| `SqliteQueryIndex` | WAL, bound parameters only, FTS5 for search; path `data/index/content.sqlite` |
| `QueryIndexFactory` | Reads settings + probe; fail closed to JSON |
| `QueryIndexRebuilder` | Full rebuild from documents (same sources as JSON reindex) |
| `QueryIndexSync` | After successful SSOT upsert/remove, update JSON **and** SQLite if driver is sqlite |
| `QueryIndexAdvisor` | Reads Guard samples + index entry count; emits recommendation code `query_index_sqlite` |
| `QueryIndexChecker` | Health: extension, file, `PRAGMA integrity_check`, row count vs JSON (lag warning) |
| Admin API | status/probe/rebuild/activate; `settings:manage` (SUPER_ADMIN engine fields) |
| CLI | `query-index:rebuild`, `query-index:status` |

`ContentIndexService` remains the owner of the JSON file. SQLite must not become the only place an entry exists.

### SQLite schema (derived, versioned)

Keep it boring: one `entries` table mirroring `ContentIndexEntry` columns needed for list/search, plus FTS5 content-sync. Schema version in `query_index_meta`. Migration = drop file + rebuild (allowed: derived).

### SQL safety

- PDO + bound parameters only; **no** string-concatenated user SQL.
- `PRAGMA` allow-list (`journal_mode=WAL`, `busy_timeout`, `foreign_keys`, `integrity_check`).
- Forbid `ATTACH`, loadable extensions, URI filenames from settings.
- File under `data/index/` with the same path rules as `content.json` (`realpath` + prefix).
- Not web-reachable (same as `data/`, `cache/`).

### Probe before activate

Activate `sqlite` only when all pass:

1. `extension_loaded('pdo_sqlite')`,
2. index directory writable,
3. rebuild completes and `query` of published articles returns the same count as JSON (±0),
4. Classic still works if you then delete the `.sqlite` file and set driver back to `json` (test).

If probe fails → stay on JSON, 422 with a generic operator message (no internals to anonymous clients).

---

## Performance Guard advisor (92d)

Extend `SafeRemediationService::recommendations()` (and incident payload) with a **typed** hint, not only prose.

**Emit `query_index_sqlite` when all are true:**

- `queryIndexDriver === json`,
- `queryIndexAdviseEnabled`,
- Guard has samples (feature used),
- index entry count ≥ `queryIndexAdviseMinEntries`,
- list/search route group p95 ≥ advise threshold  
  (routes: `/api/articles`, `/api/pages`, `/api/content`, public blog list — **templates**, no slugs).

Optional extra signal: high storage-read counters on those route groups (index file rewritten/read whole).

**`suggest` (default):** incident + dashboard card: “Enable SQLite query index (derived; files stay SSOT).” Link to Engine settings. **No settings write.**

**`automatic`:** still **must not** set `queryIndexDriver=sqlite` (copy Redis rule). Automatic stays cache-purge only unless a later iteration adds a reversible derived-only action that is **not** “enable a new engine.” Rebuilding JSON index may remain a separate explicit admin/CLI action.

Advisor copy must not claim a universal SLA. Thresholds are starting points for the operator’s hardware.

---

## Frontend

- Settings → Engine: Query index driver, advise toggle, thresholds, help text (SSOT reminder).
- Probe status: PDO available / last rebuild / entry counts.
- Buttons: **Rebuild**, **Switch to SQLite** (disabled until probe green), **Switch to JSON**.
- Performance Guard dashboard: recommendation chip when `query_index_sqlite` is active; does not auto-apply.
- SK/EN `settings` + `platform` (or APM) module strings. Password/secrets: none.

---

## Security baseline (mandatory)

- AuthN + `settings:manage` (engine is SUPER_ADMIN-only today) on activate/rebuild.
- CSRF on POST.
- No SQL in logs; LogSanitizer for paths/ids only.
- Derived file is not SSOT: backup **may** omit `.sqlite`; restore must rebuild.
- Plugins/themes cannot open the SQLite file or issue SQL (no API for arbitrary queries).
- Fail closed: missing extension → JSON; corrupt DB → JSON + incident, do not serve empty catalog as “success” if JSON still has entries (fallback to JSON driver for that request, alert).

---

## Out of scope

- PostgreSQL / MySQL / Mongo / Turso.
- Users, sessions, settings, comments, or media metadata as SQL SSOT.
- Multi-node shared SQLite on NFS.
- Automatic enable from Guard `automatic` mode.
- Replacing slug GET with SQL.
- Laravel/Eloquent.
- Plugin-authored SQL schemas.

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **92a** | `QueryIndexInterface` + JSON adapter | 🟡 P1 | ✅ | `JsonQueryIndex`, `QueryIndexFactory`, DI |
| **92b** | SQLite driver + rebuild | 🟡 P1 | ✅ | `SqliteQueryIndex`, `QueryIndexRebuilder`, schema/FTS5, path-safe file |
| **92c** | Settings switch + dual-write + fallback | 🟡 P1 | ✅ | `FallbackQueryIndex`, `QueryIndexSync`, catalog reads via `QueryIndexInterface`, probe on sqlite activate |
| **92d** | Guard advisor | 🟡 P1 | ✅ | `QueryIndexAdvisor`, breach hints, APM `advisor_hints`, dashboard deep-link to Engine |
| **92e** | Admin Engine UI + health/CLI | 🟡 P1 | ✅ | `/api/admin/query-index/*`, CLI, `QueryIndexChecker`, Engine panel rebuild/activate |
| **92f** | Tests + mandate docs + runtime watch | 🟡 P1 | ✅ | PHPUnit/PHPStan L8, SK/EN field help, `QueryIndexRuntimeWatch`, CHANGELOG on ship |

**Recommended order:** `92a → 92b → 92c → 92d → 92e → 92f`.

---

## Tests

- JSON driver: existing list/search tests still pass with factory default.
- SQLite: rebuild from fixtures → query/search match JSON counts; FTS finds a title (`entries_fts MATCH`, not alias — see [QUERY_INDEX.md](architecture/QUERY_INDEX.md)).
- Activate sqlite without rebuild/probe → `InvalidArgumentException` (422); driver stays `json`.
- Hostile: user string in search bound, not interpolated (no `OR 1=1` injection).
- Missing `pdo_sqlite` (or probe fail) → activate 422, driver stays json.
- Delete `.sqlite` while driver is sqlite → request falls back to JSON, health warns, no 500 on public list.
- SSOT write succeeds if SQLite sync throws (document on disk; incident).
- Guard `suggest` adds recommendation; does not change `queryIndexDriver`.
- Guard `automatic` does not set sqlite.
- Path escape / `ATTACH` rejected.
- Classic with driver json and no sqlite file = identical to pre-92.

---

## Definition of Done

- [x] Classic default is JSON; small sites never need SQLite.
- [x] SQLite is documented as derived in NOSQL mandate, Hybrid Engine, STORAGE, [QUERY_INDEX.md](architecture/QUERY_INDEX.md).
- [x] Performance Guard recommends enablement from load; never enables sqlite automatically.
- [x] Activate requires probe; disable and delete file leave CMS usable; runtime watch alerts (optional auto-revert to JSON).
- [x] `./scripts/iteration-gate.sh` green.
- [x] SK/EN admin copy (labels, help, tooltips, Engine panel); CHANGELOG on ship ([2.1.0-beta.83](../../CHANGELOG.md#release-2-1-0-beta-83) + post-ship fixes in [2.1.0-beta.84](../../CHANGELOG.md#release-2-1-0-beta-84): FTS `MATCH`, activate probe, deploy token).

---

## Related

- [NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md)  
- [HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md)  
- [STORAGE.md](architecture/STORAGE.md)  
- [ITERATION_71.md](ITERATION_71.md) — Performance Guard  
- [ITERATION_19.md](ITERATION_19.md) — content index  
- [ITERATION_68.md](ITERATION_68.md) — storage abstraction  
- [ITERATION_69.md](ITERATION_69.md) — cache (still the first lever for read traffic)  
- [ITERATION_89.md](ITERATION_89.md) — plugin capabilities (does not grant SQL)
