---
title: Appearance and Color Schemes
description: Configuring public light/dark mode, schemes, and preview
icon: material/palette
---

# Site Appearance — User Guide

> **Route:** **Settings → Appearance**  
> The current Public Beta manages color schemes and light/dark mode. It is not yet an installer for external theme packages.

---

## 1. What you can configure

| Setting | Meaning |
|---------|---------|
| Color scheme | semantic color set for the public site |
| Mode | `light`, `dark`, or `system` |
| Allow visitor toggle | exposes a light/dark control in public UI |
| Preview profile | sample wireframe in administration |

The administration shell's light/dark appearance is a personal workspace setting and is separate from the public site.

---

## 2. Choosing a scheme

Implemented presets:

- `indigo-classic`,
- `ocean-slate`,
- `forest-sage`,
- `sunset-rose`,
- `mono-zinc`.

1. Open **Appearance**.
2. Click a scheme card.
3. Inspect both light and dark preview.
4. Save settings.
5. Open the public site in a private window and verify the actual result.

Preview is a useful approximation, not a pixel-perfect snapshot of every page and plugin.

---

## 3. Light, dark, and system

| Mode | Behavior |
|------|----------|
| `light` | site defaults to light appearance |
| `dark` | site defaults to dark appearance |
| `system` | follows operating-system/browser preference |

When visitor toggle is enabled, a visitor can store a local browser choice. It does not change the global setting for other users.

---

## 4. Branding and content

A color scheme does not change:

- logo and favicon,
- site name,
- login background,
- Open Graph image,
- content images,
- page text or layout AST.

These layers are configured separately. A logo should remain readable on both the chosen light and dark surfaces; a transparent low-contrast logo can disappear in one mode.

---

## 5. Checks after a change

Verify at least:

- navbar and footer,
- buttons and focus state,
- forms and validation errors,
- an article with links and a code block,
- login/register/maintenance screens,
- mobile width,
- logo contrast,
- both light and dark modes.

A cache or static-publish profile may require invalidation/rebuild/publish according to the deployment mode.

---

## 6. Theme packages and Theme Studio

Installed appearance packages live under **Build → Themes** (`/themes`): ZIP import (code policy), activate, rollback, uninstall.

**Theme Studio (It.88):** **Edit** / **New** opens Monaco for layout HTML, CSS, optional JS, and `theme.json`. Buffers are checked live against the same code policy as ZIP import (`<script>`, event handlers, and `javascript:` URLs fail closed). **Open playground** (It.95b) sends the current CSS/HTML/JS (or TSX/JSX) buffer into Sandpack; **Export to Monaco** validates it and returns to this editor — it does not save the package by itself. **Normalize** strips hostile markup from a pasted template into header/main/footer slots (it will not turn a commercial theme into a pixel-perfect Paginium site). **Preview** renders sanitized HTML/CSS in a sandboxed iframe (no scripts, no same-origin). **Save** writes the package to disk when policy is clean; optional **Activate after save** uses the existing Themes activate action (`settings:manage`). Upload a PNG thumbnail (`preview.png`); capture from the preview iframe is blocked by the sandbox. The slot panel shows header/main/footer/sidebar and can insert a bundled shortcode into main. Core JS allow-list / SRI / CSP from It.87 already exist for declared `assets.scripts[]`.

Do not paste an unknown PHP/HTML template into production and expect it to run as-is. Studio normalize (88c) will strip hostile markup; it will not turn a commercial theme into a pixel-perfect Paginium site automatically.

### Importing a free HTML template (non-developer)

Paginium is a CMS with its own menu, pages, and articles. A TemplateMo/HTML5 demo is often a **one-screen art piece**. You can get close to the look; you cannot drop the live demo URL into the CMS and have every component auto-wire.

**Compression (TemplateMo 620)** is a good candidate: kinetic panels and `:target` overlays are **CSS-only** (no jQuery). Still use the **ZIP**, not `templatemo.com/live/…` (that page is ads + Google tags, not the theme).

1. Download the free ZIP from the template’s product page. Keep the author’s credit as the license requires.
2. Unzip. Open `index.html` and the `.css` file(s) in a text editor. Ignore `*.js` unless you later use the Theme Studio JS tab + `appearance.themeScriptsEnabled` (default off — leave it off for Compression).
3. In admin: **Build → Themes → New**. Paste CSS into the CSS tab. Paste the **body** markup (the five panels) into the layout HTML — prefer the **main** slot. If **Normalize** splits a full document into header/main/footer and the five-panel layout breaks, undo and paste only the panel markup into main.
4. **Preview** (sandbox, no scripts). Hover panels and `:target` links should still move if the CSS survived.
5. **Save**, then **Activate**. Check the public site. Core navbar/footer may sit on top of a full-viewport template — hide or restyle them in the theme CSS if you want the original full-bleed look.
6. Put **your** text in Paginium: landing page / Work / About / Contact as normal pages. The template’s overlay pages are demos; they are not a CMS. Either keep short overlay copy in the theme HTML, or replace `:target` links with real Paginium navigation items (look will drift; content becomes editable).

| Same as the demo? | Compression |
|-------------------|-------------|
| Dark industrial colors, Anton / Space Mono, hover expand/compress, grayscale idle panels | Yes, if CSS + panel HTML stay together |
| Work / About / Contact as CSS `:target` modals | Yes, while that HTML stays in the theme |
| Editable articles, blog, newsletter, cookie bar, CMS menu | Those are Paginium, not the ZIP — they will not appear until you add them as pages/settings |
| Pixel-identical mobile + every overlay | Only if you accept the template as a mostly-static shell |

Templates that need Bootstrap + jQuery sliders/counters will **lose** those effects after Normalize (scripts stripped). Recreate them with CSS, a Core component, or an [isolated origin iframe](../architecture/ISOLATED_ORIGIN.md) — do not paste `custom.js` into a page.

---

## 7. Troubleshooting

| Problem | Check |
|---------|-------|
| scheme did not save | validation error, permission, settings log |
| public site shows old colors | cache, service worker, hard refresh, publish/rebuild |
| only admin has a different theme | expected; admin and public themes are separate |
| logo is unreadable | transparency and contrast in light/dark mode |
| part of a plugin uses fixed colors | plugin does not use semantic tokens; report a compatibility issue |
| system mode changes automatically | browser follows OS `prefers-color-scheme` |

---

## Related documents

- [Theme architecture](../architecture/THEMES.md)
- [Logo and favicon](BRANDING.md)
- [Settings](../architecture/SETTINGS.md)
- [Admin guide](ADMIN_GUIDE.md)
