# Iteration 88 — Theme Studio (Monaco authoring, policy, preview, thumbnail)

> **Status:** ✅ shipped in **`v2.1.0-beta.69`** (2026-09-10) — 88a–88g complete  
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
| **88a** | Theme Studio shell (admin) | 🟡 P1 | ✅ | Themes → **Edit / New**; Monaco tabs; AuthZ `themes:read` (mutations later `themes:edit`) |
| **88b** | Policy + syntax API | 🟡 P1 | ✅ | `POST /api/admin/themes/validate` — HTML/CSS/JS/JSON; Monaco markers; no persist |
| **88c** | Normalize from paste/ZIP-of-files | 🟡 P1 | ✅ | `POST /api/admin/themes/normalize`; report + rewritten buffers |
| **88d** | Sandboxed preview | 🟡 P1 | ✅ | iframe; same sanitizer as public; block on policy fail |
| **88e** | Thumbnail save | 🟡 P2 | ✅ | PNG upload to `preview.png`; capture from sandbox blocked |
| **88f** | Slot/block mapping UI | 🟡 P2 | ✅ | header/main/footer/sidebar panel; insert bundled shortcode into main |
| **88g** | Persist + activate | 🟡 P1 | ✅ | write theme tree + registry; reuse activate API; Developer Mode if writing frontend theme CSS |

**Recommended order:**

```text
88b → 88a → 88d → 88c → 88g → 88e → 88f
```

JS tab ships **after or with** `87k`–`87m` (infra already shipped). **88b** opens the JS tab for local edit + validate; persist is 88g.

---

## 88a — shipped (2026-09-10)

**Admin UI:** Build → Themes → **Edit** (installed packages present on disk) or **New** (local draft buffers). Route `/themes/:themeId/edit` and `/themes/new`. Monaco tabs: Layout HTML, CSS, Manifest (`theme.json`). JS tab closed with copy pointing at 87k–m + 88b. Save ships in 88g. Preview ships in 88d.

**API (read-only):**

- `GET /api/admin/themes/{id}/files` — allow-listed tree (`html`, `css`, `js`, `json`, `md`) under `backend/resources/views/themes/{id}/`
- `GET /api/admin/themes/{id}/file?path=` — file body; `realpath` + prefix check; max 512 KiB

AuthN + 2FA + `PermissionMiddleware('themes:read')`. Existing import/activate/uninstall stay on `settings:manage`. Catalog permissions `themes:read` / `themes:edit` (ADMIN default). Path traversal rejected; logs use `LogSanitizer` (id/path only).

**Not in 88a:** validate markers, normalize, preview iframe, thumbnail, persist. Local Monaco buffers are tab-only and discarded on leave.

**Reuse:** `MonacoCodeEditor` (same as Translation Editor). **Not reused:** Shortcodes textarea, Code Editor `/code-editor` (Developer Mode).

---

## 88b — shipped (2026-09-10)

**API:** `POST /api/admin/themes/validate` `{ themeId, relativePath, content }` — CSRF + `themes:edit`. Response 200 `{ valid: true, markers: [] }` or 422 `{ valid: false, markers: [{ line, message }] }`. Never writes disk.

**Policy:** same `CodePolicyEngine::validateUntrusted` as ZIP import. HTML/CSS hostile markup (`<script>`, `on*`, `javascript:`, remote `@import`, `url(javascript:)`) lives in `UntrustedMarkupScanner` so Monaco is not a weaker path. `theme.json` also runs `ThemeManifestValidator`. Logs only sanitized id/path/marker count.

**Admin UI:** debounce (~450 ms) paints Monaco markers; JS tab is open for local edit + validate. Preview is 88d. Persist is 88g.

---

## 88d — shipped (2026-09-10)

**API:** `POST /api/admin/themes/preview` `{ themeId, files: { relativePath: content }, template? }` — CSRF + `themes:edit`. Runs 88b validators on HTML/CSS/JS buffers first (fail-closed). Never writes disk. Never puts theme JS into the document.

**Response:** 200 `{ blocked: false, document, template, issues: [] }` or 422 `{ blocked: true, document: "", issues: [{ relativePath, markers }] }`. `document` is a complete srcdoc: CSP `script-src 'none'`, sanitized layout HTML (public `ContentSecuritySanitizer` / `HtmlDomSanitizer` plus header/main/nav), concatenated CSS in a controlled `<style>` block. Sample `{{content}}` / `{{title}}` / `{{siteName}}`; `{{> partial}}` expands from submitted `partials/*.html` only (no `..`).

**Admin UI:** Preview opens an iframe with `sandbox=""` (no `allow-scripts`, no `allow-same-origin`) and `referrerPolicy=no-referrer`. Policy failure keeps the iframe empty and surfaces markers in Monaco.

**Not in 88d:** persist (88g), thumbnail (88e), executing theme JS.

---

## 88c — shipped (2026-09-10)

**API:** `POST /api/admin/themes/normalize` `{ themeId, html?, css?, js?, files? }` — CSRF + `themes:edit`. Never writes disk. Never executes JS.

**Pipeline:** ingest → report → strip/rewrite → package. Full HTML maps `<header>` / `<main>` / `<footer>` / `<aside>` onto slot partials; `<script>`, `<iframe>`, `on*`, `javascript:`, remote `@import`, and CDN stylesheets are dropped and listed. CSS `url(javascript:)` and remote `url(http…)` are stripped. JS that passes the 88b scanner is kept as `assets/theme.js` with SRI in `theme.json`; hostile JS is dropped. PHP / Blade / Twig / foreign `{{` engines **reject the entire import** (422, empty `files`).

**Admin UI:** Normalize rewrites current Monaco buffers and shows the dropped report. Persist is 88g.

---

## 88g — shipped (2026-09-10)

**API:** `POST /api/admin/themes/save` `{ themeId, files: { relativePath: content } }` — CSRF + `themes:edit`. Validates every HTML/CSS/JS/`theme.json` buffer with the 88b policy **before** any write. Fail-closed: 422 `{ blocked: true, written: [], issues }` and no files on disk. Manifest `id` must equal the folder id. Undeclared `.js` (not in `assets.scripts[]`) is 400. Core `paginium-core` cannot be overwritten.

On success: write allow-listed text files under `backend/resources/views/themes/{id}/`, seal SRI if scripts are declared, upsert `data/themes.json` (keeps existing `enabled`). Optional frontend CSS copy only when Developer Mode is unlocked **and** `frontend/src/themes/{id}/` already exists — never writes `PublicShell.tsx`. Activate is **not** inside save; the studio checkbox calls existing `POST /api/admin/themes/{id}/activate` (`settings:manage`).

**Admin UI:** Save collects Monaco buffers. A new draft (`/themes/new`) uses `theme.json` `id`, then navigates to `/themes/{id}/edit`.

---

## 88e — shipped (2026-09-10)

**API:** `POST /api/admin/themes/{id}/thumbnail` (multipart `file`, `themes:edit`) writes `preview.png` after PNG magic + `getimagesize` + size/dimension caps (512 KiB, 2048×2048). `GET …/{id}/thumbnail` (`themes:read`) serves `image/png` with `nosniff` + `inline`. SVG/JPEG/HTML rejected.

**Admin UI:** upload PNG. Capture from the preview iframe is **disabled** — empty `sandbox` without `allow-same-origin` cannot be read (`html2canvas` would fail).

---

## 88f — shipped (2026-09-10)

Studio aside lists header / main / footer / sidebar as found or missing (`{{> header}}`, `{{content}}`, `{{> footer}}`, `{{> sidebar}}` or semantic tags). Click opens the matching partial or `templates/default.html`. Insert places a bundled shortcode sample before `{{content}}`.

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
