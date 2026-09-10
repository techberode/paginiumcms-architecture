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

**Theme Studio (It.88):** **Edit** opens Monaco for layout HTML, CSS, optional JS, and `theme.json`. Buffers are checked live against the same code policy as ZIP import (`<script>`, event handlers, and `javascript:` URLs fail closed). **Normalize** strips hostile markup from a pasted template into header/main/footer slots (it will not turn a commercial theme into a pixel-perfect Paginium site). **Preview** renders sanitized HTML/CSS in a sandboxed iframe (no scripts, no same-origin). **New** starts a local draft. Save to disk is not enabled yet (88g). Core JS allow-list / SRI / CSP from It.87 already exist for declared `assets.scripts[]`.

Do not paste an unknown PHP/HTML template into production and expect it to run as-is. Studio normalize (88c) will strip hostile markup; it will not turn a commercial theme into a pixel-perfect Paginium site automatically.

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
