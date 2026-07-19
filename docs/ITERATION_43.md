# Iteration 43 — Advanced search (FE + BE)

**Status:** ✅  
**Release:** 2.0.27  
**Priority:** 🟡

## Backend

- `AdvancedSearchService` — unified search with scopes:
  - **`scope=public`** (default) — published pages/articles only; backward-compatible flat array response
  - **`scope=admin`** — requires auth; grouped payload with `results` + `counts`
- Query params: `q`, `scope`, `types=page,article,media,route`, `limit` (per type, max 20)
- `AdminRouteCatalog` — static admin module index for palette jumps
- `ContentIndexService::search()` — optional `$publishedOnly` flag for draft inclusion in admin scope
- Media search matches file name, title, alt text, path

**Endpoint:** `GET /api/search`

## Frontend

- **Admin command palette** — `Ctrl+K` / `Cmd+K` in admin shell (`AdminCommandPalette`)
  - searches pages, articles, media, admin modules
  - keyboard navigation (↑↓ Enter Esc)
  - recent jumps in `localStorage` (`paginium_admin_search_recent`)
- **Public site search** — existing `SiteSearchModal` uses `scope=public` via API client
- API: `searchAdmin()`, extended `searchContent()` in `frontend/src/api/search.ts`

## Tests

- `SearchControllerTest` — public scope, admin auth, draft pages in admin results
- `AdminCommandPalette.test.tsx` — render + navigation

## Usage

```bash
# Public (published only)
curl '/api/search?q=home&scope=public'

# Admin (session cookie required)
curl -b cookies.txt '/api/search?q=set&scope=admin&types=page,route'
```

Admin UI: open any admin screen → **Ctrl+K** → type ≥2 chars → Enter.
