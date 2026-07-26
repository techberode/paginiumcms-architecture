# Iteration 58 – Page layout builder + color schemes

**Status:** ⏳ Planned (58a after It.15) · **58b ✅ implemented** (color schemes + appearance)  
**Wave:** Post-15 Editor & UX  
**Priority:** 🟡 Medium — largest slice in wave

## Summary

Editable **page layout** based on **≥5 fixed templates** (not a full Elementor clone): configurable **Header**, **Footer**, and **Body** as named blocks. Each block holds ordered content slots (text, hero, gallery, CTA, …).

**Plus:** predvolené **farebné schémy** (5 trendy presetov) s **náhľadom farieb** a **generickým náhľadom stránky** v zvolenej schéme; verejný web + admin layout editor podporujú **svetlý / tmavý / systémový** režim.

Stays compatible with PaginiumCMS flat-file principles — **no SQL**, **no runtime PHP templates required for MVP**.

## Design principles (Paginium-safe)

| Principle | Rule |
|-----------|------|
| Flat-file SSOT | Layout JSON in content front matter or sidecar `{slug}.layout.json` |
| No heavy framework | Prefer composition of existing React components + CSS grid |
| Static-friendly | Layout resolves to HTML cache (align with It.48) |
| Escape hatch | Markdown-only pages still work — layout optional |
| Performance | Lazy-load block editors; public site reads pre-built structure |
| Theming | CSS variables (`--color-*`) per scheme; light + dark variant each |
| User preference | Site default + optional visitor toggle; `prefers-color-scheme` when `system` |

## Delivery slices (recommended)

| Slice | Scope |
|-------|--------|
| **It.58a** | 5 layout templates + block types + public render |
| **It.58b** | Color scheme catalog, swatch preview, generic page preview, appearance mode |

Slices share settings group `appearance` and layout front matter; ship **58b** together with or immediately after **58a** (same release train).

## Color schemes (preset catalog — minimum 5)

Každá schéma má **light** a **dark** variantu (token set). Admin zobrazí kartu s **5 farebnými swatchmi** (primary, secondary, surface, text, accent) + **mini wireframe** stránky (header → hero → 2 karty → footer).

| ID | Názov (SK) | Charakter | Trend / použitie |
|----|------------|-----------|------------------|
| `indigo-classic` | Indigo Classic | Indigo / violet na slate | Default Paginium — SaaS, tech |
| `ocean-slate` | Ocean Slate | Teal / cyan na cool gray | 2025–2026 „calm tech“, fintech |
| `forest-sage` | Forest Sage | Emerald / sage na warm neutral | Eko, wellness, B2B dôvera |
| `sunset-rose` | Sunset Rose | Coral / rose na cream | Magazín, lifestyle, kreatíva |
| `mono-zinc` | Mono Zinc | Zinc neutrals + jeden accent | Editorial, minimal, portfólio |

Voliteľný 6. preset (nice-to-have v1.1): `midnight-aurora` — fialovo-ružový gradient accent (modern SaaS landing).

### Token model (CSS variables)

Schémy sa neukladajú ako tisíce hexov — iba **sémantické tokeny** mapované na Tailwind / `:root`:

```css
/* public + preview iframe */
:root[data-scheme="ocean-slate"][data-theme="light"] {
  --color-primary: #0d9488;
  --color-primary-foreground: #ffffff;
  --color-secondary: #64748b;
  --color-surface: #f8fafc;
  --color-surface-elevated: #ffffff;
  --color-text: #0f172a;
  --color-text-muted: #64748b;
  --color-accent: #14b8a6;
  --color-border: #e2e8f0;
}
```

Dark variant: rovnaké kľúče, iné hodnoty pod `[data-theme="dark"]`. Verejný layout aplikuje `data-scheme` + `data-theme` na `<html>` (alebo wrapper `#public-root`).

### Swatch preview (admin)

Komponent **`ColorSchemeCard`**:

- Riadok 5 krúžkov / obdĺžnikov: primary · secondary · surface · text · accent
- Label + krátky popis (1 riadok)
- Badge „Predvolená“ na aktívnej schéme
- Klik = select (ukladá sa do settings / page override)

### Generic page preview (admin)

Komponent **`SchemePreviewFrame`** — statický wireframe (nie live slug), rovnaký pre všetky schémy:

```
┌─────────────────────────────────────┐
│ ▓▓▓▓▓▓▓▓▓▓ Header (logo + nav)      │
├─────────────────────────────────────┤
│  Hero title                         │
│  Subtitle · [ CTA button ]          │
├─────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐         │
│  │  Card    │  │  Card    │         │
│  └──────────┘  └──────────┘         │
├─────────────────────────────────────┤
│ ░░░░░░░░░░░ Footer                  │
└─────────────────────────────────────┘
```

- Reaguje na **zvolenú schému** + **preview mode** (light / dark / system)
- Použitie: **Nastavenia → Vzhľad**, layout editor sidebar, `SitePreviewModal` (It.51) — prepínač „Náhľad schémy“
- Implementácia: izolovaný React subtree alebo `<iframe src="/admin/preview/scheme?...">` bez admin bundle na public site

## Appearance mode (light / dark / system)

| Scope | Kde | Správanie |
|-------|-----|-----------|
| **Site default** | Settings `appearance.mode` | `light` \| `dark` \| `system` — verejný web |
| **Visitor override** | Settings `appearance.allowUserToggle` | Voliteľný prepínač v Navbar / Footer (localStorage) |
| **Admin** | Existujúci `ThemeContext` | Zosúladené hodnoty `light` / `dark` / `system` (už v `frontend/src/context/ThemeContext.tsx`) |
| **System** | `prefers-color-scheme` | Pri `system` počúva OS; listener na `matchMedia` change |

**Pravidlo:** farebná schéma = **paleta**; appearance mode = **svetlá vs tmavá varianta** tej istej palety. Nie dve nezávislé veci.

### Settings keys (flat-file)

| Key | Group | Default | Notes |
|-----|-------|---------|-------|
| `appearance.colorScheme` | Vzhľad | `indigo-classic` | ID z katalógu |
| `appearance.mode` | Vzhľad | `system` | Site-wide default |
| `appearance.allowUserToggle` | Vzhľad | `true` | Verejný prepínač témy |
| `appearance.previewTemplate` | Vzhľad | `hero-content` | Wireframe pre scheme preview |

Per-page override (voliteľné, front matter):

```yaml
layout:
  template: hero-content
  colorScheme: sunset-rose   # null = inherit global
  appearanceMode: null       # null = inherit global
```

### Public API

`GET /api/settings/public` → blok `appearance` (scheme id, mode, allowUserToggle — **bez** internal token hexov; FE načíta tokeny z bundled `colorSchemes.ts`).

## Template catalog (minimum 5)

| ID | Name | Structure |
|----|------|-----------|
| `single` | Single column | header → body → footer |
| `hero-content` | Hero + content | header → hero block → body → footer |
| `two-column` | Sidebar right | header → main + aside → footer |
| `landing` | Marketing | header → hero → features grid → CTA → footer |
| `blog-article` | Article chrome | minimal header → body (reuse article renderer) → footer |

## Block types (v1)

- `richtext` (MD or Tiptap slot)
- `hero` (title, subtitle, image, CTA link)
- `imageGallery` (media IDs)
- `htmlEmbed` (restricted — profile-gated)
- `spacer` / `divider`

## Admin UX

- **Layout editor** route: visual outline (not drag-anywhere pixel editor) — reorder blocks, pick template
- **Appearance panel**: grid `ColorSchemeCard` + `SchemePreviewFrame` + mode toggle (light / dark / system)
- Edit Header/Footer globally or per-page override (Settings vs page FM flag)
- Preview via existing `SitePreviewModal` (It.51) — scheme + mode synced with picker

## Storage sketch

**Layout** (unchanged):

```json
{
  "template": "hero-content",
  "header": { "variant": "default", "blocks": [] },
  "body": [
    { "type": "hero", "props": { "title": "…", "image": "media/…" } },
    { "type": "richtext", "props": { "bodyRef": "main" } }
  ],
  "footer": { "variant": "compact", "blocks": [] }
}
```

**Appearance** (settings group):

```json
{
  "colorScheme": "ocean-slate",
  "mode": "system",
  "allowUserToggle": true
}
```

**Bundled schemes** (FE + docs SSOT): `frontend/src/theme/colorSchemes.ts` — export preset tokenov pre 5 schém × 2 módy; PHPUnit contract test len na public API shape (nie duplicita hexov v PHP).

## Backend

- `LayoutValidator` + schema version
- Extend `Page` model / API serialize
- Public API returns resolved layout + cached HTML sections
- `SettingsSchema` group **`appearance`** + public slice
- Validate `colorScheme` against allowlist (`indigo-classic`, …)

## Frontend (planned files)

| Súbor | Úloha |
|-------|--------|
| `frontend/src/theme/colorSchemes.ts` | 5 presetov × light/dark tokeny |
| `frontend/src/theme/applyColorScheme.ts` | `data-scheme` / `data-theme` na root |
| `frontend/src/components/admin/ColorSchemeCard.tsx` | Swatch grid + select |
| `frontend/src/components/admin/SchemePreviewFrame.tsx` | Generic wireframe preview |
| `frontend/src/components/admin/AppearanceSettingsPanel.tsx` | Settings → Vzhľad |
| `frontend/src/hooks/usePublicAppearance.ts` | Public: settings + localStorage override |

| `frontend/src/theme/publicUiClasses.ts` | Zdieľané triedy pre verejný web |
| `frontend/src/theme/defaultTokens.css` | Fallback tokeny pred hydratáciou |

Extend existujúci **`ThemeContext`** — admin mode zostáva; public site číta `appearance.mode` z API a merge s visitor toggle.

**Verejný web (58b finish):** komponenty v `components/frontend/`, auth, maintenance a `paginium-prose` používajú `theme-*` Tailwind triedy mapované na `--color-*` (nie natvrdo `indigo-*` / `slate-*`).

## Dependencies

- ⛔ **[It.15](ITERATION_15.md)** — custom block types as plugins
- ⛔ [It.54/55](ITERATION_54.md) — richtext blocks
- 🟡 [It.48](ITERATION_48.md) — static site generation of layouts
- 🟡 [It.53](ITERATION_53.md) — smooth admin navigation for builder UI

## Out of scope (v1)

- Pixel-perfect free-form canvas
- Third-party React page builders (GrapesJS full import) unless proven lightweight
- Multi-language layout variants (It.18)
- Custom user-defined hex picker / unlimited palettes (only 5 presets + plugin hook later)
- Per-block arbitrary colors (schéma je globálna; bloky používajú tokeny)

## Acceptance criteria

### Layout (58a)

- [ ] 5 templates selectable; at least 2 block types editable
- [ ] Saved layout renders on public `/slug` without admin bundle
- [ ] PHPUnit layout validation rejects unknown block types
- [ ] Vitest block renderer snapshots
- [ ] Documented migration: legacy pages without layout → `single` template default

### Color schemes & appearance (58b)

- [x] 5 preset schemes in catalog; each with light + dark tokens
- [x] Settings UI: swatch row + generic page preview updates live on select
- [x] Mode toggle: light / dark / system; system respects `prefers-color-scheme`
- [x] Public site applies global scheme; optional visitor toggle when enabled
- [x] `GET /api/settings/public` exposes `appearance` block (contract test)
- [x] Vitest: `applyColorScheme`, preview frame renders for all scheme IDs
- [x] Docs: token table per scheme in [architecture/THEMES.md](architecture/THEMES.md) (create at implementation)

## Related

- [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md) — porovnanie 3 architektúr + security checklist (2026-07-26)
- [ITERATION_48.md](ITERATION_48.md) — PHP/static templates
- [ITERATION_51.md](ITERATION_51.md) — live preview modal (scheme preview hook)
- [ITERATION_31.md](ITERATION_BACKLOG.md) — live preview (complements builder)
- `frontend/src/context/ThemeContext.tsx` — existing admin light/dark/system (reuse pattern)
