# Iteration 42 — Admin item counts + list controls

**Status:** ✅  
**Priority:** 🟡

## Backend

- `AdminCountsService` — aggregates entity counts from flat-file repositories
- `GET /api/admin/counts` — role-aware response:
  - **EDITOR+:** `pages`, `articles`, `media`, `backups`
  - **ADMIN+:** also `comments`, `messages`, `trash`, `users`
- Settings `ui.showListCounts` (default `true`) — global toggle for sidebar badges
- Settings `ui.adminListPageSize` (default `20`) — default rows per admin list page
- Trash bulk actions API:
  - `POST /api/admin/trash/bulk-purge`
  - `POST /api/admin/trash/bulk-backup`
  - `POST /api/admin/trash/empty`
  - `GET /api/admin/trash/backups/{filename}/download`

## Frontend

- `getAdminCounts()` + `useAdminCounts()` hook
- `AdminSidebar` — badge counts from API (replaces client-side `pages.length` / `articles.length`)
- Shared admin list UX:
  - `AdminListToolbar` — search, filter, sort, page-size selector
  - `AdminListPagination` — prev/next footer
  - `useAdminListPageSize()` — per-module override stored in `localStorage`
  - `applyClientListView()` — client-side filter/sort/paginate helper
- Modules updated: **Media**, **Pages/Articles**, **Comments**, **Trash**
- Trash module: bulk **Obnoviť / Zálohovať / Zmazať natrvalo** + **Vysypať kôš**

## Tests

- `CountsControllerTest` — editor vs admin field visibility
- `TrashControllerTest` — bulk purge + empty trash
- `clientListView.test.ts` — filter/sort/paginate helper

## Disable badges

Settings → **Admin UI** → disable **Zobraziť počty v sidebari**

## Default page size

Settings → **Admin UI** → **Položiek na stránku (admin)**  
Per-module override: selector **„X / stránku“** in list toolbar (stored in browser).
