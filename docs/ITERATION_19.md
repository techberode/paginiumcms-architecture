# Iteration 19 – FlatFile Index, Pagination & Search API

**Status:** Complete  
**Version:** 2.0.7

## Summary

Scalable content listing via flock-safe flat-file index, paginated API responses with `meta`, fulltext search, and dual storage drivers (Markdown default + JSON optional).

## Backend

| Path | Role |
|------|------|
| `Core/FlatFile/Services/ContentIndexService.php` | Index at `data/index/content.json` |
| `Http/Support/JsonResponder.php` | Unified `{ success, data, meta? }` (introduced here) |
| `Http/Support/PaginationMeta.php` | `page`, `per_page`, `total`, `total_pages` |
| `Http/Support/PaginationQuery.php` | Query param parsing |
| `ContentRepository` | Index hooks on save/delete; reads `.md` and `.json` |
| `MarkdownContentStorage` / `JsonContentStorage` | Dual storage drivers |
| `SearchController` | `GET /api/search?q=` (min 2 chars, published only) |
| `SettingsSchema` | `content.storageFormat` (`md` \| `json`) |

### API behaviour

| Request | Response |
|---------|----------|
| `GET /api/pages` (no pagination params) | Full array in `data`, **no** `meta` (legacy) |
| `GET /api/pages?page=1&per_page=20` | Paginated `data` + `meta` |
| Unauthenticated list | Only `published` items |
| Admin session | All statuses |

## Frontend

| File | Role |
|------|------|
| `src/api/search.ts` | Typed search client |
| `src/components/backend/PagesManager.tsx` | Server pagination + search + status filter |
| `src/components/public/SiteSearchModal.tsx` | `/api/search` with client fallback |
| `ApiResponse.meta` | Pagination type in `client.ts` |

## Tests

- `ContentRepositoryTest` – index rebuild, pagination, search
- `ContentControllerTest` – meta shape, published filter, search
- `SettingsRepositoryTest` – `storageFormat` field

## Optional follow-up

- CLI `content:migrate --to=json`
- Pagination on `/api/media`, `/api/admin/messages` lists

## Related docs

- [CONTENT_API.md](architecture/CONTENT_API.md)
- [STORAGE.md](architecture/STORAGE.md)
- [API_CONTRACT.md](architecture/API_CONTRACT.md) – pagination §3

## Next

→ [Iteration 20](ITERATION_20.md) – core hardening & production readiness
