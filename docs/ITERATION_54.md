# Iteration 54 – Modular Markdown & WYSIWYG editor (profiles)

**Status:** ✅ Complete  
**Version:** **2.0.41**

## Summary

Dual editor stack (Markdown + Tiptap WYSIWYG) driven by **editor profiles**: presets for company pages, blog articles, minimal legal text, and full developer mode. Toolbar and Tiptap extensions load only what the profile allows.

## Deliverables

| Area | Change | Status |
|------|--------|--------|
| Backend | `EditorProfileService`, `EditorContentValidator`, built-in profiles | ✅ |
| Settings | `defaultProfilePage`, `defaultProfileArticle`; public API `editor.profiles` | ✅ |
| Front matter | `editorProfile`, `editorMode` persisted on save | ✅ |
| Frontend | `editorProfiles.ts`, `EditorProfilePicker` | ✅ |
| Markdown | Toolbar gated by profile capabilities | ✅ |
| WYSIWYG | Dynamic Tiptap extensions + paste guard | ✅ |
| Validation | BE rejects disallowed blocks on save (400) | ✅ |
| Tests | PHPUnit + Vitest profile/toolbar tests | ✅ |

## Built-in profiles

| Profile | Use case | Allowed (example) |
|---------|----------|---------------------|
| **company** | O firme | bold, headings, lists, links |
| **blog** | Novinky | + images, blockquote, inline code |
| **minimal** | Právne texty | bold, italic, links only |
| **developer** | Internal docs | full toolbar incl. tables & code blocks |

## Acceptance criteria

- [x] At least 3 built-in profiles (Company, Blog, Minimal) + Developer
- [x] Profile switch updates toolbar without full page reload
- [x] Disallowed paste/import blocked with toast message (FE) + 400 on save (BE)
- [x] PHPUnit: profile validation rejects forbidden block types
- [x] Vitest: toolbar renders fewer icons for minimal vs developer
- [x] `./scripts/iteration-gate.sh` green

## Related

- [ITERATION_15.md](ITERATION_15.md) — plugin extension hooks (future profile modules)
- [ITERATION_53.md](ITERATION_53.md) — SPA navigation baseline

## Next

→ [Iteration 55](ITERATION_55.md) — Tiptap JSON flat-file storage & image upload
