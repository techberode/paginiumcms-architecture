# Iteration 54 – Modular Markdown & WYSIWYG editor (profiles)

**Status:** ⏳ Planned (implementation **after It.15**)  
**Wave:** Post-15 Editor & UX  
**Priority:** 🔴 High — core content UX

## Summary

Dual editor stack (**Markdown** + **Tiptap WYSIWYG**) driven by **editor profiles**: one-click presets for “Company page”, “Blog”, “Developer docs”, etc. Toolbar and extensions load **only what the profile allows** — no 40-icon clutter.

## Vision (user-facing)

| Profile | Example use | Allowed (example) | Disabled (example) |
|---------|-------------|-------------------|---------------------|
| **Company** | O firme | bold, lists, links | YouTube embed, galleries |
| **Blog** | Novinky | headings, images, tags, checklists | raw HTML |
| **Minimal** | Legal text | plain formatting | media blocks |
| **Developer** | Internal docs | + `code_blocks`, tables | — |

Admin: **one toggle** per page/article (or inherited from content type defaults in Settings).

## Architecture

```
Settings (editor.profiles.*)
       ↓
ContentController / Front matter (editorProfile, editorMode: md|wysiwyg)
       ↓
React EditorShell → lazy-load Tiptap extensions per profile
       ↓
PluginManager (It.15) → optional extension modules (e.g. code_blocks)
```

### Modular extensions (requires It.15)

Advanced users enable features via config, not React rewrites:

```json
{ "code_blocks": true, "youtube": false }
```

PHP merges profile + site overrides → FE receives `EditorCapabilities` DTO. React dynamically imports e.g. `CodeBlockLowlight` only when enabled.

## Backend

| Item | Description |
|------|-------------|
| `SettingsSchema` | `editor.profiles`, default profile per `pages` / `articles` |
| Front matter | `editorProfile`, `editorMode`, optional `editorCapabilities` override |
| Validation | Whitelist node types per profile on save |
| Storage | Markdown body unchanged; WYSIWYG body → see **It.55** (JSON block) |

## Frontend

| Item | Description |
|------|-------------|
| `EditorProfilePicker` | Profile + mode switch in `ContentEditorShell` |
| `MarkdownEditor` | Existing — respect profile (hide disallowed toolbar actions) |
| `WysiwygEditor` | New Tiptap wrapper, extension registry |
| Lazy extensions | `import()` per capability flag |

## Performance principle

Tiptap renders **only enabled nodes** in the toolbar and document schema — smaller bundle per profile via code splitting.

## Dependencies

- ⛔ **[It.15](ITERATION_15.md)** — `PluginManager`, extension bundles, hook registration
- ✅ It.30 content editors baseline
- 🟡 [It.53](ITERATION_53.md) — smooth editor route transitions recommended first

## Out of scope (It.55)

- Tiptap JSON persistence, PHP validation pipeline, image upload hook

## Acceptance criteria

- [ ] At least 3 built-in profiles (Company, Blog, Minimal)
- [ ] Profile switch updates toolbar without full page reload
- [ ] Disallowed node paste/import stripped or rejected with user message
- [ ] PHPUnit: profile validation rejects forbidden block types
- [ ] Vitest: toolbar renders N icons for profile N, not full set

## Next

→ [Iteration 55](ITERATION_55.md) — Tiptap JSON flat-file storage & image upload
