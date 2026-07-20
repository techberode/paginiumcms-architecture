# Iteration 44 – Filters, sorting & public blog pagination

**Status:** ✅ Complete (It.44a–c FE + It.44d BE)  
**Version:** 2.0.37

## Summary

It.44 adds consistent filtering and sorting across admin and public lists. The first delivered slice focuses on the **public blog**: settings-driven list pagination, URL-synced filters, sort options, and prev/next article navigation without returning to the list.

## Done (public blog — Iteration 44a)

| Item | Description |
|------|-------------|
| `content.blogItemsPerPage` | Settings field (default **6**) — separate from admin `itemsPerPage` |
| List pagination | Active when published articles > `blogItemsPerPage` |
| URL sync | `/blog?page=&tag=&sort=` (`newest` \| `oldest` \| `title`) |
| Tag filter | Server-side facet buttons (`meta.tags`), synced to `?tag=` |
| Sort dropdown | Newest / oldest / title on public blog |
| Article prev/next | Navigate between articles in global sort order |
| Utils | `frontend/src/utils/blogArticles.ts` + Vitest |

## Done (admin lists — Iteration 44b)

| Item | Description |
|------|-------------|
| `AdminListFilterBar` | Shared alias over `AdminListToolbar` |
| `useAdminListQueryParams` | URL sync for pages/articles admin lists (`?q=&status=&sort=&page=&seo=1`) |
| `PagesManager` | Wired to filter hook + „Vymazať filtre“ |
| `ui.openLinksInNewTab` | Settings toggle (default **false** = same tab) |
| Link helpers | `useOpenLinksInNewTab`, `linkTargetProps`, `openExternalUrl` |

### `ui.openLinksInNewTab` applies to

- Content preview links (PagesManager, mobile card, ContentEditorShell)
- Admin header „Prejsť na web“
- Media non-previewable file open
- Footer demo external link
- Firewall docs link

## Settings

| Key | Group | Default | Usage |
|-----|-------|---------|-------|
| `content.blogItemsPerPage` | Obsah | 6 | Public blog cards per page |
| `content.showReadingTime` | Obsah | true | Odhad času čítania na blogu |
| `content.itemsPerPage` | Obsah | 20 | Admin lists + API pagination default |
| `ui.openLinksInNewTab` | Admin UI | false | New tab for previews / external URLs |

Configure in **Admin → Nastavenia → Obsah** and **Admin UI**.

## Done (admin lists — Iteration 44c)

| Item | Description |
|------|-------------|
| `useMediaListQueryParams` | URL sync: `?q=&folder=&type=image&sort=&page=&seo=1` |
| **MediaManager** | Folder, type filter, search, sort, SEO filter, pagination → URL |
| **CommentsManager** | `?q=&status=&sort=&page=` + „Vymazať filtre“ |
| **TrashManager** | `?q=&sort=&page=` + „Vymazať filtre“ |

## Done (backend — Iteration 44d)

| Item | Description |
|------|-------------|
| `filter[tag]` / `?tag=` | Exact tag match (case-insensitive) on content index |
| `filter[author]` / `?author=` | Author substring filter |
| `date_from` / `date_to` | Filter by article `createdAt` / front matter `date` (YYYY-MM-DD) |
| Public blog API | `GET /api/articles?page=&per_page=&tag=&sort=` — server-side list |
| Response meta | `tags[]`, `total_published` on paginated article lists |
| FE `BlogRenderer` | List + detail fetch from API; bootstrap bez full `/api/articles` |

## Remaining

_None — It.44 complete._

## Tests

```bash
./scripts/iteration-gate.sh
cd frontend && npm test -- --run src/utils/blogArticles.test.ts
cd backend && vendor/bin/phpunit --filter 'ContentRepositoryTest|ContentControllerTest'
```

| Test | Súbor / oblasť |
|------|----------------|
| Tag / author / date index filters | `ContentRepositoryTest` |
| Public paginated articles + meta | `ContentControllerTest` |
| `blogSortToApiSort` | `blogArticles.test.ts` |
| CONTENT_API params | `docs/architecture/CONTENT_API.md` |

## Related docs

- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) — It.44 overview
- [architecture/SETTINGS.md](architecture/SETTINGS.md)
- [architecture/CONTENT_API.md](architecture/CONTENT_API.md)

## Next

→ It.15 PluginManager (gate pre It.53–58) alebo It.32 performance (public bootstrap summary API)
