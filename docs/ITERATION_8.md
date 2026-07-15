# Iteration 8 – Media Manager (Frontend)

**Status:** Complete  
**Version:** 2.0.4

## Summary

Iteration 8 connects `/api/media` to the admin UI and completes editor integration: WYSIWYG toggle, media picker, and same-origin API URL fix for LAN deploys (`paginiumcms.com`).

## Frontend

| File | Role |
|---|---|
| `src/api/media.ts` | List, upload, patch, delete |
| `src/components/backend/MediaManager.tsx` | Grid, drag & drop, alt edit |
| `src/components/backend/MediaPickerModal.tsx` | Insert image from library (WYSIWYG) |
| `src/components/backend/MarkdownEditor.tsx` | Markdown / WYSIWYG toggle + media insert |
| `src/utils/apiBaseUrl.ts` | Same-origin API base when `VITE_API_URL` unset |

## Deploy note

If `/api/*` returns HTML on your host, configure nginx to proxy `/api` → PHP **before** SPA fallback. See `docs/deploy/NGINX_API.md`.

## Tests

- `MediaRepositoryTest`, `MediaControllerTest` (PHPUnit)
- `media.test.ts`, `MediaManager.test.tsx`, `apiBaseUrl.test.ts` (Vitest)

## Iteration 9 (prototype FE port)

Navigation, comments, contact inbox, GitHub panel — see `docs/ITERATION_9.md`.

## Backend (unchanged API)

| Method | Route | Description |
|---|---|---|
| GET | `/api/media` | List media (`?type=image`, `?mimeType=...`) |
| POST | `/api/media/upload` | Multipart upload (`file`, optional `altText`) |
| PATCH | `/api/media/{path}` | Update metadata (alt text) |
| DELETE | `/api/media/{path}` | Remove file + registry entry |

Auth required on all routes (session cookie).

## Frontend

| File | Role |
|---|---|
| `src/api/media.ts` | Typed media API + `resolveMediaUrl`, `formatMediaSize` |
| `src/components/backend/MediaManager.tsx` | Grid UI, upload zone, alt edit, delete |
| `src/App.tsx` | `/media` route wired to `MediaManager` |

Removed: `MediaPlaceholder.tsx` (stub replaced by real manager).

## Tests

- `MediaRepositoryTest` (PHPUnit) – upload, filter, update, delete, mime validation
- `MediaControllerTest` (PHPUnit) – auth, list, upload, patch, delete integration
- `media.test.ts` (Vitest) – URL/size helpers
- `MediaManager.test.tsx` (Vitest) – grid render, empty state, search filter

## Out of scope (future)

- Full DAM: nested folders, `.meta.json` sidecar, bulk move/delete
- Asset locking integration (Iteration 1)
- WYSIWYG / Monaco picker integration
- Developer unlock UI polish

## Next (Iteration 9+)

- Demo module API routes (`DEMO_MODE`)
- WYSIWYG editor in MarkdownEditor
- RSS/sitemap feeds
