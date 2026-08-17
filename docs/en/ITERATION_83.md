# Iteration 83 — Theme runtime and reference “Terminal Breach” theme

> **Status:** ⏳ planned — **first post-stable product slice** (after `v2.2.0` + It.25)  
> **Priority:** 🟡 medium (presentation; no content-model change)  
> **Wave:** Layout & appearance (extends It.58b/c + It.67b foundation)  
> **Depends on:** It.67b theme ZIP import/registry, It.58b appearance tokens, It.58c `PageLayoutShell`, stabilization exit per [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md)  
> **Snapshot:** 2026-08-17 · design approved; implementation deferred during stabilization freeze

## Goal

Ship the **first end-to-end theme package lifecycle**:

1. **Theme runtime** — activate/deactivate a theme from admin, persist choice in settings, render the public site through the active theme shell, rollback on failure, invalidate render cache.
2. **Reference theme `terminal-breach`** — bundled cyber-security / hacking aesthetic (professional SOC-terminal look, not meme “Matrix rain”).
3. **Admin UX** — list themes alongside or within **Extensions** (Plugins | Themes), with preview screenshot and one-click activate.

Color schemes (It.58b) and layout templates (It.58c) remain separate layers. A theme package owns the **public shell** (header, footer, nav chrome, default typography); schemes still override semantic tokens.

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **83a** | Settings + active theme contract | 🟡 P1 | ⏳ planned | `appearance.activeThemeId`, public API slice, safe fallback to `paginium-core` |
| **83b** | Theme runtime (activate / rollback) | 🟡 P1 | ⏳ planned | Enable/disable in registry; atomic switch; store previous theme ID; cache invalidation hook |
| **83c** | Admin UI (Extensions → Themes) | 🟡 P2 | ⏳ planned | List bundled + imported themes; import ZIP (existing API); activate/deactivate; preview frame |
| **83d** | Public renderer integration | 🟡 P1 | ⏳ planned | Resolve shell from `activeThemeId`; slot contract (`header`, `main`, `footer`); respect `layoutTemplate` + branding |
| **83e** | Bundled **`terminal-breach`** theme | 🟡 P2 | ⏳ planned | Shipped in repo; dark-default; monospace; cyber-security visual language (see §4) |

Optional follow-up (not blocking 83):

| ID | Slice | Note |
|----|-------|------|
| **83f** | Demo seed page | “Security Posture” sample content on first activate |
| **83g** | Second theme package | e.g. `clean-journal` to prove switchability |

---

## What already exists (It.67b foundation)

| Piece | Location | Gap |
|-------|----------|-----|
| ZIP import + policy scan | `ThemeImporter`, `UntrustedPolicyScanner` | ✅ import only |
| Registry | `data/themes.json`, `ThemeRegistry` | ✅ no activate API |
| Admin API | `GET/POST /api/admin/themes`, `DELETE …/{id}` | ❌ no `POST …/activate` |
| FE API client | `frontend/src/api/themes.ts` | ❌ no admin UI component |
| Fixture theme | `backend/resources/views/themes/test-theme/` | test-only, not public runtime |
| Public appearance | `colorSchemes.ts`, `PageLayoutShell` | hardcoded core shell |

See [architecture/THEMES.md](architecture/THEMES.md) §7–13.

---

## Reference theme: Terminal Breach (`terminal-breach`)

**Character:** dark security-ops dashboard — readable, WCAG-conscious, CSP-safe (CSS only, no inline scripts).

### Visual language

| Element | Design |
|---------|--------|
| **Palette (dark default)** | surface `#0a0e14`, elevated `#121820`, primary `#00e676`, accent/alert `#ff1744`, text `#c9d1d9`, muted `#6e7681`, border `#21262d` |
| **Typography** | `JetBrains Mono` / `IBM Plex Mono` for UI; optional `Space Grotesk` for display headings |
| **Header** | Command-prompt chrome: `paginium@cms`, path-style active nav, subtle cursor blink (`prefers-reduced-motion` respected) |
| **Hero** | Dark gradient, 3–5% scanline overlay, optional CSS `steps()` typewriter on `h1` only |
| **Cards / articles** | Log-entry meta lines (`[timestamp] INFO`), 3px left border by severity (ok / warning / danger) |
| **Code blocks** | GitHub-dark-style surface `#0d1117`, fake window chrome row |
| **Footer** | “System status” strip (uptime-style copy; static or settings-driven — no fake metrics in v1) |

### Explicit non-goals (avoid cheap hacker clichés)

- Full-screen Matrix character rain  
- Anonymous-mask / red-black shock aesthetic  
- Aggressive neon everywhere or `<marquee>`  
- Inline `<script>` animations (It.67 CSP)  
- Contrast failures on body text or buttons  

### Package layout (target)

```text
backend/resources/views/themes/terminal-breach/
├── theme.json
├── templates/
├── partials/
└── assets/terminal-breach.css

frontend/src/themes/terminal-breach/
└── PublicShell.tsx          # header/footer/nav wrapper (build-time bundled)

data/themes.json             # registry entry; bundled theme pre-registered on install
```

Example manifest excerpt:

```json
{
  "manifestVersion": 1,
  "id": "terminal-breach",
  "name": "Terminal Breach",
  "version": "1.0.0",
  "minCmsVersion": "2.2.0",
  "defaultColorScheme": "terminal-breach",
  "defaultMode": "dark",
  "slots": ["header", "main", "footer"],
  "templates": ["default", "article", "landing-page"],
  "supports": ["appearance-tokens", "branding", "navigation", "layout-templates"]
}
```

Bundled color scheme **`terminal-breach`** (6th preset) ships with 83e — token values aligned to the table above. Selecting another scheme in Settings → Appearance still overrides tokens while the theme shell stays active.

### Shortcode / layout synergy (It.58d / S12)

Reuse and extend `frontend/src/theme/pgLayout.css` utilities:

| Block | Terminal Breach styling |
|-------|-------------------------|
| `pg-alert` warning | Amber border, ⚠ label |
| `pg-alert` danger | Red glow, “THREAT DETECTED” tone |
| `pg-coming-soon` | “ACCESS RESTRICTED” + subtle scan animation |
| `pg-grid` | Dashboard tiles with hover lift |

---

## Settings contract (83a)

| Key | Default | Meaning |
|-----|---------|---------|
| `appearance.activeThemeId` | `paginium-core` | Active public theme package ID |
| `appearance.previousThemeId` | `null` | Last theme before switch (rollback) |

Public settings API exposes only `activeThemeId` (and existing appearance slice). Unknown ID → fallback `paginium-core` + admin warning.

---

## Admin UX (83c)

**Route:** `/extensions` with tabs **Plugins | Themes** (preferred) or dedicated `/themes`.

| Action | Behavior |
|--------|----------|
| List | Bundled + imported themes; screenshot, version, active badge |
| Activate | Preview optional → set active → invalidate cache → smoke check |
| Deactivate | Revert to `paginium-core` |
| Import ZIP | Existing It.67b flow |
| Uninstall | Blocked while active; same as plugins |

Auth: `themes:manage` permission (or reuse extensions permission with explicit audit).

---

## Definition of Done (83)

### 83a–83d (runtime)
- [ ] `appearance.activeThemeId` in schema with default and migration for existing installs
- [ ] Activate/deactivate API + PHPUnit tests
- [ ] Public site renders through active theme shell; `layoutTemplate` + branding unchanged
- [ ] Rollback to previous theme on critical render failure (logged, admin toast)
- [ ] Cache/index invalidation on theme switch (It.69 alignment)
- [ ] Gate green; smoke: switch theme → public URL visually different → revert

### 83e (Terminal Breach)
- [ ] Bundled in repo; visible in Themes list without ZIP upload
- [ ] Dark-default; monospace; header/footer/shell distinct from core
- [ ] Works with all five layout templates + at least three shortcodes (`alert-box`, `landing-hero`, `coming-soon`)
- [ ] WCAG spot-check: body text, buttons, focus rings in dark mode
- [ ] Screenshot in docs; CHANGELOG entry

---

## Stabilization: what we can do **now** vs **after stable**

During [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) (**It.83 is frozen** — no runtime code):

| Allowed now | Deferred to It.83 |
|-------------|-------------------|
| This iteration doc + backlog entry | `activeThemeId`, activate API, public shell resolver |
| Design tokens spec (§4 above) | `ThemesManager` admin UI |
| **S12 polish:** cyber-styled `pg-*` blocks in `pgLayout.css` (works on **any** scheme today) | Bundled `terminal-breach` package + `PublicShell.tsx` |
| Optional 6th color scheme **`terminal-breach`** tokens only (Settings → Appearance swatch) — **prep**, not full theme | Theme switch in Extensions |
| `coming-soon` shortcode seed (S12 checklist) | Demo “Security Posture” page seed |
| Document limitation: color scheme ≠ theme package ([user/THEMES.md](user/THEMES.md)) | ZIP lifecycle UX polish |

**Recommended order after stable tag:**

```text
It.25 (stable blocker) → v2.2.0 tag → It.83a–d runtime → It.83e Terminal Breach → optional 83f demo seed
```

---

## Security (inherits It.67)

- Theme ZIP: existing `UntrustedPolicyScanner` + manifest validator  
- No raw PHP in untrusted themes; declarative templates / React shell only  
- Assets CSP-compatible; no user-controlled remote script URLs in manifest  
- Activation does not bypass AuthN/AuthZ on admin routes  

See [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md) and [THEMES.md](architecture/THEMES.md) §9.

---

## Related documents

| Doc | Role |
|-----|------|
| [architecture/THEMES.md](architecture/THEMES.md) | Three-layer model (admin / appearance / theme package) |
| [user/THEMES.md](user/THEMES.md) | Current color-scheme user guide |
| [ITERATION_58.md](ITERATION_58.md) | Layout templates + appearance |
| [ITERATION_67.md](ITERATION_67.md) | Theme ZIP import foundation |
| [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) | Freeze rules and S12 appearance polish |
