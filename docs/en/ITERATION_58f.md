# Iteration 58f — Visual page blocks (amateur outline + developer live preview)

> **Status:** 🟡 partial — **58f-a–g** shipped; **58f-h** visual block canvas planned (audit 2026-09-17). Remainder of [It.58](ITERATION_58.md) is **58g** compile/cache with [It.48](ITERATION_48.md).  
> **Priority:** 🟡 **P1 for the publishing product** (ahead of It.92/93; parallel with It.89)  
> **Wave:** Layout builder (58b–e shipped; **58g compile stays with [It.48](ITERATION_48.md)**)  
> **Depends on:** It.58d expander + catalog · It.58e `pg-*` · It.90 insert wizards · It.88 sandbox preview pattern · It.65 gallery · It.79 DAM video · It.67/It.91 sanitizers  
> **Does not replace:** Theme Studio (It.88), plugins (It.89), admin chrome ([It.93](ITERATION_93.md))

## Why this iteration (September 2026)

The CMS now has Hybrid Engine, Theme Studio, editor wizards, DAM video, and landing shortcodes — and still **no way for a non-coder to assemble a portfolio/landing from visual blocks**. Developers have Monaco + policy, but the page editor’s “shortcodes” mode is a raw insert list, not a form.

**58f is the missing product surface.** It is not a new number. Do not open It.94 for the same work.

| Audience | 58f delivers |
|----------|----------------|
| Amateur publisher | Paleta blokov s náhľadom; vyplní text / obrázok / video; presunie sekcie. Žiadny JSON, žiadny TemplateMo ZIP. |
| Professional developer | Ten istý dokument ako Markdown/shortcody + **živý náhľad** pri úprave; nové bloky kódi v existujúcom Shortcodes Manageri. |

Restaurant: amater si objedná z jedálnička. Developer dopíše recept (shortcode `expand`). Kuchyňa ukladá jeden lístok — telo stránky v Markdown.

---

## Goal

1. **Outline builder** as a first-class `layout.builderMode=outline` view of a **page**.
2. **No second SSOT.** Canonical store stays the page Markdown body (shortcodes). Outline parses top-level blocks, edits attrs, rewrites Markdown on save. Switching templates ↔ shortcodes ↔ outline ↔ developer must not wipe body.
3. **Forms, not code**, for amateurs: each bundled shortcode `attrs` schema becomes a field panel (string, enum, media picker, DAM video). Nested inner shortcodes stay an “inner content” textarea or recursive outline later (v1: inner Markdown).
4. **Live preview** beside the outline (same sanitizer/expander as public / Theme Studio preview). Developer mode shows **source + preview**.
5. **Hero media:** image from DAM (reuse landing OG/featured path + `landing-hero` attrs). Optional **muted looping background video** from DAM (`playsinline`, no `controls`, honor `prefers-reduced-motion` → poster only). Not YouTube-as-background. Not Theme Studio `<video>` paste.

**Out of 58f:** GrapesJS / pixel canvas / arbitrary Tailwind; It.58g HTML compile cache; Cloud sidecar; editing `paginium-core` theme files.

---

## What already exists (reuse)

| Piece | Use in 58f |
|-------|------------|
| `layout.builderMode` `outline` | Settings → Layout; page editor palette + forms |
| `ShortcodeInsertPanel` | Replace/augment with palette + forms when mode is outline |
| `ShortcodeCatalogSeeder` | Bundled blocks: `landing-hero`, `showcase-hero`, `feature-grid`/`feature-card`, `cta-banner`, `testimonial`, `stats-row`, `section-head`, `pricing-*` |
| `ShortcodeExpanderService` | Public render unchanged |
| It.90 wizards | Same dialog pattern (media/video pickers) |
| Preview APIs | Theme Studio / snippet `AdminBodyPreviewPanel` / shortcode preview |
| It.65 `featureGallery` | One block that **reads the existing gallery API**, no second store |
| It.79 `:::video` | Content player block; hero background is a **separate** shortcode/attrs |
| `ContentEditorShell` | Host outline vs markdown vs developer split. Optional fullscreen **Workspace** (Elementor-style canvas; not a 58f slice) hides admin chrome so the outline + live preview fill the remaining viewport and scroll independently. |

---

## SSOT and round-trip

```text
Page Markdown
  [landing-hero title="…" /]
  [feature-grid columns="3"] … [/feature-grid]
  :::video …          ← remains a content block, listed in outline
        ↓ parse (top-level only)
Outline rows { name, attrs, innerMarkdown, sourceSpan }
        ↓ edit / DnD
Rewrite Markdown (stable formatting, no pretty-print fight)
        ↓ save
Existing content API (OCC, locks, sanitizer)
```

- Unknown / broken shortcodes: one “raw” row, not dropped.
- Prose between blocks: `markdown` rows (editable as WYSIWYG/Markdown, not forced into a hero).
- Developer tab: full Monaco/textarea of the same body; outline regenerates on blur if parse succeeds; if parse fails, keep source, show markers (do not clobber).

---

## Amateur UX (outline)

Settings → Layout → builder **Outline** (recommended default **for new pages** after 58f; existing sites keep current `builderMode` until changed).

Page editor:

- Left/top: **palette** (thumbnail + title: Hero, Cards, Quote, CTA, Gallery, Video, Stats, Pricing, Custom shortcode).
- Center: **stack of cards** (DnD). Click card → fields from `attrs` + media buttons. No JSON.
- Right: **live preview** (sandbox iframe, scripts off unless Core preview rules).

Empty portfolio starter: insert `landing-hero` + `feature-grid` + `cta-banner` in one “Use portfolio starter” action (writes shortcodes, still Markdown).

---

## Developer UX

- `builderMode=developer` (ADMIN+ if `developerRequiresAdmin`): Markdown/Monaco **and** the same preview pane.
- New block types: **Shortcodes** admin (JSON `expand` + `attrs`) — already shipped. 58f only consumes `attrs` for forms.
- Theme look: Theme Studio (It.88), not the page outline.
- Live visual while coding the **page** is the preview pane, not a second Theme Studio.

---

## Hero video (in 58f, small)

New bundled shortcode e.g. `hero-video` **or** optional attrs on `landing-hero`:

| Attr | Rule |
|------|------|
| `poster` | DAM image path only (`/storage/…` / `/api/media/file/`) |
| `src` | DAM `video/mp4` or `webm` only |
| `srcMobile` | optional smaller DAM file |

Render: wrapper `pg-hero-video`; `<video muted loop playsinline preload="metadata" poster>` + `object-fit: cover`; overlay gradient for title (CSS in `pgLayout.css`). `prefers-reduced-motion: reduce` → hide video, show poster. Never `controls`. Never autoplay with sound.

This is **not** isolated-origin widgets. It is Core HTML like It.79, different presentation. Iframe widgets: [ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md).

---

## Security

- Same `ShortcodeDefinitionPolicy` / sanitizer as 58d.
- Media URLs: existing DAM allow-list only.
- Preview iframe: no `allow-scripts` for untrusted expand HTML (same as Theme Studio preview).
- Outline cannot inject `on*` or `<script>` via form fields (escape attrs on expand — already expander job).

---

## Slices

| ID | Work | Status |
|----|------|--------|
| **58f-a** | Parse/serialize top-level blocks ↔ Markdown + tests | ✅ `frontend/src/utils/pageOutline.ts` |
| **58f-b** | Outline UI: palette, attr forms from schema, inner content | ✅ `PageOutlineEditor` |
| **58f-c** | DnD reorder + starter pack (portfolio/landing) | ✅ grip + arrows; portfolio/landing starters write shortcodes |
| **58f-d** | Live preview pane in page editor (amateur + developer) | ✅ `AdminBodyPreviewPanel` sandbox iframe + `POST /api/admin/content/render-preview` |
| **58f-e** | Media fields + `hero-video` / hero image on `landing-hero` | ✅ DAM picker on outline; `image`/`src`/`srcmobile`/`poster`; muted looping video; reduced-motion hides video |
| **58f-f** | `feature-gallery` outline block → It.65 API | ✅ |
| **58f-g** | i18n SK/EN, builderMode help, gate tests | ✅ |
| **58f-h** | Visual block canvas (DnD stack), not template-only card | ⏳ planned — see below |

Order: `a → b → d → c → e → f → g`. **58f-a–g shipped.** **58f-h** closes the amateur gap when `LayoutBuilderCard` is template-pick only. Next core queue: [It.89](ITERATION_89.md); novice UX bundle: [It.94](ITERATION_94.md).

**58g** (compile/cache): not in this spec.

### Slice 58f-h — Visual block canvas

**Problem (audit 2026-09-17):** `LayoutBuilderCard` (~69 lines) selects a **page template**, not a block-by-block layout. `PageOutlineEditor` exists for outline mode, but product expectation is a **visual canvas**: drag hero, gallery, CTA from a palette, reorder with **@dnd-kit** (already used elsewhere in the admin stack).

**Goal:**

- Wire outline / palette UX as the primary amateur surface when `layout.builderMode=outline` (or dedicated “Visual builder” entry on page edit).
- Block stack with DnD reorder; click block → attr forms (reuse 58f-b patterns).
- Live preview pane unchanged (`AdminBodyPreviewPanel` / render-preview API).
- SSOT unchanged: Markdown shortcodes on save via `pageOutline.ts`.

**Reuse:** `LandingHeroRenderer`, `FeatureGalleryRenderer`, bundled shortcodes from catalog; do **not** invent It.94 for this work ([ITERATION_58.md](ITERATION_58.md)).

**DoD:** Non-developer can assemble landing from blocks without Monaco; iteration gate green; documented in CHANGELOG when shipped.

---

## Definition of Done

- Amateur can build a landing from palette + DAM without opening Monaco or writing `[shortcode`.
- Same page opened in developer mode shows equivalent Markdown; save round-trip does not drop blocks.
- Public HTML still goes through expander + sanitizer; TTFB unchanged vs shortcode-only pages.
- Hero video (if enabled) is DAM-only, muted, reduced-motion safe.
- `./scripts/iteration-gate.sh` green.

---

## Queue note

**58f is shipped.** Next planned iteration is [It.89](ITERATION_89.md) (plugin capabilities). **58g** compile/cache stays with [It.48](ITERATION_48.md).
