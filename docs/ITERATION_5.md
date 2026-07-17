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
| `src/components/backend/UsersManager.tsx` | User management UI |
| `src/context/AuthContext.tsx` | Session state |
| `src/api/client.ts` | `withCredentials: true`; Bearer code removed |

## Tests

- `AuthControllerTest`, `TwoFactorControllerTest`
- `UserControllerTest`
- `UserRepositoryTest`

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 5

## Next

→ [Iteration 6](ITERATION_6.md) – notifications, analytics, auth UI polish
