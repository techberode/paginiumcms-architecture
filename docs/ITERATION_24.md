# Iteration 24 – Full DAM v1 (Media Library)

**Status:** Complete  
**Version:** 2.0.12  
**Release track:** post-2.0.11 (Iteration 8 completion — folders, sidecar metadata, bulk ops)

## Summary

Extends the basic Media Manager (2.0.4) into a folder-aware Digital Asset Manager: nested folders, `.meta.json` sidecar metadata per asset, bulk delete, and admin `media` settings for MIME types and upload size limits.

## Logical sequence

```
It.8 partial (FE grid/upload) → It.24 (folders + sidecar + bulk + settings)
```

## Scope

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | Folder-aware storage (`media/{folder}/…`) + `media/folders.json` index | ✅ |
| 2 | `.meta.json` sidecar (altText, title, folder) | ✅ |
| 3 | `GET/POST /api/media/folders`, `POST /api/media/bulk-delete` | ✅ |
| 4 | Settings group `media` (`allowedMimeTypes`, `maxUploadSizeKb`) | ✅ |
| 5 | FE folder nav, bulk select/delete, title + alt edit | ✅ |
| 6 | PHPUnit + Vitest | ✅ |

**Deferred (future):** asset locking, thumbnails, bulk move, caption/tags UI.

---

## Part 1 – Backend repository ✅

**Service:** `backend/app/Modules/Media/Services/MediaRepository.php`

| Feature | Implementation |
|---------|----------------|
| Folders | `normalizeFolder()`, `createFolder()`, `listFolders()`, upload param `folder` |
| Sidecar | `{path}.meta.json` with altText, title, folder, updatedAt |
| Bulk delete | `bulkDelete(array $paths): int` — skips missing paths |
| Settings | MIME allow-list + max upload size from `SettingsRepository::group('media')` |

**Registry files:**
- `media/registry.json` — asset index (unchanged shape + `folder`, `title`)
- `media/folders.json` — empty folder paths
- `media/{folder}/.paginium-folder` — on-disk folder marker

---

## Part 2 – API ✅

| Method | Route | Auth | Notes |
|--------|-------|------|-------|
| GET | `/api/media?folder=` | EDITOR+ | List with optional folder filter |
| GET | `/api/media/folders` | EDITOR+ | Returns `['', 'campaigns', …]` |
| POST | `/api/media/folders` | `media:upload` | Body: `{ "folder": "campaigns/2026" }` |
| POST | `/api/media/upload` | `media:upload` | Multipart: `file`, `altText`, `folder` |
| PATCH | `/api/media/{path}` | `media:upload` | Body: `{ "altText", "title" }` |
| POST | `/api/media/bulk-delete` | `media:upload` | Body: `{ "paths": ["media/…"] }` |
| DELETE | `/api/media/{path}` | `media:delete` | Single delete |

---

## Part 3 – Settings ✅

**Group `media` in `SettingsSchema`:**
- `allowedMimeTypes` — comma-separated list
- `maxUploadSizeKb` — default 5120 (5 MB)

---

## Part 4 – Frontend ✅

**Files:**
- `frontend/src/api/media.ts` — folders, bulk delete, metadata update
- `frontend/src/components/backend/MediaManager.tsx` — breadcrumb, folder cards, checkboxes, bulk delete, title edit, stock import

---

## Part 5 – Stock knižnica (prototype „Generovať z databázy“) ✅

**Nie je to SQL databáza** — flat-file katalóg `backend/app/Modules/Media/Data/stock-images.json` (Unsplash URL + alt/title).

| Topic | Zameranie |
|-------|-----------|
| `tech` | IT / vývoj (predvolené) |
| `business` | Firemný web |
| `food` | Varenie / gastronómia |
| `travel` | Cestovanie |
| `health` | Fitness / wellness |
| `nature` | Príroda |
| `general` | Neutrálne |

Settings `media`: `stockImagesEnabled`, `stockImageTopic`  
API: `GET /api/media/stock-topics`, `POST /api/media/stock-import`  
Backend stiahne obrázok z Unsplash a uloží ho do Media Library (nie len externý link).

---

## Tests ✅

- `MediaRepositoryTest` — folders, sidecar, bulk, settings mock
- `MediaControllerTest` — folders API, upload into folder, bulk delete
- `StockImageCatalogTest`, `StockImageImporterTest`
- `MediaManager.test.tsx` — folder navigation + stock button mock

---

## Deploy notes

After pull on backend host, no migration needed — new folders/sidecars are created on first upload or folder create.

Frontend: `paginium-deploy` (or `npm run build:prod` + rsync).
