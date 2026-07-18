# Iteration 16 – Code Editor Full Stack (Monaco, Themes, Plugins)

**Status:** Complete (core stack) — plugin bundle editor blocked on It. 15  
**Version:** 2.0.22

## Summary

Monaco-based Code Editor with whitelist paths, developer unlock, file tree, create/delete, backup restore, and safety UX.

## Done ✅

| Item | Description |
|------|-------------|
| Monaco editor | `MonacoCodeEditor` — format, word wrap, theme sync |
| Developer gate | TOTP / dev token unlock + **Zamknúť editor** |
| File tree | All allowed roots, hierarchical `FileTree` |
| Save flow | Code policy + syntax check + pre-save backup |
| Create file | `POST /api/admin/code-editor/file` + UI „Nový súbor“ |
| Delete file | `DELETE /api/admin/code-editor/file` + backup before delete |
| Restore backup | `POST /api/admin/code-editor/restore` + UI zoznam záloh |
| Safety UX | Banner, Save confirm, lock confirm |
| Docs | [user/CODE_EDITOR.md](user/CODE_EDITOR.md) |

## Allowed paths (whitelist)

- `backend/app/Modules`
- `backend/app/Http/Extensions`
- `backend/resources/views/themes`
- `backend/config`

## Remaining ⏳ (It. 15 dependency)

| Item | Description |
|------|-------------|
| Plugin bundle editor | Edit extension packages via `PluginManager` (It. 15) |

## API routes

| Method | Path |
|--------|------|
| GET | `/directories`, `/files`, `/file`, `/backups` |
| POST | `/file` (create), `/save`, `/restore` |
| DELETE | `/file` |

## Dependencies

- ✅ Iteration 14 – code policy
- ✅ Iteration 8 – developer unlock UI + Monaco
- ⏳ Iteration 15 – plugin runtime (for plugin editor only)

## Related docs

- [user/CODE_EDITOR.md](user/CODE_EDITOR.md)
- [ITERATION_14.md](ITERATION_14.md)
- [ITERATION_8.md](ITERATION_8.md)

## Next

→ [Iteration 17](ITERATION_17.md) – API↔FE scaffold law
