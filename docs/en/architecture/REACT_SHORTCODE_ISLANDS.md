# React components as shortcodes — recommended architecture

> **Goal:** Rich public UI (carousels, galleries, scroll effects, section chrome) that editors use like shortcodes—**without** arbitrary React or JS in page bodies.  
> **Related:** [SHORTCODE_COMPOSER.md](SHORTCODE_COMPOSER.md) · [PAGE_FIELD_COMPOSER_PLANNED.md](PAGE_FIELD_COMPOSER_PLANNED.md)

---

## Strategic direction (October 2026)

**Decision:** Move **interactive** public blocks and effects to **first-party React islands** in the Vite bundle. Do **not** unblock author/theme `.js`, Sandpack exports into page bodies, or third-party scripts for landing effects (Theme Studio JS tab, W3Layouts-style `custom.js`, and [Site Design](ITERATION_SITE_DESIGN.md) v1 public runtime stay **CSS/tokens only** for global tuning).

| Keep as-is (no rewrite required) | Evolve via islands + attrs |
|----------------------------------|----------------------------|
| Server expand + Markdown SSOT | Carousels, tabs, filters, lightbox, gallery layout modes |
| CSS-only shortcodes (heroes, CTA, prose widgets) | Scroll-linked section backgrounds, crossfades where CSS is insufficient |
| `[widget]` server expand + static `pgLayout.css` | New widget/shortcode types that need client state or same-origin `fetch` |
| `feature-gallery`, `staff-cards` (today’s hardcoded split) | Generic `PUBLIC_ISLANDS` registry (Phase A) |
| Admin insert modals + `render-markup` preview | Optional admin preview parity (Phase F) |

**Editor contract unchanged:** authors still pick **Outline blocks**, **shortcodes**, and **widgets** with form fields—never paste JSX or `<script>`. New React UI is registered in core, reviewed, and mounted only from PHP-emitted island markers.

**It.97** (strict CSP embed facades) remains **deferred**; prefer islands + same-origin APIs over widening `frame-src` for rich blocks.

---

## 1. Principle: two layers, one source of truth

| Layer | Responsibility | Technology |
|-------|----------------|------------|
| **Stored body** | What the editor saves | Markdown + shortcode tags (same as today) |
| **Server expand** | Safe HTML + **hydration contract** | PHP `ShortcodeExpanderService` / dedicated renderers |
| **Public interactivity** | Effects that need React | **First-party islands** in the Vite bundle only |

Authors never paste JSX. They pick a block in Outline or insert a shortcode; attrs are form fields (enums, media, strings).

---

## 2. The “island” pattern (already shipped)

Today:

1. PHP emits a placeholder, e.g. `<section class="pg-feature-gallery" data-tag="…" data-title="…"></section>`.
2. `MarkdownRenderer` runs `splitPublicHtmlIslands()` on sanitized HTML.
3. Matching segments render `<FeatureGallerySection />` instead of raw HTML.

```text
[feature-gallery title="Work" tag="studio"/]
        ↓ expand (PHP)
<section class="pg-feature-gallery" data-tag="studio" data-title="Work"></section>
        ↓ parse + hydrate (React public)
<FeatureGallerySection featureTag="studio" heading="Work" />
```

**Extend this** for every block that needs client behaviour (carousel, tabs, scroll-linked background). Blocks that are CSS-only stay pure server HTML + `pgLayout.css`.

---

## 3. Registry design (target state)

### 3.1 Backend — shortcode definition

Each interactive block has:

- Entry in `ShortcodeCatalogSeeder` (or admin-defined JSON under policy).
- **Attrs schema** (`string`, `enum`, `bool`, `media`) — same as today.
- **`expand` output** must include a stable **island marker**:
  - Prefer: `class="pg-island pg-island--{id}"` plus `data-*` attrs (no inline scripts).
  - Or: empty `<section>` with fixed class list passing policy.

Optional: dedicated PHP renderer (like `ShowcaseHeroRenderer`) when HTML is conditional.

### 3.2 Frontend — island registry

Single module, e.g. `frontend/src/islands/publicIslandRegistry.ts`:

```ts
// frontend/src/islands/publicIslandRegistry.tsx (+ definitions in publicIslandDefinitions.ts)
export type IslandProps = Record<string, string>;

export const PUBLIC_ISLANDS: Record<string, React.ComponentType<{ attrs: IslandProps }>> = {
  'feature-gallery': FeatureGalleryIsland,
  'staff-cards': StaffCardsIsland,
};
```

`splitPublicHtmlIslands` evolves to **generic** parsing:

- Detect `pg-island--{id}` (or parse `data-island="{id}"`).
- Read allowed `data-*` → `attrs` object.
- Map `id` → component from registry; unknown id → leave sanitized HTML (fail-safe).

### 3.3 Admin preview

| Mode | Behaviour |
|------|-----------|
| **Server preview** | `POST /api/admin/shortcodes/render-markup` — HTML only (iframe). |
| **Parity optional** | Same registry in admin preview route for WYSIWYG; **not required** for v1. |

Public site remains the reference for interactive behaviour.

---

## 4. What belongs in React vs CSS-only

| Need | Implementation |
|------|----------------|
| Hover, radius, gradients, typography | Server HTML + `pg-*` classes; attrs → class modifiers (`pg-section--radius-lg`) |
| Scroll reveal | Existing `pg-reveal` + `useLandingReveal` on landing shell |
| Carousel, lightbox, filter tabs, fetch API | **React island** |
| Parallax / section background crossfade | **One** core hook (e.g. `useSectionBackground`) + `data-*` on section bands — not per-page scripts |
| User-defined animation curves | **No** — enum presets only |

---

## 5. Editor UX (Outline + shortcodes)

Reuse existing pipeline:

1. **Outline palette** — one block = one shortcode name.
2. **Form fields** generated from attr schema (bool → checkbox, enum → select, media → picker).
3. **Insert modal** — live server preview for HTML; island blocks may show static placeholder until saved page is viewed publicly (or hydrate preview in admin later).

For **section shell** (`section-band`, planned It.58f-i):

- Fields: `id`, `bg-image`, `radius`, `bg-scroll`, `layout`, nested child shortcodes in `innerMarkdown`.
- Stored as paired shortcode or composed markdown — still one file SSOT.

---

## 6. Security and CSP (non-negotiable)

- No author-supplied `<script>`, `on*=` handlers, or inline `style=` in expand templates.
- Island components may use `fetch` only to **same-origin public APIs** already used (`/api/gallery/public`, etc.).
- New outbound needs → `OutboundUrlGuard` on backend only.
- **Plugins / Theme Studio** do not register public islands dynamically in v1; registration is **core bundle** + code review.
- Sandpack Playground (It.95) stays **admin-only**; export to theme/library goes through Code Policy — not direct injection into page body.

---

## 7. Phased rollout (most feasible order)

| Phase | Deliverable | Editor |
|-------|-------------|--------|
| **A** | ✅ Generalize `splitPublicHtmlIslands` + `PUBLIC_ISLANDS` registry; migrate gallery + staff | No new blocks |
| **B** | ✅ `feature-gallery` v2 attrs (`layout`, `columns`, `modalCaptionStyle`) — props into existing React | Outline fields |
| **C** | ✅ `section-band` shortcode + CSS (server HTML; scroll/fixed via classes) | Outline **Section band** |
| **D** | ✅ **gallery-carousel** island (gallery tag → `FeatureGallerySlider`) | Palette + modal |
| **E** | ✅ `[section-band]` `reveal` + `hover-effect` (lift/glow) → CSS + `pg-reveal` / `useLandingReveal` | Outline enums |
| **F** | Admin React preview uses same registry (parity) | Optional |

Do **not** start with “custom React per customer shortcode” or npm in page body.

---

## 8. Adding a new interactive block (checklist)

1. **Product:** attrs schema + UX mock (Outline fields).
2. **PHP:** renderer outputs `<section class="pg-island pg-island--foo" data-…></section>`.
3. **Policy:** shortcode definition passes `ShortcodeDefinitionPolicy` (`pg-*` only).
4. **FE:** `FooIsland.tsx` + register in `PUBLIC_ISLANDS`.
5. **Parser:** extend island split tests (`publicHtmlIslands.test.ts`).
6. **Outline:** add to `OUTLINE_PALETTE_SHORTCODES` + sample markup in `shortcodeSampleMarkup.ts`.
7. **i18n:** `editor.outline.blocks.*` SK/EN.
8. **Docs:** user shortcode row + marketing draft if public.

---

## 9. Anti-patterns

| Avoid | Why |
|-------|-----|
| Store JSX/JSON React tree in page body | Breaks SSOT, sanitization, git diff |
| Hydrate arbitrary HTML classes from plugins | XSS and bundle bloat |
| One-off `<script>` in expand template | CSP violation |
| Duplicate gallery data in shortcode attrs | Use tags / gallery catalog (It.65) |
| Full W3Layouts HTML paste | Use section-band + islands |

---

## 10. Summary

**Most feasible path:** treat React as **hydration for a fixed catalog of shortcodes**, not as a free page builder runtime. Authors use **fields**; server emits **markers**; public SPA mounts **registered components**. Visual variety comes from **attrs → classes + island props**, aligned with It.58f-i and the shortcode composer roadmap.
