# Product growth proposals — fit map (October 2026)

> **Purpose:** capture **unshipped** ideas with stack fit, security notes, and suggested iteration slices.  
> **Shipped baseline:** `v2.1.0-beta.96` — shortcodes/islands, unified lightbox (**beta.95**), Experience **B1–B2** (visual-frame + section-band motion).  
> **Multi-site + CLI:** dedicated **[ITERATION_98.md](../ITERATION_98.md)** (largest strategic item).

---

## 1. Functional proposals

| # | Feature | Stack fit today | Suggested slice | Notes |
|---|---------|-----------------|-----------------|-------|
| **1** | **A/B landing blocks** | ✅ `landing-hero`, `pricing-table` billing toggle, `[feature-gallery]`; flat-file SSOT | **It.99a** (or Experience **D**) | Store variants in `data/experiments/` JSON; public cookie bucket; conversion counter in flat-file or derived SQLite index — **no** author JS. AuthZ: `settings:experiments`. CSP unchanged. |
| **2** | **Related articles (tag similarity)** | ✅ tags on articles; `BlogSidebarService` (popular/latest) | **It.99b** | End-of-article block: score by shared tags, exclude current id, cap N; optional `[related-articles count="3"]` shortcode; cache tag `content:article`. |
| **3** | **Reading time + auto TOC** | ✅ CommonMark pipeline; save hooks in `ContentController` | **It.99c** | On save: word count → `readingTimeMinutes`; AST walk H2/H3 → stored outline or server-rendered `<nav class="pg-toc">`. Respect `prefers-reduced-motion` for TOC highlight. |
| **4** | **Auto cross-post on publish** | ✅ It.80 webhooks, `WebhookDeliveryStore`, scheduler | **It.99d** | “Recipes” = preset webhook templates (Mastodon/LinkedIn/X-shaped payloads); **no** new outbound surface — still `OutboundUrlGuard` + operator-configured URLs/secrets in settings (`type=password`). Not hard-coded vendor tokens in core. |
| **5** | **Multi-site / workspace** | 🟡 single `data/` tree today; CLI import/export exists | **[ITERATION_98.md](../ITERATION_98.md)** | Workspace dirs + CLI create/update/publish; **not** multi-instance hub in v1. |

---

## 2. Visual effects — build on existing motion discipline

Current assets (keep this pattern):

| Mechanism | Location |
|-----------|----------|
| Scroll reveal | `.pg-reveal` + `useLandingReveal` (`PageRenderer`) |
| Section band reveal | `SectionBandRenderer` — `reveal` enum → `.pg-reveal` + `.pg-reveal--*` (**B2 shipped**) |
| Insert-modal motion | `[visual-frame]` — `motion=fade-up|stagger` (Experience **B1**) |
| Hover on bands | `hover-effect` lift/glow/tilt-3d/border-sweep on `[section-band]` (**B2**) |
| Parallax-lite | `bg-attachment=fixed` on section-band |
| Reduced motion | CSS + IO fallback everywhere above |

### 2.1 Section-band `reveal` enum — ✅ **B2 shipped** (`beta.96`)

| `reveal` value | CSS class | Behaviour |
|----------------|-----------|-----------|
| `scroll` (default) | `.pg-reveal` | fade-up |
| `scroll-stagger` | `.pg-reveal--scroll-stagger` | stagger direct children |
| `slide-left` / `slide-right` | `.pg-reveal--slide-left` / `--slide-right` | translateX |
| `scale-in` | `.pg-reveal--scale-in` | scale 0.92→1 |
| `none` | — | no animation |

**Files:** `SectionBandRenderer.php`, `pgLayout.css`, seeder **section-band v3**, `SectionBandRendererTest.php`.

### 2.2 Hover presets — ✅ **B2 shipped**

`HOVER_EFFECTS`: `tilt-3d`, `border-sweep` + reduced-motion disables transform/animation.

### 2.3 Reading progress bar (**B3**)

| Piece | Approach |
|-------|----------|
| Hook | `useScrollProgress()` in public layout shell (articles + long pages only) |
| UI | `.pg-scroll-progress` fixed top; width = `%`; `z-index` below modals |
| a11y | `aria-hidden="true"` decorative; disable when reduced motion |

Optional setting: `layout.showReadingProgress`.

### 2.4 View Transitions API (**B3**)

SPA navigation wrapper in React Router — `document.startViewTransition` when supported; CSS `::view-transition-old/new`; fallback immediate navigate. **One** central helper, not per-page. Test Safari 18+ / Chromium.

### 2.5 Glass utility (**B3**)

`.pg-glass` in `pgLayout.css` using `color-mix` + `backdrop-filter`; use on section-band overlays or cards — keep allow-list in `ShortcodeDefinitionPolicy` if referenced from templates.

---

## 3. Priority recommendation (solo maintainer)

| Order | Item | Why |
|------:|------|-----|
| 1 | **Experience C** — reference landing seeds | Proves composer without new infra — [PUBLIC_EXPERIENCE_ROADMAP.md](PUBLIC_EXPERIENCE_ROADMAP.md) |
| 2 | ~~**B2** — section-band reveal + hover~~ | ✅ `beta.96` |
| 3 | **99b** — related articles | Reuses tags + sidebar patterns |
| 4 | **99c** — reading time + TOC | Editor-visible value |
| 5 | **98** — workspace + CLI | When multi-client hosting is real |
| 6 | **99a** / **99d** | Experiments / cross-post when ops need them |

---

## 4. Explicit non-goals (all proposals)

- CDN animation libraries, Lottie, GSAP in page body.
- Author-defined `<script>` or custom CSS in Markdown (Theme Studio only).
- SQL SSOT or page-builder JSON parallel to Markdown body.
- Scraping social APIs with stored user passwords in flat-file plaintext.

---

## 5. Documentation touchpoints

| Audience | Doc |
|----------|-----|
| Operators | [SHORTCODE_COOKBOOK.md](../user/SHORTCODE_COOKBOOK.md) (update when B2 ships) |
| Maintainers | [CONTINUATION.md](../CONTINUATION.md), [CHANGELOG.md](../../CHANGELOG.md) |
| CLI (98+) | `docs/en/developer/CLI_CONTENT.md` (to create with 98b) |
