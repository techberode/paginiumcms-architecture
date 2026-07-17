# Iteration 3 – Conflict Resolution

**Status:** Complete  
**Version:** 2.0.6 (core foundation)

## Summary

Three-way merge (diff3) on the client for content conflicts, manual resolution UI, and a flat-file admin conflict log.

## Backend

| Path | Role |
|------|------|
| `Core/Conflict/Models/ConflictRecord.php` | Conflict log entry model |
| `Core/Conflict/Services/ConflictLogger.php` | Flat-file `data/conflicts.json` (max 200, flock-safe) |
| `ContentController` | Logs conflict on HTTP 409 |
| `Http/Controllers/Admin/ConflictController.php` | `GET/DELETE /api/admin/conflicts` |
| `Http/Routes/conflicts.php` | Admin routes |

## Frontend

| File | Role |
|------|------|
| `src/utils/merge3.ts` | Line-based diff3 merge (13 test scenarios) |
| `src/components/versioning/ConflictResolver.tsx` | Modal: Mine / Server / Both / Manual per hunk |
| `src/components/backend/MarkdownEditor.tsx` | Auto-merge on 409; opens resolver if dirty |
| `src/api/conflicts.ts` | Admin conflict log client |

### UX

- Toast on auto-merge (info), conflict (error), cancel (info)
- Dashboard `ConflictsPanel` (Iteration 7) reads same log

## Key parameters

| Parameter | Value |
|-----------|-------|
| Merge algorithm | Line diff3 (LCS anchors) on client |
| Conflict log | `data/conflicts.json`, capped at 200 entries |

## Tests

- `merge3.test.ts` (Vitest) – 13 scenarios
- `ConflictResolver.test.tsx`
- `ConflictController` HTTP tests

## Related docs

- [API_CONTRACT.md](architecture/API_CONTRACT.md) – 409 `conflict` envelope

## Next

→ [Iteration 4](ITERATION_4.md) – settings engine and unified error handler
