# Iteration 88 — Theme Studio (Monaco authoring, policy, preview, thumbnail)

> **Status:** ⏳ planned — **new product slice** (recorded 2026-09-09 from maintainer request)  
> **Priority:** 🟡 **P1** for HTML/CSS studio + preview; 🔵 **P2** JS tab (depends on [It.87 Track C](ITERATION_87.md) `87k`–`87m`)  
> **Wave:** Themes & layout (extends It.83 runtime, It.67 policy, It.16 Monaco, It.58 preview)  
> **Depends on:** It.83 activate/PublicShell, It.67 `UntrustedPolicyScanner` + `CodePolicyEngine`, existing `MonacoCodeEditor`  
> **Does not replace:** page shortcodes, It.58f outline/DnD (page blocks), ZIP import (that path stays)

## Goal

Give the administrator a **CMS-native theme/template studio**:

1. **Monaco** tabs for HTML layout, CSS, optional JS, and `theme.json`.
2. **Policy validation** before save (same fail-closed engine as ZIP import — no weaker Monaco path).
3. **Sandboxed live preview** using the same sanitizer/expand pipeline as the public site.
4. **Thumbnail** (`preview.png`) captured or uploaded and stored in the theme package.
5. **Normalize pipeline** that turns **pasted raw** HTML/CSS/(JS) from a free template into a **CMS-usable package** — by **stripping and rewriting**, not by magically “fixing” hostile code.

This is **not** a pixel page-builder and **not** “paste Themeforest HTML and run it as-is.”

---

## What already exists (reuse — do not fork)

| Piece | Location | Role in 88 |
|-------|----------|------------|
| Monaco (self-hosted workers) | `MonacoCodeEditor.tsx`, Code Editor, ShortcodesManager | Editor UI + markers |
| Syntax stubs | `SyntaxChecker` | PHP/JSON/YAML real; **HTML/CSS/JS currently no-op** — 88 must replace stubs with policy |
| Untrusted scan | `UntrustedPolicyScanner`, `CodePolicyEngine` | Save/preview gate |
| Theme ZIP import | `ThemeImporter`, `ThemeRegistry` | Install path; studio writes the same tree |
| Theme runtime | It.83 `appearance.activeThemeId`, PublicShell | Preview + activate |
| Layout preview | `LayoutPreviewFrame`, `SitePreviewModal` | Pattern for iframe preview |
| Public HTML sanitizer | `sanitizePublicHtml` | Preview = public rules |
| Slot contract | `header` / `main` / `footer` (+ optional sidebar) | Target of HTML normalize |

Code Policy already forbids a weaker write path through Monaco ([EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md) §1.6).

---

## Studio contract

```text
Paste or type → validate (policy + syntax) → normalize (optional) → preview (sandbox)
    → save package (HTML/CSS/JS/manifest + thumbnail) → activate via existing theme API
```

**SSOT:** theme files on disk (`backend/resources/views/themes/{id}/` + FE `frontend/src/themes/{id}/` as today), registry `data/themes.json`. Studio never writes Core, `data/` content, or `public/` except the dedicated theme-asset route from 87m.

### Tabs

| Tab | File | Policy profile |
|-----|------|----------------|
| Layout HTML | `templates/*.html` or slot partials | HTML: no `<script>`, no event handlers, no `javascript:` URLs; slots `{{header}}` / `{{main}}` / `{{footer}}` |
| CSS | `assets/*.css` | no `expression(`, no `url(javascript:)`, no remote `@import` unless allow-listed |
| JS | `assets/*.js` | **only if 87k–m shipped**; else tab disabled with explanation |
| Manifest | `theme.json` | existing `ThemeManifestValidator` |
| Thumbnail | `preview.png` | image policy (safe raster types, size cap) |

### Preview

- Iframe with **CSP sandbox** (`sandbox` + no `allow-same-origin` for untrusted HTML, or dedicated preview origin).
- Same sanitizer as public render.
- Sample content: current landing shortcode seed or selected page slug.
- Invalid policy → preview **blocked** (fail-closed), markers in Monaco.

### Thumbnail

- **Primary:** upload `preview.png` (already a theme-package convention).
- **Optional 88e:** capture from preview iframe (`html2canvas` or server-side render) → save under theme `preview.png` via existing media/theme write with MIME/path checks. Capture is **best-effort**; never a substitute for policy.

---

## Normalize pipeline (“pregenerator”)

Honest scope: **ingest → report → strip/rewrite → package**.

| Input | Action |
|-------|--------|
| Full HTML document | Extract `body`; map `<header>`, `<main>`, `<footer>`, `<aside>` to slots; drop `<script>`, `<iframe>` (unless later audited), `on*` attributes, `javascript:` / `data:text/html` |
| Linked CSS | Concatenate local files; drop `@import url(http…)`; rewrite `url(...)` to theme `assets/` or `/storage/` allow-list |
| Linked JS | **Do not execute.** If 87k exists: keep only files that pass scanner + size/token bans, list in `assets.scripts[]` with SRI. Else: **drop** and list in the report |
| CDN URLs | Reject / strip (same as theme manifest today) |
| PHP / `{{` engines / Blade | Reject entire import |

**Output:** `{ html, css, js?, manifest, dropped: string[], markers: MonacoMarker[] }`.

The operator reviews the report in Monaco. **Save is allowed only when policy is clean** (dropped JS is OK if the JS tab is empty).

This cannot turn an arbitrary commercial HTML template into a pixel-perfect Paginium site automatically. It produces a **safe starting package**.

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **88a** | Theme Studio shell (admin) | 🟡 P1 | ⏳ | Extensions → Themes → **Edit / New**; Monaco tabs; AuthZ `themes:edit` |
| **88b** | Policy + syntax API | 🟡 P1 | ⏳ | `POST /api/admin/themes/validate` — HTML/CSS/JS/JSON; Monaco markers; no persist |
| **88c** | Normalize from paste/ZIP-of-files | 🟡 P1 | ⏳ | `POST /api/admin/themes/normalize`; report + rewritten buffers |
| **88d** | Sandboxed preview | 🟡 P1 | ⏳ | iframe; same sanitizer as public; block on policy fail |
| **88e** | Thumbnail save | 🟡 P2 | ⏳ | upload required; optional capture from preview |
| **88f** | Slot/block mapping UI | 🟡 P2 | ⏳ | highlight header/main/footer; optional insert of bundled shortcodes into `main` |
| **88g** | Persist + activate | 🟡 P1 | ⏳ | write theme tree + registry; reuse activate API; Developer Mode if writing frontend theme CSS |

**Recommended order:**

```text
88b → 88a → 88d → 88c → 88g → 88e → 88f
```

JS tab ships **after or with** `87k`–`87m`. Until then, normalize **drops** scripts and the JS tab is read-only “removed by policy”.

---

## Explicit non-goals

- Pixel canvas / Gutenberg / Webflow clone (that remains It.58f for **pages**, not themes).
- Running pasted JS in the admin origin.
- Auto-enabling `unsafe-inline` or CDN scripts.
- Writing into `Core/` or content `data/` from the studio.
- Guaranteeing visual fidelity of third-party HTML templates.

---

## Security baseline (mandatory)

- AuthN + `PermissionMiddleware('themes:edit')` (or stricter SUPER_ADMIN) on every mutating `/api/admin/themes/*`.
- CSRF on POST/PUT.
- Validate **before** write; preview uses the same validators.
- Path: only under the theme id directory; `realpath` + prefix check; no `..`.
- Log sanitized paths/ids (`LogSanitizer`); never dump full pasted HTML into logs.
- Theme JS: 87k allow-list + SRI + CSP hashes only.

---

## Tests

- Hostile HTML (`<script>`, `onerror=`, `javascript:`) → validate 422, preview empty, no files written.
- Normalize drops scripts and lists them in `dropped`.
- CSS `url(javascript:…)` rejected.
- Happy path: slot HTML + CSS → save → activate → public shell uses package.
- PHPStan L8, PHPUnit, Vitest for studio shell.

---

## Related

- [ITERATION_83.md](ITERATION_83.md) — theme runtime  
- [ITERATION_87.md](ITERATION_87.md) — Track C JS allow-list  
- [ITERATION_67.md](ITERATION_67.md) — untrusted ZIP/policy  
- [ITERATION_16.md](ITERATION_16.md) — Monaco Code Editor  
- [architecture/THEMES.md](architecture/THEMES.md)  
- [developer/EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md)
