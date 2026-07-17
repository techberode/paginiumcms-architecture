# Iteration 2 – Auto-Save, Versioning & Conflict Detection

**Status:** Complete  
**Version:** 2.0.6 (core foundation)

## Summary

Optimistic locking via content revisions, auto-save drafts, and version history integrated into the Markdown editor.

## Backend

| Path | Role |
|------|------|
| `Core/FlatFile/Services/ContentRevision.php` | Deterministic revision fingerprint (`sha1` of content + canonical front matter) |
| `Core/FlatFile/Exception/ContentConflictException.php` | HTTP 409 with server version in `conflict` context |
| `Core/Drafts/` | Auto-save draft storage (`data/drafts/{type}/{slug}.json`) |
| `Http/Controllers/Content/DraftController.php` | `PUT/GET/DELETE /api/drafts/{type}/{slug}` |
| `Http/Routes/drafts.php` | Auto-discovered routes |
| `ContentController` | Checks `baseRevision` on save; records commit `message`; bumps `revision` field |
| `Core/Versioning/Services/VersionManager.php` | Fixed `hydrate()` + type cleanup |

### Key parameters

| Parameter | Value |
|-----------|-------|
| Auto-save interval (frontend) | 60 s (configurable via settings from It. 4) |
| Draft storage | `data/drafts/{type}/{slug}.json` |
| Conflict response | HTTP 409 + `conflict.serverContent` / `serverRevision` |

## Frontend

| File | Role |
|------|------|
| `src/api/versions.ts` | Version history, compare, restore |
| `src/api/drafts.ts` | Auto-save draft API |
| `src/hooks/useAutoSave.ts` | Draft save every 60 s when dirty |
| `src/components/versioning/DiffViewer.tsx` | Side-by-side version diff |
| `src/components/backend/MarkdownEditor.tsx` | Lock + auto-save + `baseRevision` + commit message |
| `src/components/CodeEditor/VersionHistory.tsx` | Wired to `DiffViewer` |

## Tests

- `ContentRevision` / draft manager tests
- `DraftController` HTTP tests
- `VersionManagerTest`

## Related docs

- [VERSIONING.md](architecture/VERSIONING.md)
- [CONTENT_API.md](architecture/CONTENT_API.md)

## Next

→ [Iteration 3](ITERATION_3.md) – diff3 merge UI and conflict log
