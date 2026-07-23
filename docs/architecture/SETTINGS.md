# PaginiumCMS – Settings, Validation & Notifications

> Flat-file settings engine, unified JSON error handling, shared FE↔BE validation, and Iteration 6 notification/analytics settings.

---

## 1. Settings engine

### Storage

- File: `backend/storage/app/content/data/settings.json`
- Only **overrides** from `SettingsSchema` defaults are persisted
- Concurrency: `flock(LOCK_EX)` in `SettingsRepository`
- **PHPUnit / HTTP integračné testy** (`APP_ENV=testing`): používajú **`data/settings.testing.json`** — produkčný `settings.json` (SMTP, heslá, …) sa počas testov nečíta ani nezapisuje. Pozri `docs/developer/TESTING.md`.

### Endpoints

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/settings/public` | Anonymous / logged-in | Public slice: `general`, `branding`, `content`, `editor`, `maintenance`, `notifications`, … |
| `GET` | `/api/admin/settings` | ADMIN | Schema + values; `accessControl` group + `meta.permissions` only for SUPER_ADMIN |
| `GET` | `/api/admin/settings/{group}` | ADMIN | One group; `accessControl` → 403 unless SUPER_ADMIN |
| `PUT` | `/api/admin/settings/{group}` | ADMIN | Validate + save; `accessControl` → 403 unless SUPER_ADMIN; reloads RBAC + syncs Path ACL |
| `DELETE` | `/api/admin/settings` | ADMIN | Reset all groups to defaults |

### Schema groups (výber)

| Group | Purpose | Who can edit |
|---|---|---|
| `general` | siteName, siteUrl, adminEmail, language, timezone, timezoneDst, allowRegistration | ADMIN+ |
| `branding` | logoUrl, faviconUrl | ADMIN+ |
| `accessControl` | permissionsAdmin/Editor/User, pathAclEnabled, pathAclRulesJson | **SUPER_ADMIN only** |
| `content` | itemsPerPage, blogItemsPerPage, defaultStatus, autoSaveInterval, lockTtl | ADMIN+ |
| `maintenance` | Coming Soon / Under Maintenance modes and templates | ADMIN+ |
| `editor` | defaultEditor, spellcheck, tabSize | ADMIN+ |
| `smtp` | SMTP transport | ADMIN+ |
| `notifications` | Toast UI | ADMIN+ |
| `connectors` | Email, ntfy, Discord, Telegram, webhook | ADMIN+ |
| `monitoring` | Incident alerts, scheduled reports | ADMIN+ |
| `security` | Password policy, 2FA staff requirement | ADMIN+ |
| `firewall` | WAF settings slice | ADMIN+ |
| `logging` | Log level, retention, auth endpoint logging | ADMIN+ |

### Frontend

| File | Role |
|---|---|
| `api/settings.ts` | Typed admin + public settings API |
| `components/backend/SettingsView.tsx` | Schema-driven admin form (supports `password` fields); deep link `?group=` |
| `utils/adminDeepLinks.ts` | Shareable admin paths (`settingsGroupPath`, …) |
| `context/SettingsContext.tsx` | Global effective settings |
| `context/NotificationContext.tsx` | Toast UI driven by `notifications` public settings |
| `hooks/useToast.ts` | Shortcut for toast helpers |

Deep links: [ADMIN_DEEP_LINKS.md](./ADMIN_DEEP_LINKS.md) (`/settings?group=logging`, `/settings?category=security&group=accessControl`, …).

User guides: [BRANDING.md](../user/BRANDING.md), [ACCESS_CONTROL.md](../user/ACCESS_CONTROL.md).

---

## 2. Notifications & analytics (Iteration 6)

### Admin API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/admin/notifications/overview` | Active connectors (with auth status), fallback email, visit stats, top pages |
| `POST` | `/api/admin/notifications/test` | Send test message via one enabled adapter |
| `POST` | `/api/admin/notifications/test-connector` | Validate connector credentials + optional delivery test (It.47) |
| `GET` | `/api/admin/analytics/overview` | Visits, page views, referers, devices (`?period=today`) |
| `GET` | `/api/admin/analytics/chart` | Daily chart (`?days=30`) |

### Backend services

- `NotificationFactory` – builds `NotificationService` from settings
- `SmtpTransport` – lightweight SMTP client (TLS + AUTH LOGIN)
- Adapters: `EmailAdapter`, `NtfyAdapter`, `DiscordAdapter`, `TelegramAdapter`, `WebhookAdapter`
- `IncidentNotifier` – multi-channel alerts for auth failures, audit security events, traffic spikes
- `AnalyticsMiddleware` – tracks non-API page views
- `Reporter` / `AnalyticsManager` – flat-file analytics reports

### Frontend

| File | Role |
|---|---|
| `api/notifications.ts` | Notification overview + test send |
| `api/analytics.ts` | Analytics reports |
| `components/backend/NotificationsOverview.tsx` | Admin dashboard for connectors + visits |

---

## 3. Auth flows (Iteration 6)

| Flow | API | Frontend |
|---|---|---|
| Login | `POST /api/auth/login` | `LoginModal` |
| Register | `POST /api/auth/register` | `RegisterModal` (`/register`) |
| Forgot password | `POST /api/auth/reset-password` | `ForgotPasswordModal` – sends email when SMTP configured |
| Reset password | `POST /api/auth/verify-reset-token` | `ResetPasswordModal` (`/reset-password?token=…`) |
| Change password | `POST /api/auth/change-password` | `ChangePasswordModal` in sidebar |

Password reset no longer returns a demo token in production. Token is only included when `APP_ENV` is `development` or `testing` and SMTP is not configured.

---

## 4. Unified error handler

Registered in `bootstrap/app.php` via `ApiErrorHandler`.

| Exception | HTTP | JSON body |
|---|---|---|
| `ValidationException` | 422 | `{ success: false, error, errors }` |
| Slim `HttpException` | exception code | `{ success: false, error }` |
| other | 500 | `{ success: false, error }` |

---

## 5. Shared validation

- Backend: `Core/Validation/Validator.php`, `ValidationRules.php`
- Frontend: `utils/validation.ts`, `validatePasswordPolicy()`
- Endpoint: `GET /api/validation/rules[/{context}]`

---

## 6. Tests

- Backend: `SettingsRepositoryTest`, `NotificationFactoryTest`, `IncidentNotifierTest`, `AuthControllerTest`
- Frontend: `notificationSettings.test.ts`, `validation.test.ts`

Run:

```bash
cd backend && ./vendor/bin/phpunit
cd frontend && npm test
```
