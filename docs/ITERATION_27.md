# Iteration 27 – Admin View Modes + SEO Metadata Panel

**Status:** Complete  
**Version:** 2.0.15  
**Release track:** Admin UX — list layouts + SEO workflow

## Summary

Unified **admin display modes** (list / list with preview / preview-only grid) across Media Library, Articles, and Pages. Extended **SEO metadata panel** — alt text, tags, meta description, OG image, robots, canonical — with inline editing and list-level **SEO health** indicators and filters.

## Logical sequence

```
It.24 (DAM) → It.26 / 2.0.14 (lightbox + binary I/O) → It.27 (view modes + SEO admin UX)
It.23 (SeoMetaBuilder — public head tags) ─────────────────────────────────────────────┘
```

## Scope

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | `AdminViewMode` — `list` \| `list-preview` \| `preview` + persistence (localStorage) | ✅ |
| 2 | **Media Library** — mode toggle (unify existing grid with table + compact gallery) | ✅ |
| 3 | **Articles / Pages** (`PagesManager`) — three modes instead of table-only | ✅ |
| 4 | **SEO metadata panel** — sidebar or tab in editor + inline on media cards | ✅ |
| 5 | **SEO health badges** in lists (missing alt, description, tags, OG) | ✅ |
| 6 | Backend — front matter / sidecar fields + validation + optional audit API | ✅ (fields) / ⏳ (`GET /api/content/seo-audit` deferred) |
| 7 | Tests (Vitest + PHPUnit) + docs | ✅ (Vitest; audit API PHPUnit deferred) |

---

## Part 1 – Admin view modes (`AdminViewMode`) ✅

**Goal:** Editors choose how to browse content — same control on `/media`, `/articles`, `/pages`.

| Mode | UI | Best for |
|------|-----|----------|
| **List** (`list`) | Table — columns, sort, bulk select | Managing many items quickly |
| **List with preview** (`list-preview`) | Table row + thumbnail (media thumb; article featured/OG image) | Scan + visual identification |
| **Preview** (`preview`) | Card / masonry grid (existing Media grid) | Visual selection, DAM workflow |

### Frontend components

| File | Responsibility |
|------|----------------|
| `frontend/src/components/backend/AdminViewModeToggle.tsx` | Three-icon toggle (List / List+Image / Grid) |
| `frontend/src/hooks/useAdminViewMode.ts` | State + `localStorage` per section (`admin.viewMode.media`, `.articles`, `.pages`) |

Optional later: sync preference to user settings API (see It.38 feature flags / user prefs).

### Where to apply (MVP)

| Section | Route | Notes |
|---------|-------|-------|
| Media Library | `/media` | Grid = `preview`; add `list` + `list-preview` |
| Articles | `/articles` | Today: table-only in `PagesManager` |
| Pages | `/pages` | Same component as articles |
| Navigation | `/navigation` | Out of MVP unless icons/assets added |
| Comments / Users | — | Table-only is sufficient |

---

## Part 2 – SEO metadata panel ✅

**Backend already exists (It.23):** `SeoMetaBuilder`, `GET /api/seo/{type}/{slug}`, settings group `seo`.

**It.27 adds admin UX** so editors fill SEO fields without editing raw front matter.

### Fields by entity

| Field | Media (sidecar) | Page / Article (front matter) | Public SEO API |
|-------|-----------------|-------------------------------|----------------|
| Alt text | ✅ `altText` | — (inline images via media) | OG fallback |
| Display title | ✅ `title` | ✅ `title` | `%title%` in template |
| Meta description | — | ✅ `description` / `metaDescription` / `seoDescription` | `<meta name="description">` |
| Tags | — | ✅ `tags[]` | RSS / internal search |
| OG image | media URL | ✅ `ogImage` / featured image | `SeoMetaBuilder` openGraph |
| Robots | — | ✅ per-page override | `<meta name="robots">` |
| Canonical | — | ✅ optional | `<link rel="canonical">` |

### UI deliverables

1. **`SeoMetadataPanel.tsx`** — form with character hints (title ~60, description ~160 recommended)
2. **`MarkdownEditor`** — “SEO” tab or side drawer
3. **`MediaManager`** — alt/title edit; **list / list-preview** open `MediaMetadataModal` (responsive dialog, no table overlap); **preview grid** keeps inline edit on cards
4. **`MediaMetadataModal.tsx`** — title + alt textarea, image preview, live SEO badge, Escape / backdrop close
5. **`SeoHealthBadge.tsx`** — status: OK / warning / missing

### List-level SEO overview

In **list** and **list-preview** modes, show column or badge:

| Status | Condition |
|--------|-----------|
| 🟢 OK | Required SEO fields present for published content |
| 🟡 Warning | Missing description or alt on key asset |
| 🔴 Critical | Published without meta description / image without alt |

**Filter:** “Show only items with SEO issues”.

---

## Part 3 – Backend extensions ✅ (audit API deferred)

| Area | Change |
|------|--------|
| Content front matter | Document + validate `metaDescription`, `ogImage`, `robots`, `canonical`, `seoTitle` |
| Media sidecar | Optional `tags[]`, `caption`, `seoDescription` |
| `MediaRepository` / `ContentRepository` | PATCH fields; optional bulk SEO export (CSV/JSON) |
| `GET /api/content/seo-audit` | List items with missing fields — **deferred** to backlog |

**Rule:** Reuse `SeoMetaBuilder` — no duplicate SEO logic; only persist fields in flat-file and expose in admin forms.

---

## Part 4 – Out of scope (It.27)

| Feature | Target iteration |
|---------|------------------|
| Full-page live preview iframe | [It.31 – Live Preview](ITERATION_BACKLOG.md) |
| Bulk multi-select SEO patch | [It.28 – Bulk actions](ITERATION_BACKLOG.md) |
| AI-generated meta descriptions | Backlog / plugin |
| Server-side admin pagination | [It.36 – Pagination](ITERATION_BACKLOG.md) (may run in parallel) |
| Scoped section FileManager | [It.40 – Section FileManager](ITERATION_BACKLOG.md) |

---

## Test plan

1. Media → switch List / List+Preview / Grid → preference survives page reload.
2. Articles → list-preview shows featured image or placeholder.
3. Article editor → SEO panel → save → `GET /api/seo/article/{slug}` returns updated values.
4. Media without alt → warning badge in grid.
5. Filter “SEO issues” → only incomplete items shown.
6. Media list mode → pencil opens modal → save → table updates without column overlap.
7. PHPUnit: front matter validation (deferred); Vitest: `AdminViewModeToggle`, `SeoHealthBadge`, `MediaMetadataModal` flow via `MediaManager`.

---

## Deploy

Frontend + backend (`paginium-deploy`). No database migration — flat-file fields and sidecar JSON only.

---

## Related documents

- [ITERATION_9.md](ITERATION_9.md) — prototype port (nav, comments, contact)
- [ITERATION_23.md](ITERATION_23.md) — `SeoMetaBuilder` (backend complete)
- [ITERATION_24.md](ITERATION_24.md) — DAM sidecar (alt, title)
- [ITERATION_26.md](ITERATION_26.md) — media lightbox + 2.0.14 binary hotfix
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) — It.28+
