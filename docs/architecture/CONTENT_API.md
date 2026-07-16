# Content API – Pagination & Search (Iteration 19)

> Version 2.0.8 · Flat-file index at `data/index/content.json`  
> **Security & write access (It. 20):** see [CORE_HARDENING.md](./CORE_HARDENING.md)

## List endpoints

`GET /api/pages` · `GET /api/articles`

### Legacy mode (backward compatible)

No `page` or `per_page` query param → returns full array without `meta`.

```json
{
  "success": true,
  "data": [ { "slug": "home", "title": "...", "status": "published", ... } ]
}
```

### Paginated mode

Query: `?page=1&per_page=20&status=published&search=blog&sort=-updatedAt`

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 143,
    "total_pages": 8
  }
}
```

| Param | Default | Notes |
|-------|---------|-------|
| `page` | 1 | min 1 |
| `per_page` / `perPage` | from `content.itemsPerPage` (20) | max 100 |
| `status` | — | `draft`, `published`, `archived` |
| `search` | — | min 2 chars, matches title/slug/excerpt/tags |
| `sort` | `-updatedAt` | prefix `-` = descending |

### Public access rules

- Unauthenticated requests: only `published` items (list + single slug).
- Authenticated session (cookie): all statuses visible.

## Search endpoint

`GET /api/search?q=home&type=page|article&limit=20`

- Minimum query length: 2 characters.
- Returns only **published** content from index.
- Empty/short query → `{ "success": true, "data": [] }`.

```json
{
  "success": true,
  "data": [
    {
      "slug": "home",
      "type": "page",
      "title": "Domov",
      "status": "published",
      "excerpt": "...",
      "tags": [],
      "updatedAt": "2026-07-16T10:00:00+02:00",
      "path": "pages/home.md"
    }
  ]
}
```

## Content storage format

Setting: `content.storageFormat` → `md` (default) | `json`

| Format | File | Structure |
|--------|------|-----------|
| `md` | `pages/{slug}.md` | YAML front matter + Markdown body |
| `json` | `pages/{slug}.json` | Single JSON object with `content` field + metadata |

Both formats are readable on list/get; new saves use the configured format.

## Soft delete (trash)

`DELETE /api/pages/{slug}` and `DELETE /api/articles/{slug}` move files to `content/trash/` with a `.meta.json` sidecar (not permanent delete). Restore via admin trash API — see [CORE_HARDENING.md](./CORE_HARDENING.md).

## Frontend wiring

| API | Client | UI |
|-----|--------|-----|
| Paginated lists | `useApi().get()` | `PagesManager` |
| Search | `api/search.ts` | `SiteSearchModal` |
| Types | `PaginationMeta` on `ApiResponse` | — |
