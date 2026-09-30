# Page field composer — planned iteration (handoff)

> **Captured:** September 30, 2026  
> **Status:** ⏳ planned — not started in code  
> **Extends:** [It.58f](../ITERATION_58f.md) visual blocks, [SHORTCODE_COMPOSER.md](SHORTCODE_COMPOSER.md) phases 2–4  
> **User goal (SK summary):** Pages must not all look identical. Operators compose **fields (sections)** and drop **registered components** (galleries, heroes, carousels, text) with per-block styling—rounded corners, background (static vs scroll), gradients, carousel placement—**without editing code or creating a new theme per page**.

Reference inspiration (external HTML template demos, e.g. W3Layouts-style landings) is **not** copied as raw HTML/JS. Public output stays server-expanded shortcodes, allow-listed `pg-*` CSS, CSP, and first-party hydration only where already allowed (e.g. `FeatureGallerySection`, `useLandingReveal`).

---

## 1. Problem statement

| Today | Gap |
|-------|-----|
| **Outline** = ordered blocks → same Markdown/shortcodes as Developer mode | Fine-grained **per-block** layout (grid vs carousel, columns, hero strip) mostly **global** (Settings → Gallery) |
| **One gallery catalog** + **tags** + multiple `[feature-gallery]` blocks | “Several galleries on one company page” works via tags; **per-block display mode** does not |
| **Theme Studio** | Global tokens—not per-page section chrome |
| **Shortcode slot toggles** (showcase-hero, cta-banner, landing-hero v3) | No generic **section shell** (background DAM, radius, scroll behaviour, two-column slots) |

---

## 2. Target mental model

```text
Page (landing layout)
 └── Section (FIELD)     id/anchor, min-height, bg (none|tone|DAM image|gradient preset)
      ├── radius: sharp | rounded | pill
      ├── bg-scroll: static | scroll-with-page | fixed (parallax-lite, CSS/core only)
      ├── layout: full | contained | two-column
      └── slots[]          1..N registered components
           ├── Gallery (tag filter, layout override: grid|slider|hero-strip|masonry?)
           ├── Hero variants
           ├── Carousel (items from gallery tag or explicit IDs)
           └── CTA, text, widgets, …
```

**Admin:** form fields + Media picker + enums—not author-supplied CSS/JS.  
**Storage:** still page body as Markdown shortcodes (SSOT flat-file), optionally richer attrs on each tag.  
**Security:** enums → classes; backgrounds from `DamMediaUrl`; behaviour via core bundle hooks (same class as `useLandingReveal`), not page-specific scripts.

---

## 3. Portfolio / “our work” page (primary use case)

**Today (no new code):**

1. Admin → Feature gallery — one photo per form; **Module tag** = project/build (`projekt-a`, …).
2. Page slug e.g. `nase-prace`, builder **Outline**, layout **landing**.
3. Repeat: `[section-head anchor="…"]` + short markdown + `[feature-gallery title="…" tag="projekt-a"]`.
4. Reorder blocks via DnD.

**Planned (this iteration):**

| Slice | Deliverable |
|-------|-------------|
| **58f-i-a** | `feature-gallery` **v2 attrs**: `layout`, `columns`, `modalCaptionStyle` **per block** (override global `gallery.*` settings) |
| **58f-i-b** | Shortcode **`section-band`** + `SectionBandRenderer`: `id`, DAM `bg-image`, overlay, `radius`, `bg-attachment`, optional `two-column` inner |
| **58f-i-c** | Outline forms + `render-markup` preview for new attrs; tests + `pgLayout.css` |
| **58f-i-d** (optional) | Core hook: section background crossfade on scroll (sibling to `useLandingReveal`); page flag or section attr |
| **Later** | **Album** entity (`data/gallery-albums/`) if tags are insufficient—only if customers need many isolated collections |

**Lightbox UX:** align gallery modal with article prose lightbox where product agrees—shared component policy, not duplicate modals.

---

## 4. Explicit non-goals

- Pasting third-party demo HTML/JS into page body.
- Per-page Theme Studio packages for every landing.
- User-authored `<script>`, inline `style=`, or arbitrary class names in Markdown.
- Full Elementor parity in one slice—ship **section shell + gallery v2** first.

---

## 5. Suggested implementation order

1. **`feature-gallery` v2** — highest value for multi-project company pages.  
2. **`section-band`** + outline palette entry.  
3. **Effect / scroll presets** (SHORTCODE_COMPOSER Phase 4) on section wrapper.  
4. Composer UI “parts library” (Phase 2–3) when attrs stabilize.

---

## 6. Related docs and code

| Area | Path |
|------|------|
| Outline palette | `frontend/src/utils/outlinePalette.ts` |
| Gallery block | `FeatureGallerySection`, `FeatureGalleryGrid`, `FeatureGallerySlider` |
| Global gallery settings | `SettingsSchema` → `gallery.layout`, `modalCaptionStyle`, … |
| Gallery user guide | [GALLERY.md](../user/GALLERY.md) |
| Shortcode roadmap | [SHORTCODE_COMPOSER.md](SHORTCODE_COMPOSER.md) |
| Landing | [LANDING_PAGE.md](../user/LANDING_PAGE.md) |

---

## 7. Session notes (September 30, 2026)

- Editorial desk queue confirmed wired via `DeskInboxService` + `GET /api/auth/me/desk` (audit false positive on direct controller call).
- Shipped same week on `main`: desk integration test, CI `check-roadmap-stale.sh`, FAQ/link shortcodes, `checklist`/`stat-duo` widgets, landing-hero v3 toggles.
- **Next coding session:** start with **58f-i-a** (`feature-gallery` per-block layout) unless product reprioritizes section backgrounds.
