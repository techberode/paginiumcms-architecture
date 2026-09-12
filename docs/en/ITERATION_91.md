# Iteration 91 — Trusted HTML & external embeds (role-based)

> **Status:** 🟡 partial — **91a + 91b shipped** (trusted HTML + YouTube/Vimeo embeds); **91c** Tiptap/audit pending  
> **Priority:** 🟡 P1 — developer/admin authoring without lowering public XSS bar  
> **Wave:** Editor security & embeds (post It.79, parallel with It.90)  
> **Depends on:** [It.79](ITERATION_79.md) DAM video · [It.55](ITERATION_55.md) Tiptap · [It.19](ITERATION_19.md) content security settings · RBAC (`PermissionCatalog`)  
> **Related:** [It.90](ITERATION_90.md) toolbar builder (HTML block tool appears when permitted)

## Goal

Allow **verified staff** (ADMIN / SUPER_ADMIN by default, configurable via ACL) to insert **controlled HTML fragments** and **external media embeds** (YouTube, Vimeo) without opening arbitrary `<script>` / `<iframe>` paste to all editors.

**Invariant:** the React **“Insert HTML block”** button bypasses WYSIWYG formatting only — **never** backend sanitization. Public render stays at or above today’s security level.

---

## Problem

| Need | Today | Gap |
|------|-------|-----|
| Custom layout HTML for landing sections | Blocked — raw HTML rejected in Markdown | Trusted channel needed |
| YouTube player in article | Blocked — no iframe | Provider shortcode needed |
| External hyperlink | ✅ works | — |
| Self-hosted video | ✅ It.79 `:::video` | — |

---

## Architecture

```
Author (FE)                    Save (BE)                         Public render
─────────                      ─────────                         ─────────────
[Insert HTML block] modal  →   EditorContentValidator            MarkdownContentParser
  inserts :::html-safe         + permission check                  → HtmlSafeShortcode
                               + TrustedHtmlPurifier               → ContentSecuritySanitizer
[Insert embed] wizard      →   + permission content:embed-*        → EmbedShortcode → sandboxed iframe
                               Audit log (trusted save)
```

### Defense in depth (unchanged baseline)

1. **Permission gate** — `content:trusted-html` / `content:embed-external`
2. **Site master switch** — `editor.trustedHtmlEnabled` (default `false` fail-closed for strict sites)
3. **Block isolation** — HTML only inside `:::html-safe` … `:::`, embeds only inside `:::embed`
4. **Purify on save** — [HTMLPurifier](https://github.com/ezyang/htmlpurifier) with strict config per role tier
5. **Purify on render** — existing `ContentSecuritySanitizer` + `HtmlDomSanitizer` (second pass)
6. **Hard deny always** — `script`, arbitrary `iframe` in html-safe body, `on*` attrs, `javascript:` URIs

---

## Permissions (RBAC)

| Permission | Default roles | Purpose |
|------------|---------------|---------|
| `content:trusted-html` | SUPER_ADMIN, ADMIN | Insert/edit `:::html-safe` blocks |
| `content:embed-external` | SUPER_ADMIN, ADMIN | Insert `:::embed` (YouTube/Vimeo) |

EDITOR / USER: **denied** unless explicitly granted in Settings → Access control.

Validator receives **authenticated user** at save time; missing permission → **422** with generic message (no block body leak).

---

## Settings

Under `editor`:

```yaml
editor:
  trustedHtmlEnabled: false          # master switch; both permissions ineffective when false
  embedProvidersEnabled:             # or flat list embedAllowedProviders
    - youtube
    - vimeo
```

Under `contentSecurity` (existing group):

```yaml
contentSecurity:
  trustedHtmlAllowedTags: "div,span,p,a,img,table,thead,tbody,tr,th,td,ul,ol,li,strong,em,blockquote,code,pre,h1,h2,h3,h4"
  # script, iframe, object, embed, form never allowed here
```

---

## Content formats

### `:::html-safe` (trusted HTML block)

```markdown
:::html-safe
<div class="grid-2">
  <p>Custom layout</p>
</div>
:::
```

| Rule | Detail |
|------|--------|
| Who can save | `content:trusted-html` + `editor.trustedHtmlEnabled` |
| Processing | Body → `TrustedHtmlPurifier` → stored purified |
| Markdown without block | Still **no** raw HTML tags (current rule) |
| FE | Modal textarea / optional Monaco; toolbar capability `htmlBlock` |

### `:::embed` (external provider)

```markdown
:::embed
provider: youtube
id: dQw4w9WgXcQ
:::
```

Inline form: `:::embed provider="youtube" id="dQw4w9WgXcQ" :::`

| Provider | Allowed host | Render |
|----------|--------------|--------|
| `youtube` | `youtube.com`, `youtube-nocookie.com` | BE-generated `<iframe>` fixed attrs + `sandbox` |
| `vimeo` | `player.vimeo.com` | same pattern |

**No** user-supplied iframe `src`. ID validated with strict regex.

---

## Backend components

| Component | Path (planned) | Role |
|-----------|----------------|------|
| `TrustedHtmlPurifier` | `Core/Security/Services/` | HTMLPurifier wrapper, config from settings |
| `HtmlSafeShortcode` | `Core/Editor/Services/` | expand `:::html-safe` at parse time |
| `ExternalEmbedShortcode` | `Core/Editor/Services/` | expand `:::embed` → sandboxed iframe HTML |
| `EditorContentValidator` | extend | role-aware; allow html-safe/embed blocks |
| `MarkdownContentParser` | extend | register expanders before CommonMark |
| `ContentBodyRenderer` | unchanged pipeline | sanitize after expand |
| Audit | `SecurityAuditStore` or upload audit pattern | log trusted-html save (user id, content id, hash) |

### HTMLPurifier config (trusted tier)

- `HTML.Allowed` from `contentSecurity.trustedHtmlAllowedTags`
- `URI.AllowedSchemes` → `http`, `https`, `mailto`
- `Attr.AllowedClasses` → optional regex / null (class allowed, no `style`)
- `HTML.ForbiddenElements` → `script`, `iframe`, `object`, `embed`, `form`, `input`
- `CSS.AllowedProperties` → empty (no inline style)

Default/public tier keeps existing `HtmlDomSanitizer` only.

---

## Frontend components

| Component | Change |
|-----------|--------|
| `MarkdownContentEditor` | toolbar `htmlBlock` + `externalEmbed` when permissions + settings |
| `HtmlBlockInsertModal` | textarea → `buildHtmlSafeShortcode()` |
| `EmbedInsertModal` | provider picker + ID field → `buildEmbedShortcode()` |
| `WysiwygEditor` | optional `htmlSafeBlock` / `externalEmbed` nodes (export to shortcodes) |
| `htmlSafeShortcode.ts` / `embedShortcode.ts` | mirror BE for admin preview |
| Auth | expose `permissions[]` on session/user API if not already; gate UI |

Preview uses `sanitizePublicHtml` — **informational**; BE is authoritative.

---

## Phased delivery

| Phase | Scope | Outcome |
|-------|--------|---------|
| **91a** | Permissions, settings, `:::html-safe`, HTMLPurifier, role-aware validator, FE HTML modal | Trusted HTML for admins |
| **91b** | `:::embed` YouTube + Vimeo, FE embed wizard, sanitizer tests | External players without raw iframe paste |
| **91c** | Tiptap nodes + export; audit log; i18n polish | Parity with It.90 toolbar direction |
| **91d** | Hostile fixtures, docs, iteration gate | Ship-ready |

It.90 toolbar builder can add `htmlBlock` / `externalEmbed` as optional tools gated by the same permissions.

---

## Security (strict)

1. Never disable `contentSecurity.sanitizeHtmlOnSave` for trusted path.
2. Never allow `script` or free-form `iframe` in `:::html-safe`.
3. Embed iframes only from BE template with allow-listed `src` host.
4. CSRF + `content:edit` unchanged on save endpoints.
5. Permission checks server-side only — UI hide is not sufficient.
6. Regression: EDITOR without permission cannot save html-safe even via API tampering.

---

## Out of scope

- Arbitrary iframe URL paste.
- MDX / JSX / user JavaScript in content.
- TikTok/Spotify/other providers until allow-list entry + renderer added.
- Disabling sanitizer globally for “trusted users”.

---

## Tests

- Permission denied → 422 on `:::html-safe` save.
- Master switch off → 422 even for ADMIN.
- HTMLPurifier strips `<script>`, `onload=`, `javascript:` href.
- `:::embed` youtube valid id → iframe with nocookie host option.
- Invalid provider / id → validator error, empty public output.
- Markdown without permission: raw `<div>` still rejected.
- Vitest: modals, shortcode builders; PHPUnit: purifier, expanders, validator.

---

## Definition of Done

- [x] Permissions + settings wired; fail-closed defaults (`trustedHtmlEnabled` default `false`).
- [x] `:::html-safe` + HTMLPurifier on save and expand (91a).
- [x] `:::embed` YouTube/Vimeo with BE-only iframe HTML (91b).
- [x] FE “Insert HTML block” modal gated by permission + setting (91a).
- [ ] Audit log for trusted HTML saves (91c).
- [x] PHPUnit regression for purifier/validator; iteration gate green (91a).
- [ ] Full DoD including 91b–91d + CHANGELOG release section.

## Related

[It.90 Editor Workbench](ITERATION_90.md) · [It.79 DAM video](ITERATION_79.md) · [developer/SECURITY.md](developer/SECURITY.md) · [ISSUES.md](../ISSUES.md)
