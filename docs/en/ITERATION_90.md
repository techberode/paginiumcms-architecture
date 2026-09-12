# Iteration 90 — Editor Workbench (toolbar builder + publishing wizards)

> **Status:** ⏳ planned  
> **Priority:** 🟡 P1 — publishing UX for non-technical authors  
> **Wave:** Editor & content authoring (post It.79)  
> **Depends on:** [It.55](ITERATION_55.md) Tiptap · [It.79](ITERATION_79.md) DAM video · [It.60](ITERATION_60.md) editor component registry · [It.78](ITERATION_78.md) upload policy  
> **Supersedes (UX):** rigid editor profiles as primary gate — profiles become optional **presets** only

## Goal

Make publishing **comfortable for inexperienced users** without hunting external Markdown docs for tables, diagrams, or charts. Replace profile-centric capability gating with a **Settings-driven toolbar builder** for both **Markdown** and **Tiptap (WYSIWYG)** surfaces, plus guided insert wizards that store **safe canonical blocks** (shortcodes / validated nodes), not arbitrary HTML or JS.

**Invariant:** flat-file Markdown (or Tiptap JSON round-tripped to Markdown/HTML) remains SSOT; backend CommonMark + shortcode preprocessors remain authoritative for public render.

---

## Problem with current model

| Today | Why it fails |
|-------|----------------|
| Editor profiles (Company / Blog / Minimal / Developer) | Authors must guess a profile; capabilities hidden behind opaque names |
| Textarea + icon toolbar | Tables, Mermaid, charts require syntax knowledge |
| Separate capability paths for MD vs WYSIWYG | Confusing; video already needed Settings extensions (It.79) as a patch |

**Direction:** site admin configures **which tools appear on each toolbar**; former profiles are **one-click presets** (“Load Blog toolbar”) — not a runtime gate in the article editor.

---

## Target UX — Settings → Editor

```
Editor settings
├── Default mode (Markdown | WYSIWYG)
├── Markdown toolbar   [ordered checklist of tools]
├── WYSIWYG toolbar    [ordered checklist of tools]
├── Optional plugin tools (manifest-registered, site opt-in)
└── [Apply preset ▼]   ← former profiles (Blog, Minimal, …)
```

Article editor **does not** ask for a profile. Optional front matter `editorProfile` becomes legacy / preset hint only (backward compatible).

---

## Core tool catalog (built-in)

Each tool maps to a **capability id**, toolbar button, optional **insert wizard**, and BE validation + render path.

| Tool id | User action | Stored form | Public render |
|---------|-------------|-------------|---------------|
| `heading` | pick level H1–H6 | `# …` / Tiptap heading | CommonMark / Tiptap HTML |
| `bold`, `italic`, `link`, … | toggle / dialog | MD or Tiptap marks | existing |
| `image` | Media Library picker | `![alt](url)` / image node | existing |
| `video` | Media Library (video mode) | `:::video` / video node | It.79 |
| `table` | rows/columns wizard | GFM table | CommonMark GFM |
| `callout` | type picker (Note, Tip, Warning) | `:::note` … | shortcode renderer |
| `mermaid` | diagram editor + preview | `:::mermaid` + body | BE → **SVG at save or render** (no client `eval`) |
| `chart` | data + chart type form | `:::chart` + JSON schema | trusted template → SVG/PNG or sandboxed chart lib |
| `codeBlock` | language picker | fenced code | CommonMark |

User never hand-writes `| A | B |` or ` ```mermaid ` — wizards emit validated blocks.

---

## Tiptap parity (required)

The same **capability ids** and **Settings toolbar lists** drive **both** surfaces:

| Layer | Markdown surface | WYSIWYG (Tiptap) surface |
|-------|------------------|---------------------------|
| Toolbar source | `editor.markdownToolbar[]` | `editor.wysiwygToolbar[]` |
| Insert wizards | shared React dialogs | same dialogs → Tiptap commands |
| Capabilities | `EditorCapabilities` merge | Tiptap extension pack from enabled ids |
| Plugin tools | shortcode insert | custom Tiptap node + same shortcode on export |

It.79 `EditorExtensionsPanel` (`markdownExtraCapabilities`, `wysiwygExtraCapabilities`) **merges into** this model in 90a, then migrates to unified toolbar keys.

---

## Plugin-extensible editor tools

Extend [It.60](ITERATION_60.md) `EditorComponentRegistry` pattern to **Editor Tool** manifest entries (aligned with [It.89](ITERATION_89.md) capability broker for server-side plugins):

```json
{
  "editorTools": [
    {
      "id": "pricing-table",
      "label": "Pricing table",
      "surfaces": ["markdown", "wysiwyg"],
      "markdownDirective": "pricing-table",
      "jsonSchema": { "type": "object", "properties": { … } },
      "capability": "editor:tool:pricing-table"
    }
  ]
}
```

Rules:

- No arbitrary HTML/JS in stored content — only registered directive + validated JSON body.
- BE **must** register a renderer (core or plugin hook); save fails if renderer missing.
- Site admin enables tools in Settings (same panel as built-in tools).
- Static scan + capability manifest before plugin enable ([It.89](ITERATION_89.md)).

---

## Backend contract

| Component | Change |
|-----------|--------|
| `SettingsSchema` | `editor.markdownToolbar`, `editor.wysiwygToolbar` (ordered string[]); deprecate profile-as-gate in UI |
| `EditorProfileService` | presets only; `resolveEffectiveCapabilities()` ← toolbar + optional extras |
| `EditorContentValidator` | validate each enabled block type (mermaid syntax subset, chart JSON schema) |
| `MarkdownContentParser` | expand `:::mermaid`, `:::chart`, `:::note`, … |
| New renderers | `MermaidShortcode`, `ChartShortcode`, `CalloutShortcode` |
| `TiptapHtmlRenderer` | nodes for table, callout, mermaid/chart placeholders |
| `ContentSecuritySanitizer` | no new raw HTML; SVG from trusted BE pipeline only |

Settings example:

```yaml
editor:
  defaultMode: markdown
  markdownToolbar:
    - heading
    - bold
    - link
    - image
    - video
    - table
    - callout
    - mermaid
  wysiwygToolbar:
    - heading
    - bold
    - link
    - image
    - video
    - table
    - callout
  markdownSurface: native   # native | codemirror6 | mdxeditor (90d)
```

---

## Frontend contract

| Component | Change |
|-----------|--------|
| `EditorToolbarBuilder` | Settings UI — reorderable checklist for MD + WYSIWYG |
| `EditorInsertWizard` | shared modals (table, callout, mermaid, chart) |
| `MarkdownContentEditor` | toolbar from settings; optional CodeMirror 6 surface (90d) |
| `WysiwygEditor` | dynamic Tiptap extension pack from `wysiwygToolbar` |
| `editorProfiles.ts` | presets map → toolbar arrays; remove profile picker from article UI |
| i18n | `editor.toolbar.*`, wizard copy SK/EN |

---

## Phased delivery

| Phase | Scope | Outcome |
|-------|--------|---------|
| **90a** | Toolbar builder in Settings; presets; deprecate profile picker in article editor | Biggest UX win, low risk |
| **90b** | Wizards: table, callout | No external MD docs for common blocks |
| **90c** | `:::mermaid` + BE SVG render + sanitizer tests | Diagrams without client-side eval |
| **90d** | Rich MD surface opt-in (CodeMirror 6); syntax highlight | Better authoring, same SSOT |
| **90e** | `:::chart` + plugin Editor Tool SDK | Graphs + third-party tools under manifest |

---

## Security (strict)

1. **No raw HTML** from wizards; no MDX/JSX; no user `<script>`.
2. **Mermaid/chart** — parse on server; reject unknown diagram types; output sanitized SVG or static image.
3. **Plugin tools** — manifest + capability + BE renderer required; CodePolicyEngine on plugin PHP.
4. **URLs** — media tools unchanged (library-only for video/image).
5. **CSRF/auth** — unchanged; settings mutation `settings:manage`.
6. **Regression tests** — XSS fixtures for each new block type; round-trip MD ↔ Tiptap where applicable.

---

## Out of scope

- YouTube/Vimeo iframe embeds.
- Full MDX / React-in-content.
- Arbitrary user JavaScript in articles.
- Removing `editorProfile` from front matter in one release (deprecate gradually).
- Marketplace/store for editor tools (future; 90e is manifest-only).

---

## Tests

- Settings save/load toolbar order; preset apply fills both toolbars.
- Disabled tool → button hidden in MD and Tiptap; insert API rejected if forced.
- Table wizard → valid GFM; callout → valid directive; validator rejects malformed bodies.
- Mermaid: valid diagram → SVG in public HTML; `javascript:` / `<script>` in body rejected.
- Plugin tool: manifest registration → appears in Settings when plugin enabled; save without BE renderer → 422.
- Vitest: toolbar builder, wizards; PHPUnit: shortcode renderers, validator.

---

## Definition of Done (full iteration)

- [ ] Toolbar builder replaces profile picker as primary authoring UX (MD + Tiptap).
- [ ] Presets available; existing content opens without migration.
- [ ] Table + callout wizards shipped.
- [ ] Mermaid shortcode with BE SVG render.
- [ ] Chart shortcode MVP + extension point for plugin tools.
- [ ] Optional CodeMirror 6 markdown surface.
- [ ] PHPUnit + Vitest + iteration gate green; SK/EN docs; CHANGELOG.

## Related

[It.79 DAM video](ITERATION_79.md) · [It.55 Tiptap](ITERATION_55.md) · [It.60 Editor registry](ITERATION_60.md) · [It.89 Plugin capabilities](ITERATION_89.md) · [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md)
