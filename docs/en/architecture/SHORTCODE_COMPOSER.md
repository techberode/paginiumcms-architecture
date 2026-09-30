# Shortcode composer roadmap (visual parts library)

Goal: let operators **toggle** and **compose** landing blocks (badge, terminal, subtitle, CTAs, …) from the admin UI—similar to the visual insert modals—**without installing plugins** or pasting forbidden external JavaScript.

Constraints (non‑negotiable):

- Public HTML is built on the **server** (`ShortcodeExpanderService`, optional dedicated renderers).
- Expand templates and classes must pass **`ShortcodeDefinitionPolicy`** (`pg-*`, `prose`, allow‑listed patterns).
- No arbitrary `<script>` or third‑party JS; motion/hover = **CSS** (`pgLayout.css`, `:hover`, `@media`, `prefers-reduced-motion`, optional `pg-reveal`).
- Stored source remains **shortcode tags** in the page/article body (SSOT).

---

## Current state (baseline)

| Layer | What exists |
|-------|-------------|
| **Bundled catalog** | `ShortcodeCatalogSeeder` → `data/shortcodes/definitions/*.json` (e.g. `showcase-hero` with fixed HTML for badge, title, subtitle, terminal, two CTAs). |
| **Expansion** | Mostly `{{attr}}` + `{{content}}` substitution; special cases: `landing-hero`, `feature-gallery`, `visual-frame`, widgets. |
| **Editor insert** | Visual shortcode modal + server preview (`POST /api/admin/shortcodes/render-markup`); layout via `[visual-frame]`; **Modal workspace** + **include field** checkboxes in the page editor (see [Shortcodes and widgets (user)](../user/SHORTCODES_AND_WIDGETS.md)). |
| **Admin CRUD** | Settings → Shortcodes (definitions JSON, policy on save). |

**Phase 1 (done for `showcase-hero`):** `ShowcaseHeroRenderer` + bool attrs `show-badge`, `show-terminal`, `show-cta`, `show-cta2`. Editor **include field** still controls which attrs appear in the Markdown tag; bool attrs control server-side slot visibility. Next: `landing-hero`, `cta-banner`, …

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

## Phase 4 — “Dynamic” effects without external JS

Allowed:

- **CSS‑only** interaction: `.pg-card:hover`, focus rings, `transition`, `@keyframes` in theme/`pgLayout.css`.
- **Reveal on scroll:** existing `pg-reveal` + optional `IntersectionObserver` only if ever added to **first‑party bundled** frontend bundle (not author‑supplied)—prefer pure CSS `@starting-style` / `:has()` where sufficient.
- **Data attributes** on `pg-*` nodes set from shortcode attrs (e.g. `data-accent="primary"`) with fixed CSS selectors—no inline `style=` in author templates (sanitizer strips it).

Not allowed:

- User‑uploaded scripts, `onclick=`, external CDNs for behavior.

Composer UI can expose **effect presets** (enum): `none | hover-lift | hover-glow` → adds only allow‑listed modifier classes on the wrapper.

---

## Phase 5 — Optional React **preview** in admin only

React may render **admin preview** of a composed block (WYSIWYG parity). **Published site** still uses server HTML from PHP for SSOT and CSP.

If a block needs client hydration on the public site, it must be a **first‑party core feature** (reviewed bundle), not per‑shortcode arbitrary code.

---

## Suggested implementation order

1. `showcase-hero` slot toggles + renderer refactor (proves bool + modal + tests).
2. Parts library JSON + seeder + policy tests.
3. `ComposedShortcodeRenderer` + one example composed shortcode.
4. Admin composer UI (MVP: checklist of parts, no drag‑drop).
5. Effect presets (CSS class enums).

---

## Related docs

- [Shortcodes and widgets (user)](../user/SHORTCODES_AND_WIDGETS.md)
- [Extension code policy](../developer/EXTENSION_CODE_POLICY.md)
- Visual insert: Settings → Editor (`visualInsertModalsEnabled`, `visualInsertTypographyEnabled`); **Modal workspace** in the content editor (localStorage)
