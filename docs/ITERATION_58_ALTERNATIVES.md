# It.58 — Layout & page-building alternatives (decision + phased plan)

> **Status:** Decision document for [ITERATION_58.md](ITERATION_58.md)  
> **Updated:** 2026-07-30 · CMS `v2.1.0-beta.21` · React SPA · flat-file SSOT · **58b shipped** (appearance / color schemes)  
> **Language:** English (project docs standard)

This document replaces the earlier 2026-07-26 comparison (react-grid-layout vs sequential DnD vs static HTML).  
Goal: a **Paginium-native** stack with **several layout builders the user can switch between in Settings** — not role-locked “skill tracks” — while keeping security, flat-file SSOT, speed, and SEO.

---

## 1. Design north star

| Constraint | Rule |
|------------|------|
| **Settings switch, not role lock** | User (or site default) picks **which layout builder to work with** in Settings / editor chrome. Roles may *allow* Advanced, but do **not** force a single builder per persona. |
| **No heavy third-party builders** | No GrapesJS / Elementor clones as core. Optional thin DnD only for block reorder. |
| **Flat-file SSOT** | Layout + content stay JSON/MD on disk; public render is deterministic. |
| **Speed & SEO** | Public HTML from allow-listed structure — target sub-ms PHP path after cache warm (It.48). |
| **Security first** | Allow-lists (block types, shortcode names, `pg-*` classes, templates). Sanitize HTML; no `eval`. |
| **Fail-closed untrusted** | Plugins / themes / layout shortcodes / Monaco: always `CodePolicyEngine` + artifact schema — cannot disable for non-core. |
| **Live layout preview** | Same UX idea as **58b color schemes**: card/wireframe **LayoutPreviewFrame** updates when template / shortcode / outline changes. |
| **Developer = Monaco** | Advanced lane edits shortcodes / layout markup / template JSON in **Monaco** (already in Code Editor), with insert helpers — not a free-for-all textarea. |
| **Originality** | Multi-builder switch + shared AST + scheme-like preview — not a me-too pixel canvas. |

---

## 2. How the user chooses a builder (important)

**Wrong model:** “Beginner role → only templates; Developer role → only Monaco.”  
**Right model:** Settings (and/or page editor toolbar) expose:

```
Layout builder:  ○ Templates   ○ Shortcodes   ○ Block outline   ○ Developer (Monaco)
```

| Setting | Scope | Notes |
|---------|--------|-------|
| `layout.builderMode` (working name) | **User preference** and/or **site default** | `templates` \| `shortcodes` \| `outline` \| `developer` |
| Per-page override | Optional | Page can pin a mode for that document |
| Capability gate | Soft | e.g. EDITOR: templates + shortcodes + outline; `developer` may require ADMIN / Developer Mode unlock — **still a switch**, not a separate product |

All modes read/write the **same canonical layout AST** (plus body shortcodes where applicable). Switching mode does not wipe content; the UI changes, the SSOT stays.

```
Settings: active builder ──► Editor chrome (Templates | Shortcodes | Outline | Monaco)
                                      │
                                      ▼
                            Canonical layout AST (+ body)
                                      │
                                      ▼
                     LayoutPreviewFrame (like SchemePreviewFrame)
                                      │
                                      ▼
                            Public HTML (+ cache)
```

### Personas (guidance only — not hard locks)

| Persona | Likely picks | Still can open |
|---------|--------------|----------------|
| Beginner | **Templates** | Shortcode palette later |
| Experienced blogger | **Shortcodes** | Templates / outline |
| Developer | **Developer (Monaco)** + custom shortcode defs | Any other mode for content editors on the team |

---

## 3. Four builders (same product)

### Builder A — Page templates

Pick named template (`landing-page`, `contact`, `services`, `single`, `blog-index`).  
JSON slot definitions; fill hero/body/gallery in forms.

**Preview:** `LayoutPreviewFrame` shows wireframe for that template (header / hero / columns / footer), updating on template change — mirror of `SchemePreviewFrame`.

### Builder B — Shortcodes

Author in MD/Tiptap with palette insert:

```text
[section layout="2-columns"]…|||…[/section]
[callout tone="info"]…[/callout]
[feature-gallery]
```

**Creating / editing shortcode definitions (core + site):** **Monaco** panel (Developer builder or Settings → Layout → Shortcodes) with schema JSON + example body — not ad-hoc PHP in the browser.

**Preview:** Preview frame expands shortcodes to the same wireframe / mini render.

### Builder C — Block outline (optional phase)

Sequential sections + reorder + width enums → `pg-*` classes.  
Comfort UI for people who want drag without code.

### Builder D — Developer (Monaco)

- Edit layout sidecar / template JSON in Monaco  
- Edit custom shortcode definitions (name, attr schema, expand template) in Monaco  
- Safe `pg-*` markup snippets with autocomplete from allow-list  
- Lint / validate before save (unknown shortcode, illegal class → error)

Reuse existing `@monaco-editor/react` from Code Editor; lazy-load in layout settings.

---

## 4. Layout preview (parity with color schemes)

| Color schemes (58b) | Layout builders (58c+) |
|---------------------|-------------------------|
| `ColorSchemeCard` + swatches | `LayoutBuilderCard` (mode icon + short description) |
| `SchemePreviewFrame` wireframe | **`LayoutPreviewFrame`** — same generic page chrome, slots reflect **active template / AST** |
| Live update on scheme select | Live update on template / shortcode / outline / Monaco validate-success |
| Light/dark/system | Same appearance tokens on preview |

Settings → **Layout** (or Appearance sibling): builder switcher **above** the preview, so the user sees *what they will work with* before opening a page.

---

## 5. Safe Grid / Flex (`pg-*`)

Not raw Tailwind from users. Curated `pg-*` classes emitted by shortcodes / outline / Monaco snippets.

```html
<div class="pg-grid pg-grid-cols-1 pg-md:grid-cols-3 pg-gap-4">…</div>
```

Cards / alerts / banners = named shortcodes or blocks, not inventing Bootstrap per page.

---

## 6. What we reject as core

| Alternative | Verdict |
|-------------|---------|
| react-grid-layout pixel canvas | ❌ |
| GrapesJS / full visual builders | ❌ |
| Arbitrary user Tailwind / inline styles | ❌ |
| Role-exclusive builders (no Settings switch) | ❌ |
| Runtime PHP templates as only path | ❌ MVP |

---

## 7. Recommended architecture

**Name:** **Paginium Layout Switch** (multi-builder, one AST)

| Layer | Storage | Editor UI |
|-------|---------|-----------|
| Active builder mode | Settings / user prefs | Switcher + `LayoutPreviewFrame` |
| Template ID | Page FM | Template picker |
| Shortcodes | Body + registry | Palette + **Monaco** for definitions |
| Outline JSON | `{slug}.layout.json` | Outline UI |
| `pg-*` CSS | Bundled stylesheet | Monaco snippets / shortcode expand |
| Feature gallery | It.65 API | Block / `[feature-gallery]` |

---

## 8. Phased delivery (one iteration)

### Phase 58c — Templates + Settings switch + LayoutPreviewFrame

- [ ] `layout.builderMode` (site default + user preference)
- [ ] Template catalog ≥5; picker in page editor when mode = templates
- [ ] **`LayoutPreviewFrame`** in Settings (scheme-preview parity)
- [ ] Soft capability: who may select `developer` (default ADMIN)
- [ ] PHPUnit / Vitest for mode setting + unknown template reject

### Phase 58d — Shortcode engine + Monaco definitions

- [ ] Registry + parser + expand-on-save
- [ ] Built-ins: `section`, `grid`, `callout`, `feature-gallery`
- [ ] Admin shortcode palette in Shortcodes mode
- [ ] **Monaco** UI to create/edit shortcode definitions (schema + expand template)
- [ ] **Every save:** `ShortcodeDefinitionPolicy` + `CodePolicyEngine::validateUntrusted` — reject broken/hostile defs (422)
- [ ] Preview frame reflects shortcode expand
- [ ] Plugin registration hook (It.15) — same gate; no bypass

### Phase 58e — Safe `pg-*` utilities + Monaco snippets

- [ ] CSS pack + attr maps from shortcodes
- [ ] Monaco autocomplete for allow-listed classes only
- [ ] htmlEmbed profile strips non-`pg-*` classes

### Phase 58f — Block outline UI

- [ ] Reorder + width enums; sync rules with AST when switching from Monaco/shortcodes

### Phase 58g — Compile & cache (It.48)

- [ ] Published AST → HTML cache; invalidate on layout/content/appearance change

---

## 9. Future (explicitly **out of It.58**)

Later iterations (themes / modules / plugin authoring):

- Create **plugins, themes, modules** via **Monaco** and/or a **visual editor of defined code blocks**
- Same security baseline: `CodePolicyEngine`, no `data/` access, allow-listed APIs
- May reuse Layout Switch patterns (mode switch + preview + Monaco)

Do **not** block 58c–58g on that workstream.

---

## 10. Decision matrix (score 1–5)

| Criterion | Templates | Shortcodes + Monaco defs | Outline | Pixel grid | Full visual builder |
|-----------|-----------|--------------------------|---------|------------|---------------------|
| Settings-switch UX | **5** | **5** | **5** | 2 | 2 |
| Preview parity with schemes | **5** | **4** | **5** | 3 | 3 |
| Developer comfort (Monaco) | 3 | **5** | 3 | 3 | 2 |
| Security | **5** | **4** | **4** | 2 | 1 |
| Perf / SEO | **5** | **5** | **5** | 3 | 2 |
| **Pick** | 58c | **58d heart** | 58f | Reject | Reject |

---

## 11. Maximum protection (mandatory — core and non-core)

Anything authored **outside CMS core** (plugins, themes, layout shortcodes, Monaco buffers, future module studio) must pass a **fail-closed** gate before activation.

### Layers

| Layer | What | Engine |
|-------|------|--------|
| **1. Syntax** | PHP lint / JSON parse | `SyntaxChecker` |
| **2. Security scan** | Forbidden PHP constructs/functions | `SecurityScanner` + untrusted forbid list |
| **3. Policy** | Size, strict_types, extension namespace | `CodePolicyEngine` |
| **4. Artifact schema** | Shortcode definition JSON | `ShortcodeDefinitionPolicy` |
| **5. Expand allow-list** | No script/iframe; only `pg-*` / public classes in expand HTML | `ShortcodeDefinitionPolicy` |
| **6. Runtime** | Public expand uses AST + sanitizers — never `eval` of user PHP | Shortcode engine (58d) |

### Fail-closed rules

1. **Untrusted paths always validated** — even if Settings `codePolicy.enabled` is false for core Code Editor.  
2. **`validateUntrusted($path, $content)`** — Monaco / import must call this before write (forced untrusted realm).  
3. Untrusted PHP: `EXTENSION_FORBIDDEN` + `declare(strict_types=1)` required.  
4. Shortcode defs are **data + expand templates**, never executable PHP.  
5. Broken / hostile definitions → `CodePolicyViolationException` → **HTTP 422**, artifact not written, registry not updated.  
6. Preview uses the same validators — no “preview-only” escape hatch.

### Path markers (untrusted)

`backend/app/Http/Extensions/`, `themes/`, `data/layout/`, `data/shortcodes/`, `data/plugins/`, virtual `untrusted://…`

### Settings (`codePolicy`)

| Key | Role |
|-----|------|
| `enabled` | Core Code Editor only |
| `strictMode` | Extension namespace rules (default **true**) |
| `maxFileSizeKb` / `untrustedMaxFileSizeKb` | Size caps |
| `forbiddenPhpFunctions` | Base list; untrusted merges include/require/unserialize/call_user_func* |

Future plugin/theme Monaco studio **reuses the same gate** — no parallel weak path.

---

## 12. Open decisions (58c kickoff)

| # | Question | Recommendation |
|---|----------|----------------|
| 1 | Site default vs per-user builder mode? | Both: site default + user override in prefs |
| 2 | SSOT if outline JSON and shortcodes both present? | Follow `builderMode`; document sync on mode switch |
| 3 | Monaco for shortcode defs in Settings or Code Editor route? | Settings → Layout → Shortcodes (Monaco lazy); deep-link from Code Editor optional |
| 4 | Who can enable Developer builder? | ADMIN + optional Developer Mode unlock |

---

## 13. Related

- [ITERATION_58.md](ITERATION_58.md) — implementation plan  
- [ITERATION_65.md](ITERATION_65.md) — `featureGallery`  
- [ITERATION_48.md](ITERATION_48.md) — static compile  
- [ITERATION_15.md](ITERATION_15.md) — plugins  
- Code Editor / Monaco — existing `@monaco-editor/react` patterns  

---

## 14. One-line verdict

**Ship multiple layout builders behind a Settings switch (not role silos), with scheme-like live preview, Monaco for developer/shortcode authoring, shared AST for public HTML — plugin/theme Monaco studios come later.**
