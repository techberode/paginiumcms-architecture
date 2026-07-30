# Iteration 65 — Feature gallery (admin screenshots)

**Status:** ✅ Complete (Phase 1 + Phase 2) — **`v2.1.0-beta.21`**  
**Priority:** ✅ Done  
**Use case:** Present PaginiumCMS admin UI screenshots with feature descriptions — marketing, demo, onboarding.  
**Related:** [It.26 Media lightbox](ITERATION_26.md) · [It.58 Layout builder](ITERATION_58.md) (future gallery block) · [It.64 Footer social](ITERATION_64.md)  
**Out of scope (optional later):** Phase 3 — Ken Burns, deep links, It.58 Gallery block, JSON export/import.

---

## Goal

A dedicated **Gallery** module: upload or pick screenshots from Media, attach titles/descriptions/feature tags, publish on the public site in a **high-quality slider + modal** experience. Full placement and visual options configurable in admin — without hardcoding assets in the theme.

Aligns with project principles: flat-file data, open-source showcase, no paid SaaS layer.

---

## Non-goals (v1)

- Full DAM replacement (Media module stays source of binaries)
- Elementor-style free-form page builder (see It.58)
- Video hosting / YouTube embed gallery (optional later)
- Multi-tenant / per-customer galleries

---

## User stories

| Actor | Story |
|-------|--------|
| **Admin** | I add admin screenshots with title, short description, optional “feature module” tag (Analytics, Newsletter, …) and sort order. |
| **Visitor** | I browse a smooth slider/carousel on the home page or `/features` and open a fullscreen modal with caption + keyboard navigation. |
| **Admin** | I choose layout (grid / slider / hero strip), animation preset, autoplay, and which page shows the gallery — without redeploying frontend. |

---

## Architecture

```
Admin UI (/gallery)
    → PUT/POST /api/admin/gallery/*
    → GalleryRepository (flat-file data/gallery/)
         items/{id}.json + index.json
    → Media paths reference /storage/ or /api/media/file/…

Public site
    → GET /api/gallery/public (published items + settings slice)
    → FeatureGallerySection (home embed or route /features)
         Slider (Embla or CSS scroll-snap + a11y)
         Modal (extend MediaPreviewLightbox patterns)
```

### Flat-file model

**`data/gallery/index.json`** — ordered list of item IDs + publish metadata.

**`data/gallery/items/{id}.json`**

| Field | Type | Notes |
|-------|------|--------|
| `id` | string | `gallery_*` |
| `title` | string | Slide heading |
| `description` | string | Feature explanation (plain or markdown subset) |
| `mediaPath` | string | From Media picker — relative under `media/` |
| `featureTag` | string? | e.g. `analytics`, `newsletter`, `scheduler` |
| `linkUrl` | string? | Optional “learn more” |
| `sortOrder` | int | |
| `status` | enum | `draft` \| `published` |
| `publishedAt` | string? | ISO datetime |

**Settings group `gallery`** (or extend `marketing` if small):

| Key | Type | Purpose |
|-----|------|---------|
| `enabled` | bool | Master switch |
| `placement` | enum | `home`, `route`, `both`, `off` |
| `publicRoute` | string | Default `/features` |
| `layout` | enum | `slider`, `grid`, `hero-strip` |
| `effectPreset` | enum | `subtle`, `cinematic`, `minimal` |
| `autoplayEnabled` | bool | |
| `autoplayIntervalMs` | int | 4000–15000 |
| `showFeatureTags` | bool | Badge on slides |
| `modalCaptionStyle` | enum | `below`, `overlay`, `side` |
| `openLinksInNewTab` | bool | Inherits global UI if unset |

---

## Backend scope

| Component | Responsibility |
|-----------|------------------|
| `Modules/Gallery/` | Repository, validator, public serializer |
| `Http/Routes/gallery.php` | Admin CRUD + public read |
| `GalleryController` (Admin) | Auth + `gallery:manage` permission |
| `GalleryPublicController` | Anonymous GET published items |
| Settings | `SettingsSchema` group `gallery` |

**Security:** mutating routes → `AuthMiddleware` + `PermissionMiddleware('gallery:manage')`; public read only published; media URLs via existing allow-list; captions sanitized; no user HTML in API without sanitizer.

**Permission:** add `gallery:manage` to ADMIN default map; EDITOR read-only optional (phase 2).

---

## Frontend scope

### Admin (`/gallery`)

- List with drag reorder (reuse bulk/dnd patterns from navigation or media grid)
- Create/edit drawer: Media picker, title, description, tag, publish toggle
- **Gallery settings** tab: layout, effects, placement preview mockup
- i18n module `gallery/{sk,en}.ts`

### Public

| Component | Behavior |
|-----------|----------|
| `FeatureGallerySlider` | Touch + keyboard; respects `prefers-reduced-motion` |
| `FeatureGalleryModal` | Fullscreen; prev/next; ESC; focus trap (a11y) |
| `FeatureGalleryGrid` | Fallback layout when `layout=grid` |
| `useGalleryPublic` | React Query → `/api/gallery/public` |

**Visual effects (presets, CSS-first):**

- `subtle` — fade + slight scale on active slide
- `cinematic` — crossfade, soft vignette, lazy blur-up on images
- `minimal` — no autoplay animation; instant swap

Optional dependency: **`embla-carousel-react`** (~3kb gzip) if native scroll-snap is insufficient for autoplay loop — evaluate in Phase 2 spike.

**Integration points:**

- `PublicHomePage` or dedicated `FeaturesPage` route
- Settings-driven: show block only when `gallery.enabled && placement matches`

---

## Phased delivery (recommended)

### Phase 1 — MVP (ship first)

- [x] BE: repository + admin CRUD + public GET
- [x] FE admin: list + form + media picker
- [x] FE public: **grid** + **basic modal** (reuse lightbox UX from It.26)
- [x] Settings: `enabled`, `placement`, `publicRoute`
- [x] PHPUnit + Vitest + smoke in `ITERATION_65.md`
- [x] Seed screenshots — **ops on prod** via Admin → Gallery (repo ships empty `data/gallery/`; no binary assets in git)

**Estimate:** 1 focused iteration (~similar size to It.64 + It.33 slice).

### Phase 2 — Slider & admin polish

- [x] Slider layout + autoplay + pause on hover/focus
- [x] Effect presets (`subtle` / `cinematic` / `minimal`)
- [x] Feature tag badges + filter chips on public page
- [x] Admin live preview panel
- [x] Dynamic `publicRoute` from Settings (single path segment)
- [x] `hero-strip` layout + `modalCaptionStyle` (below / overlay / side)

### Phase 3 — Premium UX (optional)

- [ ] Ken Burns / parallax on hero-strip (CSS only, reduced-motion off)
- [ ] Deep link to slide (`?slide=analytics`)
- [ ] It.58 layout block “Gallery” consuming same API
- [ ] Export/import gallery JSON (backup-friendly)

---

## Acceptance criteria (Phase 1)

- [x] Admin can CRUD gallery items linked to Media files
- [x] Only `published` items appear on public API
- [x] Public modal: keyboard ←/→, ESC, focus trap
- [x] Gallery hidden when `enabled=false`
- [x] PHPStan L8 + iteration gate green
- [x] Docs: `ADMIN_GUIDE`, `CHANGELOG`, this file updated

## Acceptance criteria (Phase 2)

- [x] Settings drive `layout`, `effectPreset`, `autoplayEnabled`, `autoplayIntervalMs`, `modalCaptionStyle`
- [x] Slider/hero-strip: prev/next, dots, keyboard on track, pause on hover/focus
- [x] `prefers-reduced-motion` disables autoplay and animations
- [x] Tag filter chips on public section (when tags exist)
- [x] Admin `/gallery` live preview of published items
- [x] Custom `publicRoute` (e.g. `/funkcie`) resolves via SPA slug intercept

---

## Smoke test

1. Admin → **Gallery** → add item (screenshot + title + description + tag) → publish.
2. Settings → **Gallery** → enable; layout `slider`; effect `cinematic`; autoplay on.
3. Public site — slider visible; hover pauses; click opens modal; filter chips work.
4. Change `publicRoute` to `/funkcie` → Navigation link → page loads gallery.
5. `curl -s http://localhost:8080/api/gallery/public | jq '.data.items | length'`
6. `curl -s http://localhost:8080/api/settings/public | jq '.data.gallery'`

---

## Usage guide (admin)

1. **Upload screenshots** — Admin → **Media** → upload PNG/WebP admin UI captures.
2. **Create gallery items** — Admin → **Feature gallery** (`/gallery`) → **Add screenshot** → pick image from Media, title, description, optional module tag (e.g. `analytics`) → **Published** → Save.
3. **Enable on public site** — Settings → **Site** → **Feature gallery** → enable gallery; choose **Placement**:
   - `route` — only the public route (default `/features`)
   - `home` — embed below home page content
   - `both` — home embed + public route
4. **Layout & effects (Phase 2)** — set **Layout** (`grid` / `slider` / `hero-strip`), **Effect preset**, autoplay interval; optional modal caption style.
5. **Live preview** — on `/gallery`, use the preview panel (published items only; mirrors Settings layout).
6. **Menu link (optional)** — Admin → **Navigation** → new item, path matching `publicRoute` (e.g. `/features` or `/funkcie`).
7. **Verify** — open public route; filter by tag; open modal; test ←/→ and ESC.

`publicRoute` supports a **single path segment** (e.g. `/features`, `/funkcie`). Multi-segment paths are normalized to the first segment.

---

## Decisions

| # | Question | Outcome |
|---|----------|---------|
| 1 | Dedicated route `/gallery` vs `/features`? | **`/features`** default; custom via `publicRoute` |
| 2 | Separate admin menu vs under Content? | **Top-level `/gallery`** |
| 3 | New npm dependency for carousel? | **CSS scroll-snap** (no Embla) — sufficient for autoplay + a11y |
| 4 | Markdown in descriptions? | **Plain text** (Phase 3 optional) |

---

## Relation to layout builder (It.58)

It.65 delivers a **standalone module** first. It.58a can later register a **Gallery block** that calls the same `GET /api/gallery/public` — no duplicate storage.

