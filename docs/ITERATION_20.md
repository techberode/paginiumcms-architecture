# Iteration 20 – Core Hardening & Production Readiness

**Status:** Mostly complete  
**Version:** 2.0.8

## Summary

Production security and operations: RBAC on mutations, public media serving, maintenance mode, trash restore, backup cron, and frontend preview/role guard.

## Backend – done ✅

| Feature | Implementation |
|---------|----------------|
| RBAC | `PermissionMiddleware` on content/media writes |
| `:manage` alias | `AuthorizationManager` – ADMIN covers domain permissions |
| Media serving | `GET /storage/{path}` + Vite/nginx proxy |
| Maintenance | `MaintenanceModeMiddleware` + `general.maintenanceMode` |
| Registration toggle | `general.allowRegistration` → 403 on register |
| Guest comments | `comments.allowGuestComments` enforced |
| Session fixation | `session_regenerate_id()` in `SessionManager::setUser()` |
| Trash API | `GET /api/admin/trash`, `POST /api/admin/trash/{id}/restore` |
| Soft-delete meta | `.meta.json` sidecar on trash move |
| Backup cron | `bin/console backup:run-schedule` + `BackupScheduler` |

## Frontend – done ✅

| Feature | Route / component |
|---------|-------------------|
| Unpublished preview | `/preview/:slug` |
| Role guard | `AdminRoleGuard` – USER → public site |
| Document title | Public site from page title + site name |
| Version history in editor | `VersionHistory` in `MarkdownEditor` |
| Developer logs | `/developer/logs` → `DeveloperLogsViewer` |

## Remaining ⏳

- Brute-force lockout per email/IP (`SecurityLogger` extension)
- Trash admin UI in React
- (Done in 2.0.8 patch) Full HTTP tests for trash restore

## Tests (2.0.8)

- `CoreHardeningTest`, `PermissionMiddlewareTest`, `MaintenanceModeMiddlewareTest`
- `TrashServiceTest`, `TrashControllerTest`, `StorageControllerTest`
- `BackupSchedulerTest`, `BackupManagerTest`, `CommentsControllerTest`
- `AuthorizationManagerManagePermissionTest`, `FileWriterTest` (meta sidecar)

## Related docs

- [CORE_HARDENING.md](architecture/CORE_HARDENING.md) – detailed reference
- [CHANGELOG.md](../CHANGELOG.md) – [2.0.8]

## Next

→ [Iteration 21](ITERATION_21.md) – API contract & automated testing
