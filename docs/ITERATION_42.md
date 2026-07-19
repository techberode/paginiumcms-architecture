# Iteration 42 — Admin item counts

**Status:** ✅  
**Priority:** 🟡

## Backend

- `AdminCountsService` — aggregates entity counts from flat-file repositories
- `GET /api/admin/counts` — role-aware response:
  - **EDITOR+:** `pages`, `articles`, `media`, `backups`
  - **ADMIN+:** also `comments`, `messages`, `trash`, `users`
- Settings `ui.showListCounts` (default `true`) — global toggle for sidebar badges

## Frontend

- `getAdminCounts()` + `useAdminCounts()` hook
- `AdminSidebar` — badge counts from API (replaces client-side `pages.length` / `articles.length`)

## Tests

- `CountsControllerTest` — editor vs admin field visibility

## Disable badges

Settings → **Admin UI** → disable **Zobraziť počty v sidebari**
