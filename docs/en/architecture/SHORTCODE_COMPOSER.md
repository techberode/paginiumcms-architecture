# Shortcode composer roadmap (visual parts library)

Goal: let operators **toggle** and **compose** landing blocks (badge, terminal, subtitle, CTAs, …) from the admin UI—similar to the visual insert modals—**without installing plugins** or pasting forbidden external JavaScript.

**Product direction (October 2026):** avoid “recolored spreadsheet” public sites—see [PUBLIC_EXPERIENCE_ROADMAP.md](PUBLIC_EXPERIENCE_ROADMAP.md) (motion preset library on `[visual-frame]`, reference compositions, agency checklist).

Constraints (non‑negotiable):

- Public HTML is built on the **server** (`ShortcodeExpanderService`, optional dedicated renderers).
- Expand templates and classes must pass **`ShortcodeDefinitionPolicy`** (`pg-*`, `prose`, allow‑listed patterns).
- No arbitrary `<script>`, theme JS in page bodies, or third‑party behavior CDNs. **Static** motion/hover stays **CSS** (`pgLayout.css`, `pg-reveal`, enums → modifier classes). **Interactive** behaviour (carousel, client fetch, complex gallery modes) uses **first-party React islands** only — see [REACT_SHORTCODE_ISLANDS.md](REACT_SHORTCODE_ISLANDS.md).
- Stored source remains **shortcode tags** in the page/article body (SSOT).

---

## Current state (baseline)

| Layer | What exists |
|-------|-------------|
| **Bundled catalog** | `ShortcodeCatalogSeeder` → `data/shortcodes/definitions/*.json` (e.g. `showcase-hero` with fixed HTML for badge, title, subtitle, terminal, two CTAs). |
| **Expansion** | Mostly `{{attr}}` + `{{content}}` substitution; special cases: `landing-hero`, `feature-gallery`, `visual-frame`, widgets. |
| **Editor insert** | Visual shortcode modal + server preview (`POST /api/admin/shortcodes/render-markup`); layout via `[visual-frame]`; **Modal workspace** + **include field** checkboxes in the page editor (see [Shortcodes and widgets (user)](../user/SHORTCODES_AND_WIDGETS.md)). |
| **Admin CRUD** | Settings → Shortcodes (definitions JSON, policy on save). |

**Phase 1 (done):** `ShowcaseHeroRenderer`, `CtaBannerRenderer`, and `LandingHeroRenderer` + bool slot attrs (`show-badge`, `show-terminal`, `show-cta`, `show-cta2`, `show-subtitle`, `show-media`, …). Editor **include field** still controls which attrs appear in the Markdown tag; bool attrs control server-side slot visibility.

---

## Phase 1 — Slot toggles on bundled shortcodes (near term)

**User story:** When inserting `showcase-hero`, checkboxes: *Show badge*, *Show terminal*, *Show secondary CTA*, etc.

**Implementation pattern (recommended):**

1. Add **`bool` attrs** to the definition schema, e.g. `show-terminal`, `show-badge`, `show-cta2` (default `true` for backward compatibility).
2. Move HTML assembly for complex blocks to a **small PHP renderer** (same pattern as `LandingHeroRenderer`), not a monolithic `expand` string—conditionals are unsafe inside policy‑locked templates.
3. Extend **ShortcodeInsertModal** to render `bool` attrs as checkboxes; preview calls `render-markup` with updated attrs.
4. Regression tests: expand with `show-terminal="false"` must not output `.pg-showcase-terminal`.

Apply the same pattern to `landing-hero`, `cta-banner`, etc., one block at a time.

---

## Phase 2 — Internal **parts library** (catalog of slots)

**User story:** Admin maintains a reusable list of **parts** (not full pages): `badge`, `terminal`, `subtitle`, `primary-cta`, `ghost-cta`, `section-eyebrow`, …

**Data model (flat‑file, under `data/shortcodes/`):**

```json
{
  "id": "terminal-strip",
  "label": "Terminal strip",
  "html": "<pre class=\"pg-showcase-terminal\"><code>$ {{text}}</code></pre>",
  "attrs": { "text": { "type": "string" } }
}
```

- Each part template is policy‑validated (`pg-*` classes only).
- Parts are **not** inserted directly into pages; they are referenced by **composed** shortcodes.

**Composer storage:** a composed shortcode definition gains:

```json
{
  "name": "my-hero",
  "version": 1,
  "compose": {
    "wrapper": "pg-showcase-hero",
    "parts": ["badge", "title", "subtitle", "terminal-strip", "cta-pair"]
  },
  "attrs": { "... merged from parts ..." }
}
```

Expansion: `ComposedShortcodeRenderer` loads parts, renders enabled slots, wraps with wrapper class.

---

## Phase 3 — Admin **Shortcode composer** UI

**Location:** Administration → Shortcodes → **Create from parts** (alongside raw JSON editor).

**UX (aligned with existing modals):**

1. Pick wrapper layout (hero / section head / card shell).
2. Enable/disable parts from the library (checkbox list + drag order).
3. Map attrs to fields (reuse ShortcodeInsertModal field generation).
4. **Live preview** (existing `render-markup` endpoint).
5. Save → writes definition JSON + registry entry (policy gate unchanged).

**Custom shortcode** in your sense = **composed definition**, not free‑form HTML (that remains `:::html-safe` + trusted HTML for admins).

---

## Phase 4 — Effects: CSS presets + React islands

**CSS (default):**

- `.pg-card:hover`, focus rings, `transition`, `@keyframes` in `pgLayout.css`.
- `pg-reveal` + `useLandingReveal` on landing shells (existing).
- Shortcode attrs → **enum → modifier classes** (`hover-lift`, `radius-lg`); no inline `style=` in author templates.

**React (catalog only):**

- When CSS or server HTML is not enough, add a block to **`PUBLIC_ISLANDS`** (PHP marker + registered component). Same Outline/insert UX as today.
- Migrate existing islands (gallery, staff) to the generic registry before adding new types.

**Not allowed:** user scripts, `onclick=`, plugin-supplied public components, or JSX stored in page body.

---

## Phase 5 — Public hydration + optional admin parity

**Published site:** SSOT remains Markdown/shortcodes on disk; PHP emits sanitized HTML + island markers; public SPA hydrates **registered** components ([REACT_SHORTCODE_ISLANDS.md](REACT_SHORTCODE_ISLANDS.md) phases A–F).

**Admin:** server `render-markup` preview stays the default; optional later parity using the same registry (Phase F).

---

## Suggested implementation order

1. ~~`showcase-hero` slot toggles~~ (done) · **`feature-gallery` v2 per-block layout** + **`section-band`** — see [PAGE_FIELD_COMPOSER_PLANNED.md](PAGE_FIELD_COMPOSER_PLANNED.md) (**It.58f-i**, September 30, 2026 handoff).
2. Parts library JSON + seeder + policy tests.
3. `ComposedShortcodeRenderer` + one example composed shortcode.
4. Admin composer UI (MVP: checklist of parts, no drag‑drop).
5. Effect presets (CSS class enums).

---

## Related docs

- [Shortcodes and widgets (user)](../user/SHORTCODES_AND_WIDGETS.md)
- [Extension code policy](../developer/EXTENSION_CODE_POLICY.md)
- Visual insert: Settings → Editor (`visualInsertModalsEnabled`, `visualInsertTypographyEnabled`); **Modal workspace** in the content editor (localStorage)
