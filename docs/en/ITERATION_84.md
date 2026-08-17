# Iteration 84 — Content presentation, blog sidebar, landing, roles, navigation

> **Status:** ✅ complete (2026-08-17) — slices **84a–84e** shipped; gate green  
> **Priority:** 🟡 medium (product polish + admin power; no Hybrid Engine dependency)  
> **Wave:** Content ops & public presentation (extends It.44 blog, It.56 nav, It.58 layout, It.81 editorial)  
> **Depends on:** shipped content index, shortcode pipeline, `PermissionCatalog`, `navigation.json`, analytics pageviews (admin)  
> **Snapshot:** 2026-08-17 · shipped in `v2.1.0-beta.57`

## Goal

Close five presentation and access gaps discovered during testing and product review:

1. **Categories** for articles (optional pages) — taxonomy beyond flat tags  
2. **Landing page** — marketing home for PaginiumCMS using extended shortcodes  
3. **Blog sidebar** — tags, categories, latest, most-read; admin placement toggles + layout CSS  
4. **Custom roles** — SUPER_ADMIN creates roles with extended permission matrix  
5. **Navigation placement** — top vs side cascade menu; expand effects; deeper levels (SUPER_ADMIN)

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **84a** | Content categories | 🟡 P2 | ✅ shipped | Optional `category` on articles/pages; index facet; public filter; admin picker + registry |
| **84b** | Blog sidebar widgets | 🟡 P1 | ✅ shipped | Settings toggles, `/api/blog/sidebar`, layout CSS, popular sort |
| **84c** | Landing / marketing shortcodes | 🟡 P1 | ✅ shipped | 7 marketing shortcodes + `pgLayout.css` + demo `/paginium-cms` + [LANDING_PAGE.md](user/LANDING_PAGE.md) |
| **84d** | Custom roles (SUPER_ADMIN) | 🔴 P3 | ✅ shipped | Role CRUD + dynamic permission map; migrate from fixed 3-role settings |
| **84e** | Nav placement & cascade UX | 🟡 P2 | ✅ shipped | Top / side / both; animation settings; optional 4th level for SUPER_ADMIN |

Recommended delivery order (lowest risk first):

```text
84c → 84b → 84a → 84e → 84d
```

`84d` last — largest security blast radius.

---

## Current state vs request

### 1. Categories (84a)

| Exists today | Gap |
|--------------|-----|
| Flat **tags** on content (`Content::getTags()`), tag filter on blog, bulk tag assign (It.81b) | No **category** field, no hierarchy, no category archive URLs, pages have tags API but no public category facet |

**Design (basic phase — not full DAM taxonomy):**

| Piece | Proposal |
|-------|----------|
| Storage | Front matter `category: "news"` (string slug) + optional `data/taxonomy/categories.json` registry (id, label, slug, parentId nullable) |
| Index | `ContentIndexService` facet `category`; filter `filter[category]` on articles (and optionally pages) |
| Public | `/blog?category=security` + sidebar category list (84b) |
| Admin | Category picker in editor; SUPER_ADMIN CRUD for registry (or free-text slug in basic phase) |
| i18n | Category labels in registry; content stays locale-specific |

**Stretch (post-84a):** hierarchical categories, `/blog/category/{slug}` pretty routes, sitemap entries.

---

### 2. Landing page (84c)

| Exists today | Gap |
|--------------|-----|
| `layoutTemplate: landing`, `template: home`, shortcodes `landing-hero`, `feature-grid`, `feature-card`, `alert-box`; `PageRenderer` home slug; snippets (It.81f) | No bundled **marketing block set** (CTA, pricing, testimonial, stats); no default PaginiumCMS demo home seed |

**Design:**

| Deliverable | Detail |
|-------------|--------|
| New shortcodes | `cta-banner`, `stats-row`, `testimonial`, `pricing-table` (HTML templates in catalog; policy-scanned) |
| CSS | Extend `frontend/src/theme/pgLayout.css` (`pg-cta`, `pg-stats`, …) |
| Demo content | Optional seed: home page body composing hero + feature grid + CTA (maintainer/demo profile only) |
| Docs | User guide § “Build a landing page in 10 minutes” |

**Non-goals:** It.58f DnD builder (frozen); theme package shell (It.83).

---

### 3. Blog sidebar (84b)

| Exists today | Gap |
|--------------|-----|
| Blog list: tag pills in **header**, sort newest/oldest/title, pagination (It.44); analytics **top articles** admin-only | No **sidebar**; no **most-read** on public blog; `two-column` layout has placeholder aside |

**Settings contract (new group `blogSidebar` or under `content`):**

| Key | Default | Meaning |
|-----|---------|---------|
| `content.blogSidebarEnabled` | `false` | Master switch |
| `content.blogSidebarPlacement` | `right` | `left` \| `right` |
| `content.blogSidebarShowTags` | `true` | Tag cloud / list |
| `content.blogSidebarShowCategories` | `true` | After 84a |
| `content.blogSidebarShowLatest` | `true` | N recent posts (default 5) |
| `content.blogSidebarShowPopular` | `true` | Top by pageview aggregate |
| `content.blogSidebarLatestCount` | `5` | |
| `content.blogSidebarPopularCount` | `5` | |

**Backend:**

- Public-safe endpoint or extend `GET /api/articles` meta: `popular[]`, `latest[]` (cached rollup from analytics store / `Reporter`)
- Sort option `popular` → `-viewCount` when index field exists (denormalized on publish or nightly rollup)

**Frontend:**

- `BlogSidebar.tsx` widget column
- `BlogRenderer` grid: main + aside when enabled
- Shared layout classes: `.pg-blog-with-sidebar`, `.pg-blog-aside` (mirror `pg-layout-two-column`)

**Admin:** Settings → Content → Blog sidebar (checkboxes + placement select).

---

### 4. Custom roles (84d)

| Exists today | Gap |
|--------------|-----|
| Fixed roles `SUPER_ADMIN`, `ADMIN`, `EDITOR`, `USER`; `PermissionCatalog` (18 permissions); settings matrix for 3 roles; path ACL | SUPER_ADMIN **cannot create new roles**; catalog fixed in PHP |

**Design:**

| Piece | Proposal |
|-------|----------|
| Storage | `data/roles.json` — `{ id, name, permissions[], system: bool }` |
| Service | `RoleRepository` + merge with built-in roles in `AuthorizationManager` |
| Admin API | CRUD `/api/admin/roles` — SUPER_ADMIN + 2FA only |
| UI | Extend `AccessControlSettingsPanel` or new `RolesManager` |
| Migration | On upgrade, copy `permissionsAdmin/Editor/User` from settings into system role records |
| Rules | Cannot delete system roles; cannot strip last SUPER_ADMIN; permission IDs must exist in catalog |

**Security gate:** full route audit, PHPUnit hostile tests, update `ACCESS_CONTROL.md` and ISSUES if new permissions added.

---

### 5. Navigation placement & cascade (84e)

| Exists today | Gap |
|--------------|-----|
| 3-level tree in `navigation.json`; desktop hover dropdown + mobile accordion (`Navbar.tsx`); rich nav fields (It.56); settings `navigationUi.*` | No **top vs side** placement; footer flat only; max depth 3 for all admins |

**Settings contract:**

| Key | Default | Meaning |
|-----|---------|---------|
| `navigation.placement` | `top` | `top` \| `side` \| `both` |
| `navigation.sideBreakpoint` | `lg` | When side nav collapses to drawer |
| `navigation.expandAnimation` | `true` | Cascade expand/collapse motion |
| `navigation.maxDepth` | `3` | SUPER_ADMIN may set `4` (cap enforced server-side) |

**Frontend:**

- `SideNav.tsx` — vertical cascade with expand chevrons + CSS transitions (`prefers-reduced-motion`)
- `PublicSiteLayout` — slot top nav + optional side column
- Reuse `navigationTree.ts` depth validation (raise cap when setting allows)

**SUPER_ADMIN only:** max depth and placement in Settings → Navigation (existing `NavigationManager` + new global nav layout panel).

---

## Definition of Done (by slice)

### 84a — Categories
- [x] Schema + index facet + public filter on blog (`?category=slug`)
- [x] Editor category field (articles; optional pages)
- [x] Registry CRUD at `/api/admin/categories` + bundled seeder
- [x] PHPUnit (`CategoryRepositoryTest`, `ContentIndexCategoryFilterTest`)

### 84b — Blog sidebar
- [x] Settings toggles + placement (`content.blogSidebar*`)
- [x] Public sidebar renders when enabled; responsive grid (`pg-blog-with-sidebar`)
- [x] Latest + popular widgets; tags + categories in sidebar
- [x] Public-safe popular data via analytics rollup (30d)
- [x] Sort option **Most read** (`sort=-popular`) on article list
- [x] PHPUnit `BlogSidebarServiceTest`

### 84c — Landing shortcodes
- [x] ≥4 new shortcodes seeded + expand tests (7 total: cta-banner, stats-row, stat-item, testimonial, pricing-table, pricing-plan, pricing-feature)
- [x] `pgLayout.css` blocks styled
- [x] Demo page `/paginium-cms` in DemoFixtures
- [x] [LANDING_PAGE.md](user/LANDING_PAGE.md) user guide
- [x] Shortcode policy scan passes (bundled seeder)

### 84d — Custom roles
- [x] Role CRUD API + UI
- [x] AuthorizationManager loads dynamic roles
- [x] Migration from settings matrix
- [x] Security regression tests; no permission bypass

### 84e — Nav placement
- [x] Settings placement + side nav component (`navigation.*`, `SideNav.tsx`)
- [x] Cascade animations with reduced-motion fallback
- [x] SUPER_ADMIN depth override (max 4 via settings + backend validation)
- [x] Recursive top dropdown + side/mobile drawer modes

---

## Stabilization interaction

| Slice | During stabilization | Note |
|-------|---------------------|------|
| **84c** | ✅ Allowed as **polish** (extends It.58 shortcodes) | Aligns with S12 |
| **84b** | 🟡 Allowed if no schema break; settings need defaults | Improves S10 blog presentation |
| **84a** | 🟡 New index field — ship with migration + defaults | Small schema extension |
| **84e** | 🟡 Layout refactor — test public pages thoroughly | |
| **84d** | ✅ Allowed — shipped with security regression tests | SUPER_ADMIN + 2FA only; `data/roles.json` |

**Rule:** each merged slice must keep `./scripts/iteration-gate.sh` green.

---

## API sketch (incremental)

| Method | Route | Slice |
|--------|-------|-------|
| `GET` | `/api/articles?filter[category]=slug` | 84a |
| `GET` | `/api/public/blog-sidebar` | 84b (or meta on articles list) |
| `GET/POST/PATCH/DELETE` | `/api/admin/categories` | 84a |
| `GET/POST/PATCH/DELETE` | `/api/admin/roles` | 84d |
| `GET` | `/api/settings/public` | extended appearance + blogSidebar + navigation.placement |

All mutating routes: existing Auth + CSRF + permission gates.

---

## Related documents

| Doc | Role |
|-----|------|
| [ITERATION_44.md](ITERATION_44.md) | Blog pagination |
| [ITERATION_56.md](ITERATION_56.md) | Rich navigation |
| [ITERATION_58.md](ITERATION_58.md) | Layout + shortcodes |
| [ITERATION_81.md](ITERATION_81.md) | Tags, snippets |
| [CONTENT_COMMENTS_NAV.md](CONTENT_COMMENTS_NAV.md) | Nav contract |
| [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) | Gate + parallel polish |
| [developer/ACCESS_CONTROL.md](developer/ACCESS_CONTROL.md) | RBAC baseline |
