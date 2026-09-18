# Admin toast coverage audit (Iteration 94)

Audit date: 2026-09-18. Existing stack: `NotificationProvider` + `useToast()` (no second library).

## Always toast on success/error (core paths)

| Area | Module | Mutations covered |
|------|--------|-------------------|
| Content | `PagesManager`, article/page editors | save, delete, bulk, publish |
| Settings | `SettingsView`, group panels | save, validation errors via toast + `FieldError` |
| Media | `MediaManager` | upload, delete, bulk, optimize, download (It.96) |
| Users | `UsersManager` | CRUD, bulk, GDPR anonymize |
| System | `SystemUpdateView`, banner | deploy outcome, check failures |

## Confirmed after It.94 (destructive + feedback)

All listed managers now use accessible confirm modals (`useAdminConfirm` / `confirmDialog`); destructive flows still call `toast.success` / `toast.error` on API result.

## Known gaps (non-blocking, backlog)

| Module | Gap | Notes |
|--------|-----|-------|
| `ChangePasswordModal` | was English-only strings | Fixed It.94 — i18n + inline `FieldError` |
| `CodeEditor` / `VersionHistory` | mixed SK/EN developer copy | Confirm modal unified; full i18n → future It.95+ |
| Read-only views | e.g. analytics load | error toast on fetch fail only |
| Silent success | some toggle-only settings sub-panels | rely on Settings save bar |

## Rules for new modules

- Mutating actions: `toast.success` / `toast.error` (never log secrets).
- Destructive: `useAdminConfirm()` or `confirmDialog()` — not `window.confirm`.
- Validation: prefer `FieldError` + optional toast for submit blocked.
