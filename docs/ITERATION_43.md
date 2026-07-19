# Iteration 43 — Advanced search (FE + BE)

**Status:** ✅ Implemented  
**Release track:** **Unreleased** (target post-2.0.26)  
**Priority:** 🟡

## Summary

Scoped fulltext search for public site and admin **command palette** (`Ctrl+K`): content, media, and admin module quick jumps — without breaking the legacy flat `/api/search` response for public clients.

## Logical sequence

```
It.19 (content index) → It.22 (discoverability) → It.43 (scoped search + admin palette)
```

## Scope

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | `AdvancedSearchService` + `AdminRouteCatalog` | ✅ |
| 2 | `GET /api/search?scope=&types=` | ✅ |
| 3 | Admin `AdminCommandPalette` (`Ctrl+K`) | ✅ |
| 4 | Public `SiteSearchModal` + `scope=public` | ✅ |
| 5 | PHPUnit + Vitest | ✅ |

---

## Part 1 – Backend ✅

**Service:** `backend/app/Core/Search/Services/AdvancedSearchService.php`

| Scope | Auth | Types | Response |
|-------|------|-------|----------|
| `public` (default) | none | `page`, `article` | flat `data: SearchResult[]` (backward compatible) |
| `admin` | session | `page`, `article`, `media`, `route` | `data: { query, scope, results[], counts{} }` |

Query params: `q` (min 2), `scope`, `types` (comma-separated), `limit` (per type, max 20).

**Draft content:** admin scope uses `ContentIndexService::search(..., publishedOnly: false)`.

**Media:** matches file name, title, alt text, registry path.

**Routes:** static catalog in `AdminRouteCatalog.php` (sidebar modules, role-filtered).

---

## Part 2 – Frontend ✅

| Component | Role |
|-----------|------|
| `AdminCommandPalette.tsx` | Admin quick jump — `Ctrl+K` / `Cmd+K` |
| `ResponsiveLayout.tsx` | Global hotkey listener |
| `frontend/src/api/search.ts` | `searchContent()`, `searchAdmin()` |
| `SiteSearchModal.tsx` | Public instant search (unchanged UX, explicit `scope=public`) |

Recent admin jumps: `localStorage` key `paginium_admin_search_recent` (max 8).

---

## Part 3 – Test infrastructure (same release train) ✅

| Piece | Purpose |
|-------|---------|
| `TestStorageCleaner` | End-of-suite cleanup — generic test artifacts only |
| `backend/bin/test-artifacts.php` | `--scan` / `--purge` CLI |
| `settings.testing.json` | PHPUnit HTTP tests — production SMTP/settings untouched |
| `scripts/run-all-tests.zsh` | Step 12 cleanup, live output, progress bars |

See [developer/TESTING.md](developer/TESTING.md).

---

## Tests ✅

| Suite | File |
|-------|------|
| PHPUnit | `backend/tests/Http/Controllers/Content/SearchControllerTest.php` |
| PHPUnit | `backend/tests/Support/TestStorageCleanerTest.php` |
| Vitest | `frontend/src/components/backend/AdminCommandPalette.test.tsx` |

---

## Usage

```bash
# Public (published only)
curl '/api/search?q=home&scope=public'

# Admin (session cookie required)
curl -b cookies.txt '/api/search?q=set&scope=admin&types=page,route'
```

Admin UI: any admin screen → **Ctrl+K** → ≥2 chars → Enter.

## Related

- [ITERATION_19.md](ITERATION_19.md) — content index (search foundation)
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) — It.44 filters & sort (next)
- [CHANGELOG.md](../CHANGELOG.md) — `[Unreleased]`
- [developer/TESTING.md](developer/TESTING.md) — full test procedure
