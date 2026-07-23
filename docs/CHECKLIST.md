# PaginiumCMS – API, Frontend & Feature Checklist

**Last updated:** 2026-07-19 (release **2.0.26**)  
**Version:** 2.0.26  
**Architecture docs:** [architecture/](architecture/) · CI incidents: [ISSUES.md](ISSUES.md) ISS-015–022

Single source of truth: čo existuje v backend API, čo React admin SPA používa, stav oproti pôvodnému prototypu (`screenshots/`).

---

## Legend

| Symbol | Meaning |
|--------|---------|
| ✅ | Done and wired end-to-end |
| 🟡 | Partial – backend or UI exists, not fully connected |
| ⛔ | Missing or stub only |
| 🔒 | Requires admin + 2FA (when enabled) |
| 🛠 | Requires Developer Mode unlock |

---

## 1. Backend API inventory (~30 route files)

Auth routes sú **inline** v `backend/bootstrap/app.php` (`/api/auth/*`). Ostatné v `backend/app/Http/Routes/*.php`.

### Auth – `/api/auth` (~18 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| POST | `/register`, `/register/verify-otp`, `/register/resend-otp` | ✅ | `RegisterModal` |
| POST | `/login` | ✅ | `LoginModal` |
| POST | `/logout` | ✅ | layout |
| POST | `/reset-password`, `/verify-reset-token` | ✅ | password modals |
| POST | `/change-password` | ✅ | `ChangePasswordModal` |
| GET | `/me` | ✅ | `AuthContext` |
| GET | `/csrf-token` | ✅ | `api/client.ts` |
| POST | `/2fa/*` | ✅ | `TwoFactorSettings`, login |
| GET | `/2fa/status`, `/2fa/qr-code` | ✅ | `AuthContext` |

### Content – `/api/pages`, `/api/articles`

| CRUD + search + publish | ✅ | `PagesManager`, `MarkdownEditor` |
| OTP publish workflow | ✅ | `workflows.ts` |

Pozri [architecture/CONTENT_API.md](architecture/CONTENT_API.md).

### Media – `/api/media` (~14 routes)

| List/upload/folders/stock/bulk | ✅ | `MediaManager` (`/media`) |

### Admin – nové / rozšírené (2.0.25–2.0.26)

| Area | Routes | FE |
|------|--------|-----|
| Trash | `/api/admin/trash/*` | ✅ `TrashManager` |
| Firewall | `/api/admin/firewall/*` | ✅ `FirewallManager` |
| Logs | `/api/admin/logs/*` | ✅ `LogsManager` (bulk, pagination, delete-all) |
| Counts | `/api/admin/counts` | ✅ sidebar badges |
| Messages | `/api/admin/messages/*` + bulk | ✅ `MessagesViewer` |
| Comments mod | `/api/admin/comments/*` + bulk | ✅ `CommentsManager` |
| Navigation | `/api/admin/navigation` | ✅ `NavigationManager` |
| Jobs / scheduler | `/api/admin/jobs/*` | ✅ `SchedulerView` |
| Workflows OTP | `/api/workflows/otp/*` | ✅ publish / comments |
| GitHub sync | `/api/admin/github/*` | ✅ `GitHubSyncPanel` |
| Feeds / SEO | `/api/feeds/*`, `/api/seo/*` | ✅ public site |

### Core admin (existujúce)

| Area | Status | FE |
|------|--------|-----|
| Settings | ✅ 🔒 | `SettingsView` |
| Users | ✅ 🔒 | `UsersManager` |
| Backups | ✅ 🔒 | `BackupManager` |
| Versions | ✅ 🔒 | `VersionHistory` v editore |
| Code editor | ✅ 🔒 🛠 | `CodeEditor` |
| Developer | ✅ 🔒 | unlock gate + `/developer/logs` |
| Audit | ✅ 🔒 | `AuditTrail` |
| Analytics / Dashboard | ✅ 🔒 | `DashboardView` |
| Health | ✅ 🔒 | `HealthPanel` |
| Notifications | ✅ 🔒 | `NotificationsOverview` |
| Conflicts | ✅ 🔒 | `ConflictsPanel` |
| Drafts / Locks | ✅ | editor |

Kompletný zoznam: [architecture/API.md](architecture/API.md).

---

## 2. Frontend route checklist

### Auth (unauthenticated)

| Route | Component | Status |
|-------|-----------|--------|
| `/login` | `LoginModal` | ✅ |
| `/register` | `RegisterModal` | ✅ |
| `/forgot-password`, `/reset-password` | modals | ✅ |

### Admin (authenticated + role guard)

| Route | Component | Status |
|-------|-----------|--------|
| `/dashboard` | `DashboardView` | ✅ |
| `/pages`, `/pages/:slug` | `PagesManager`, `MarkdownEditor` | ✅ |
| `/articles`, `/articles/:slug` | same | ✅ |
| `/media` | `MediaManager` | ✅ |
| `/navigation` | `NavigationManager` | ✅ |
| `/comments` | `CommentsManager` | ✅ |
| `/messages` | `MessagesViewer` | ✅ |
| `/github` | `GitHubSyncPanel` | ✅ |
| `/code-editor` | `CodeEditor` + dev gate | ✅ |
| `/backups` | `BackupManager` | ✅ |
| `/trash` | `TrashManager` | ✅ |
| `/firewall` | `FirewallManager` | ✅ |
| `/logs` | `LogsManager` | ✅ |
| `/audit`, `/audit/content/:id`, `/audit/user/:id` | `AuditTrail` | ✅ |
| `/notifications` | `NotificationsOverview` | ✅ |
| `/scheduler` | `SchedulerView` | ✅ |
| `/settings` | `SettingsView` | ✅ |
| `/account/security` | `AccountSecurityView` | ✅ |
| `/users` | `UsersManager` | ✅ |
| `/developer/logs` | `DeveloperLogsViewer` | ✅ |

### Public site (same SPA)

| Route | Component | Status |
|-------|-----------|--------|
| `/` | homepage | ✅ `PublicSiteContext` |
| `/:slug` | `PublicSlugPage` | ✅ |
| `/blog`, `/blog/:slug` | `BlogRenderer` | ✅ |

---

## 3. Frontend feature matrix

| Feature | Backend | Frontend UI | Tests |
|---------|---------|-------------|-------|
| Session auth + CSRF | ✅ | ✅ | AuthController, Vitest |
| 2FA TOTP | ✅ | ✅ | TwoFactorController |
| Content CRUD + SEO | ✅ | ✅ | ContentControllerTest |
| Auto-save drafts | ✅ | ✅ | DraftManager |
| Content locking | ✅ | ✅ | LockManager |
| 3-way merge / conflicts | ✅ | ✅ | ConflictResolver |
| Settings (schema forms) | ✅ | ✅ | SettingsRepository |
| User management + RBAC | ✅ | ✅ | UserController |
| Backups + restore | ✅ | ✅ | BackupControllerTest |
| Version history / diff | ✅ | ✅ in editor | VersionManager |
| Media DAM + stock | ✅ | ✅ | MediaController, MediaManager.test |
| Trash / soft-delete | ✅ | ✅ | TrashController |
| WAF (It.50) | ✅ | ✅ `/firewall` | Firewall*Test |
| HTTP + app logging | ✅ | ✅ `/logs` | LogControllerTest |
| Notifications (SMTP, ntfy) | ✅ | ✅ | NtfyAdapterTest |
| Analytics + dashboard | ✅ | ✅ | DashboardControllerTest |
| OTP workflow | ✅ | ✅ | OtpWorkflowServiceTest |
| Code policy + editor | ✅ | ✅ 🛠 | CodeEditorControllerTest |
| Public theme rendering | ✅ API | ✅ React public routes | Vitest public components |
| PluginManager | ✅ | ✅ | It.15 — 2.0.38 |

---

## 4. API client modules (`frontend/src/api/`)

| Module | Used in UI | Notes |
|--------|------------|-------|
| `client.ts` | ✅ all | Axios + CSRF + 422/409 |
| `auth.ts` | ✅ | |
| `content.ts`, `drafts.ts`, `locks.ts` | ✅ | editor |
| `media.ts` | ✅ | `MediaManager` |
| `settings.ts` | ✅ | |
| `users.ts`, `validation.ts` | ✅ | |
| `firewall.ts` | ✅ | It.50 |
| `logs.ts` | ✅ | 2.0.26 |
| `workflows.ts` | ✅ | OTP |
| `dashboard.ts`, `analytics.ts` | ✅ | |
| `notifications.ts` | ✅ | |
| `developer.ts` | ✅ | |
| `conflicts.ts`, `health.ts` | 🟡 | dashboard panels |
| `codeEditor.ts`, `backup.ts`, `audit.ts` | 🟡 | some screens use raw `useApi` |

---

## 5. Middleware stack (backend)

Poradie v `bootstrap/app.php`: CORS → Security → Maintenance → Locale → **Firewall** → RateLimit → Analytics → routes → **RequestLogging**.

Detail: [architecture/BACKEND.md](architecture/BACKEND.md), [architecture/CORE_HARDENING.md](architecture/CORE_HARDENING.md).

---

## 6. Test coverage (CI baseline)

| Suite | Počet | Príkaz |
|-------|-------|--------|
| PHPUnit | **599** (15 skipped) | `./vendor/bin/phpunit` |
| PHPStan L8 | 0 errors | `./vendor/bin/phpstan analyse backend --level=8` |
| Vitest | **135** (36 files) | `cd frontend && npm test` |
| ESLint | 57 warnings (limit 65) | `npm run lint` |
| Type-check | strict | `npm run type-check` |

CI: `.github/workflows/ci.yml`. Posledné opravy: ISS-020 (ESLint), ISS-021 (PHPStan), ISS-022 (MediaManager Vitest).

---

## 7. Known gaps (backlog)

1. **PluginManager** on `Http/Extensions` (It.15)
2. **CSRF globálne middleware** — odložené (ISS-012); SameSite=Lax dnes
3. **API cleanup** — jednotné typed moduly namiesto raw `useApi` v niektorých screenoch
4. **ESLint tech debt** — 57/65 warnings (`no-explicit-any`, ISS-011)
5. **HTTPS v produkcii** — heslo polia varovanie (ISS-008)
6. **Postman/Newman** smoke v CI — voliteľné (`docs/api/`)

---

## 8. Quick verification

```bash
# Backend (repo root)
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit

# Frontend
cd frontend && npm run lint && npm run type-check && npm test
```

---

## Súvisiace dokumenty

- [architecture/API.md](architecture/API.md) · [API_CONTRACT.md](architecture/API_CONTRACT.md)
- [architecture/BACKEND.md](architecture/BACKEND.md) · [CORE.md](architecture/CORE.md)
- [user/FIREWALL.md](user/FIREWALL.md) · [user/LOGGING.md](user/LOGGING.md)
- [developer/TESTING.md](developer/TESTING.md)
- [CHANGELOG.md](../CHANGELOG.md) — 2.0.26
