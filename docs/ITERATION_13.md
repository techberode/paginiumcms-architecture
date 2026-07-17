# Iteration 13 – Demo Module (Isolated Mock Data)

**Status:** Planned  
**Version:** —

## Summary

Isolated demo environment with `DEMO_MODE` and separate storage path so training/sandbox never touches production content.

## Goals

| Deliverable | Description |
|-------------|-------------|
| `DEMO_MODE` | Env flag switches base path + read-only guards |
| Separate storage | e.g. `storage/app/demo/` or vfs overlay |
| Demo data provider | `Modules/Demo/` – seed pages, articles, media |
| Reset API | Admin `POST /api/admin/demo/reset` (SUPER_ADMIN only) |
| UI banner | “Demo mode” indicator in admin shell |

## Existing code

- `Modules/Demo/Contracts/DemoDataProviderInterface.php` – scaffold present
- Extend with full provider + route isolation

## Dependencies

- ✅ Iteration 19 – content repository abstraction
- ⏳ Iteration 15 – plugin paths (optional demo extensions)

## Tests (planned)

- Demo writes never appear in production path
- Reset restores seed snapshot

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 13

## Next

→ [Iteration 14](ITERATION_14.md) – Code policy (already documented)
