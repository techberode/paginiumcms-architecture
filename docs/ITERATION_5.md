# Iteration 5 – Users & Authentication Hardening

**Status:** Complete  
**Version:** 2.0.6 (core foundation)

## Summary

Admin user CRUD with role assignment, enforced 2FA on admin routes, and session-based auth (HttpOnly cookies, no Bearer token in frontend).

## Backend

| Feature | Implementation |
|---------|----------------|
| Admin user CRUD | `Http/Controllers/Admin/UserController.php` |
| Role assignment | `USER`, `EDITOR`, `ADMIN`, `SUPER_ADMIN` via `UserRepository` |
| 2FA enforcement | `TwoFactorMiddleware` on `/api/admin/*` |
| Session auth | `SessionManager`, `AuthenticationManager` |
| CSRF | `CsrfProtectionManager` + `X-CSRF-TOKEN` header |
| Password policy | `PasswordPolicy` + shared validation rules |

### Auth API (legacy flat envelope)

Login/register responses put `user` at root level — documented in [API_CONTRACT.md](architecture/API_CONTRACT.md) §2.6.

## Frontend

| File | Role |
|------|------|
| `src/api/auth.ts` | Login, register, logout, 2FA, password reset |
| `src/api/users.ts` | Admin user CRUD |
| `src/components/backend/UsersManager.tsx` | User management UI (2.0.18 refresh) |
| `src/context/AuthContext.tsx` | Session state |
| `src/api/client.ts` | `withCredentials: true`; Bearer code removed |

### Admin user form (2.0.18)

| Field | API field | Notes |
|-------|-----------|-------|
| Používateľské meno | `username` | Unique slug, auto-derived from e-mail |
| Zobrazované meno | `name` | Display name |
| E-mail | `email` | Login identifier |
| Heslo | `password` | Required on create; optional on edit |
| Potvrdenie hesla | `passwordConfirm` | Required when `password` is set (create or edit) |
| Rola | `role` | USER / EDITOR / ADMIN / SUPER_ADMIN |
| Stav účtu | `active` | Inactive users cannot log in |
| 2FA | `twoFactorEnabled` | Locked when `security.requireTwoFactorStaff` applies to staff roles |
| 2FA Secret | `twoFactorSecret` | Returned on `GET /api/admin/users/{id}` only |

Settings: **Bezpečnosť → Vynútiť 2FA pre editorov a adminov** (`requireTwoFactorStaff`).

### Password confirmation (2.0.56)

| Flow | API | FE |
|------|-----|-----|
| Registrácia | `POST /api/auth/register` — `password` + `passwordConfirm` (alias `password_confirm`) | `RegisterModal` — druhé pole, `validatePasswordConfirmation()` |
| Admin create user | `POST /api/admin/users` | `UsersManager` — povinné obe polia |
| Admin edit password | `PUT /api/admin/users/{id}` — len ak sa mení heslo | Potvrdenie sa zobrazí pri vyplnenom hesle |

Backend: `ValidationRules::validatePasswordConfirmation()` pred password policy → HTTP **422** s `errors.passwordConfirm`.  
Frontend: zdieľaná utilita `validatePasswordConfirmation()` + i18n `passwordMismatch` / `passwordConfirmRequired`.

## Tests

- `AuthControllerTest`, `TwoFactorControllerTest`
- `UserControllerTest`
- `UserRepositoryTest`

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 5

## Next

→ [Iteration 6](ITERATION_6.md) – notifications, analytics, auth UI polish
