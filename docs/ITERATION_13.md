# Iteration 13 – Demo Module (Isolated Mock Data)

**Status:** ✅ Complete (Unreleased)  
**Version:** —

## Summary

Isolated demo environment with `DEMO_MODE` and separate storage path so training/sandbox never touches production content.

## Delivered

| Deliverable | Status |
|-------------|--------|
| `DEMO_MODE` env flag | ✅ |
| `DemoMode` + `DemoStorageService` | ✅ |
| Separate storage `storage/app/demo/` | ✅ |
| Seed pages + articles (`DemoFixtures::seedFiles()`) | ✅ |
| MOCK comments/messages/newsletter via `DemoDataProvider` | ✅ |
| `GET /api/admin/demo/status` | ✅ |
| `POST /api/admin/demo/reset` (SUPER_ADMIN only) | ✅ |
| Public settings `demo.enabled` | ✅ |
| Admin banner + `/demo` manager UI | ✅ |
| PHPUnit isolation + controller smoke | ✅ |

## Backend

```
Modules/Demo/Services/DemoMode.php
Modules/Demo/Services/DemoStorageService.php
Modules/Demo/Services/DemoDataProvider.php
Modules/Demo/Data/DemoFixtures.php
Http/Controllers/Admin/DemoController.php
Http/Routes/demo.php
```

| Route | Auth | Notes |
|-------|------|-------|
| `GET /api/admin/demo/status` | ADMIN + 2FA | enabled, paths, file_count |
| `POST /api/admin/demo/reset` | SUPER_ADMIN + 2FA | Re-seed demo files |

**Activation:** `export DEMO_MODE=true` before starting PHP.

## Frontend

- `frontend/src/api/demo.ts`
- `DemoModeBanner` in admin shell (when `demo.enabled`)
- `DemoManager` at `/demo` — status + reset

## Tests

| Suite | File |
|-------|------|
| PHPUnit | `DemoStorageServiceTest` — demo writes never touch content path |
| PHPUnit | `DemoControllerTest`, `DemoDataProviderTest` |

## Dependencies (met)

- ✅ Iteration 19 – content repository abstraction

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 13
- [Modules/Demo/README.md](../backend/app/Modules/Demo/README.md)

## Next

→ [Iteration 14](ITERATION_14.md) – Code policy engine
