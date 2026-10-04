# CSP `frame-src 'self'` — remove third-party iframe exceptions (planned)

**Status:** ⏸️ **paused by product decision (2026-10-02)** — keep existing YouTube/Vimeo/Google Maps iframe + CSP allow-list for now. Rich layout/interactivity is delivered via **first-party React shortcode islands** instead ([REACT_SHORTCODE_ISLANDS.md](REACT_SHORTCODE_ISLANDS.md)). Revisit It.97 when embed facades become a hard requirement.

**Goal (when resumed):** Public site and default admin CSP must not whitelist Google, YouTube, Vimeo, or other third-party origins in `frame-src`. User-facing video and maps must work without cross-origin iframes in page content.

## Problem

Today several layers weaken the frame boundary:

| Surface | Current compromise | Risk |
|---------|-------------------|------|
| `SecurityMiddleware` default | `frame-src` includes `google.com`, `youtube-nocookie.com`, `player.vimeo.com` | Any XSS or HTML injection can load those frames; CSP no longer fails closed for embeds |
| `ExternalEmbedShortcode` | Renders `<iframe class="paginium-external-embed">` | Third-party player in first-party document |
| `ContentSecuritySanitizer` | Preserves allow-listed embed iframes (ISS-193) | Sanitizer exception path for `iframe` |
| `WidgetCatalog` map widget | Google Maps `<iframe>` via `MapEmbedUrlGuard` | Same CSP dependency |
| `PlaygroundSettings` (when enabled) | Replaces global `frame-src` with Sandpack + **still** `google.com` | Broadens frame policy site-wide, not route-scoped |
| `public/.htaccess` | Duplicate CSP with same third-party `frame-src` | Drift from PHP middleware |
| Admin previews | Sandboxed `iframe` for HTML mail / theme preview | Acceptable only if `sandbox` + no sensitive cookies; keep `'self'` / `blob:` only |

Third-party iframes are **not** required for editorial UX; they were chosen for inline YouTube/Vimeo and map widgets. Replacements exist that keep **`frame-src 'self'`** (and optional `blob:` for Sandpack on a dedicated route).

## Target architecture

### Public content embeds (`:::embed`)

Replace iframe output with a **facade block** (no nested browsing context):

- Markup: `<figure class="paginium-embed-facade paginium-embed-facade--{provider}">` containing:
  - Poster area (static SVG or self-hosted placeholder; optional future: cached thumbnail job)
  - `<a href="{canonical watch URL}" rel="noopener noreferrer" target="_blank">` with accessible label
  - Optional `<figcaption>` unchanged
- Layout classes (`--align-*`, `max-width`) mirror current embed CSS on the facade, not `iframe`
- Admin preview (`embedShortcode.ts`, `EmbedInsertModal`) uses the same facade HTML for WYSIWYG parity
- `ExternalEmbedShortcode::isAllowedIframeSrc()` → rename or replace with URL allow-list for **link targets** only; raw pasted `<iframe>` in Markdown continues to convert to `:::embed` blocks, not pass through as HTML

### Map widget

Replace Google Maps iframe with:

- Static map image from an allowed **image** origin (e.g. OpenStreetMap static tile or self-generated PNG) **or** branded placeholder
- Primary action: link to maps URL (Google/OpenStreetMap) in new tab
- `MapEmbedUrlGuard` validates **outbound link** URLs, not iframe `src`

### CSP defaults

- `SecurityMiddleware`: `csp_frame_src` → `frame-src 'self'` only (plus `blob:` if needed for first-party workers, not third-party hosts)
- Remove third-party hosts from `public/.htaccess` snippet when middleware is source of truth
- **Playground:** scope Sandpack `frame-src` to `/playground` (or sub-resource response) via route-aware middleware — do **not** override global CSP when playground is enabled
- Document deploy checklist: operators must not re-add YouTube/Google to `frame-src` in reverse proxies

### Sanitizer

- Remove special-case preservation of `paginium-external-embed` iframes
- Facade markup uses only allow-listed tags (`figure`, `a`, `img`, `div`, `span`) already permitted in prose

### Admin-only iframes

Keep where unavoidable, under stricter rules:

- Message HTML preview, PDF viewer, theme preview: `sandbox` attribute, no `allow-same-origin` for untrusted HTML, `srcdoc` or `'self'` URLs only
- Never load third-party `src` in admin iframes except Sandpack on the playground route (explicit operator opt-in)

## Migration

1. Ship facade HTML + CSS; feature-flag or setting `content.embedMode: iframe|facade` default **`facade`** for new installs
2. One-time content regen: expand `:::embed` to facade on save or via CLI regen (cached HTML invalidation)
3. Tighten CSP to `'self'`; run hostile fixtures + embed tests
4. Update user docs (`MEDIA_IN_CONTENT.md`, `CONTENT_EDITOR.md`) — no operator `frame-src` exceptions for YouTube/Vimeo
5. Remove `google.com` from `PlaygroundSettings::SANDBOX_FRAME` when maps use facade in docs examples

## Tests to update

- `ExternalEmbedShortcodeTest` — expect facade classes, no `<iframe`
- `ContentSecuritySanitizerTest` — drop iframe preserve cases; add facade strip/harden cases
- `SecurityMiddlewareTest` — default CSP without third-party `frame-src`
- `MarkdownContentParserTest` embed case
- FE `embedShortcode.test.ts`, visual regression for `.paginium-embed-facade` in `pgLayout.css`

## Relation to other docs

- [REACT_SHORTCODE_ISLANDS.md](REACT_SHORTCODE_ISLANDS.md) — interactive UI via hydration, not arbitrary iframes in body
- It.91 / ISS-193 — historical iframe embed path; this iteration **supersedes** the public iframe model
- It.95 playground — route-scoped CSP contributor, not global third-party frames

## Suggested backlog id

**It.97** (or next free slot): **CSP frame-src self-only + embed/map facades**
