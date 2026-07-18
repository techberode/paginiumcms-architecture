# Iteration 16 – Code Editor Full Stack (Monaco, Themes, Plugins)

**Status:** Partial (Monaco core ✅)  
**Version:** 2.0.21 target for Monaco UI; full stack pending

## Summary

Replace textarea with Monaco in Code Editor, CMS theme editing under `resources/views/themes/`, and create/delete/restore flows gated by code policy and developer unlock.

## Done ✅

- `@monaco-editor/react` in frontend dependencies
- **`MonacoCodeEditor`** — full editor in `CodeEditor.tsx` (replaces textarea), dark/light theme, format + word wrap
- `DeveloperUnlockGate` (Iteration 8 / 14)
- `CodeEditorManager` + policy layer (Iteration 14)
- Allowed paths: `Http/Extensions`, `Modules`, `resources/views/themes`

## Remaining ⏳

| Item | Description |
|------|-------------|
| FileTree | Hierarchical tree aligned with `listFiles` API |
| Create/delete files | API + UI with backup restore |
| Theme editor | Edit PHP/HTML theme files in admin |
| Plugin editor | Edit extension bundles (depends on It. 15) |

## Dependencies

- ✅ Iteration 14 – code policy
- ✅ Iteration 8 – developer unlock UI + Monaco editor
- ⏳ Iteration 15 – plugin runtime

## Related docs

- [ITERATION_14.md](ITERATION_14.md)
- [ITERATION_8.md](ITERATION_8.md)

## Next

→ [Iteration 17](ITERATION_17.md) – API↔FE scaffold law
