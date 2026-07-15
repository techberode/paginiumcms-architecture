# PaginiumCMS – API, Frontend & Feature Checklist

**Last updated:** 2026-07-15 (after Iterations 7 & 14)  
**Version:** 2.0.3  
**Prototype reference:** `screenshots/` (14 PNG mockups from Jun 2026)

This document is the single source of truth for what exists in the backend API, what the React admin SPA actually uses, and how it compares to the first UI prototype.

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

## 1. Backend API inventory (~91 routes)

### Auth – `/api/auth` (14 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| POST | `/register` | ✅ | `RegisterModal` |
| POST | `/login` | ✅ | `LoginModal` |
| POST | `/logout` | ✅ | `ResponsiveLayout` |
| POST | `/reset-password` | ✅ | `ForgotPasswordModal` |
| POST | `/verify-reset-token` | ✅ | `ResetPasswordModal` |
| POST | `/change-password` | ✅ | `ChangePasswordModal` |
| GET | `/me` | ✅ | `AuthContext` |
| GET | `/csrf-token` | ✅ | `api/client.ts` |
| POST | `/2fa/enable` | ✅ | `TwoFactorSettings` |
| POST | `/2fa/disable` | ✅ | `TwoFactorSettings` |
| POST | `/2fa/verify` | ✅ | `TwoFactorSettings` |
| GET | `/2fa/qr-code` | ✅ | `TwoFactorSettings` |
| GET | `/2fa/status` | ✅ | `AuthContext` |
| POST | `/2fa/verify-login` | ✅ | `LoginModal` |

### Content – `/api/pages`, `/api/articles` (12 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| GET | `/api/pages` | ✅ | `PagesManager`, `DashboardView` |
| GET | `/api/pages/{slug}` | ✅ | `MarkdownEditor` |
| POST/PUT/PATCH/DELETE | pages CRUD | ✅ | `MarkdownEditor` |
| GET | `/api/articles` | ✅ | `PagesManager` |
| GET | `/api/articles/{slug}` | ✅ | `MarkdownEditor` |
| POST/PUT/PATCH/DELETE | articles CRUD | ✅ | `MarkdownEditor` |

### Drafts – `/api/drafts` (3 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| GET/PUT/DELETE | `/api/drafts/{type}/{slug}` | ✅ | `useAutoSave`, `MarkdownEditor` |

### Media – `/api/media` (4 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| GET/POST/PATCH/DELETE | `/api/media/*` | ✅ backend | ⛔ no `/media` route in FE |

### Locking – `/api/locks` (5 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| POST | `/acquire`, `/heartbeat`, `/release` | ✅ | `useContentLock` |
| GET/DELETE | admin list + force-release | ✅ | `LocksPanel`, `DashboardView` |

### Validation – `/api/validation` (2 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| GET | `/rules`, `/rules/{context}` | ✅ | `UsersManager`, `utils/validation` |

### Settings (5 routes)

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| GET | `/api/settings/public` | ✅ | `SettingsContext` (toast) |
| GET/PUT/DELETE | `/api/admin/settings/*` | ✅ 🔒 | `SettingsView` |

### Admin – Users (5 routes) 🔒

| CRUD | `/api/admin/users` | ✅ | `UsersManager` |

### Admin – Backups (5 routes) 🔒

| CRUD + restore | `/api/admin/backups` | ✅ | `BackupManager` |

### Admin – Versions (6 routes) 🔒

| History/compare/restore | `/api/admin/versions/*` | ✅ | `DiffViewer` only; `VersionHistory` not routed |

### Admin – Code Editor (4 routes) 🔒 🛠

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| GET | `/files`, `/file`, `/backups` | ✅ | `CodeEditor` |
| POST | `/save` | ✅ + policy 422 | `CodeEditor` |

### Admin – Developer (4 routes) 🔒

| Method | Path | Status | FE wired |
|--------|------|--------|----------|
| GET | `/status` | ✅ | `DeveloperUnlockGate` |
| POST | `/unlock`, `/lock` | ✅ | `DeveloperUnlockGate` |
| GET | `/logs` | ✅ | ⛔ no dedicated FE page |

### Admin – Analytics (3 routes) 🔒

| overview/chart/realtime | `/api/admin/analytics/*` | ✅ | via `DashboardView` overview |

### Admin – Dashboard (1 route) 🔒

| GET | `/overview` | ✅ | `DashboardView` |

### Admin – Health (3 routes) 🔒

| index/checks/{name} | `/api/admin/health/*` | ✅ | via dashboard `HealthPanel` |

### Admin – Notifications (2 routes) 🔒

| overview + test send | `/api/admin/notifications/*` | ✅ | `NotificationsOverview` |

### Admin – Conflicts (2 routes) 🔒

| list + clear | `/api/admin/conflicts` | ✅ | `ConflictsPanel` (display); clear API unused in FE |

### Admin – Audit (5 routes) 🔒

| content/user/stats/export/log | `/api/admin/audit/*` | ✅ | `AuditTrail` (stats + export); sub-routes partial |

### System

| GET | `/`, `/api/health`, `/favicon.ico` | ✅ | public health only |

---

## 2. Frontend route checklist

### Auth routes (unauthenticated)

| Route | Component | Status | Prototype match |
|-------|-----------|--------|-----------------|
| `/login` | `LoginModal` | ✅ | `screenshots/login.png` |
| `/register` | `RegisterModal` | ✅ | — |
| `/forgot-password` | `ForgotPasswordModal` | ✅ | — |
| `/reset-password` | `ResetPasswordModal` | ✅ | — |

### Admin routes (authenticated)

| Route | Component | APIs used | Status | Prototype |
|-------|-----------|-----------|--------|-----------|
| `/dashboard` | `DashboardView` | dashboard, pages, articles, users, backups, audit | ✅ | `dashboard.png` |
| `/pages` | `PagesManager` | pages list/delete | ✅ | part of `administration.png` |
| `/pages/:slug` | `MarkdownEditor` | full content + locks + drafts | ✅ | `edit_article.png` |
| `/articles` | `PagesManager` | articles | ✅ | `paginium_blog.png` (public) |
| `/articles/:slug` | `MarkdownEditor` | articles | ✅ | `versioning_article.png` |
| `/code-editor` | `CodeEditor` + gate | code-editor + developer | ✅ 🟡 UI basic | — |
| `/backups` | `BackupManager` | backups | ✅ | `backup.png` |
| `/audit` | `AuditTrail` | audit stats/export | ✅ | part of `administration.png` |
| `/audit/content/:id` | `AuditTrail` | — | 🟡 no `useParams` | — |
| `/audit/user/:id` | `AuditTrail` | — | 🟡 no `useParams` | — |
| `/notifications` | `NotificationsOverview` | notifications + analytics slice | ✅ | — |
| `/settings` | `SettingsView` | settings + 2FA | ✅ | `system_settings.png`, `seo.png` |
| `/users` | `UsersManager` | users + validation | ✅ | `users.png` |
| `/media` | — | — | ⛔ nav link only | — |
| `/preview/:slug` | — | — | ⛔ linked from PagesManager | public pages in `paginium_*.png` |

---

## 3. Frontend feature matrix

| Feature | Backend | Frontend UI | Tests |
|---------|---------|-------------|-------|
| Session auth + CSRF | ✅ | ✅ | AuthController, Vitest auth flows |
| 2FA TOTP | ✅ | ✅ | TwoFactorController, TwoFactorManager |
| Content CRUD (pages/articles) | ✅ | ✅ | Content tests |
| Auto-save drafts | ✅ | ✅ | drafts API |
| Content locking | ✅ | ✅ | LockManager, locks API |
| 3-way merge / conflicts | ✅ | ✅ | merge3, ConflictResolver |
| Settings engine (schema forms) | ✅ | ✅ | Settings tests |
| User management + RBAC | ✅ | ✅ | UserController |
| Backups CRUD + restore | ✅ | ✅ | BackupControllerTest |
| Version history / diff | ✅ | 🟡 component exists, not routed | VersionManager tests |
| Notifications + SMTP | ✅ | ✅ | NotificationFactoryTest |
| Analytics + realtime | ✅ | ✅ dashboard | RealtimeTrackerTest, AnalyticsControllerTest |
| Dashboard monitoring | ✅ | ✅ | DashboardControllerTest, panel Vitest |
| Health checks | ✅ | ✅ panel | — |
| Code policy + editor | ✅ | ✅ | CodePolicyEngine, SecurityScanner, CodeEditor HTTP |
| Developer unlock gate | ✅ | ✅ | DeveloperControllerTest, DeveloperUnlockGate Vitest |
| Code editor (Monaco) | — | ⛔ textarea only | — |
| WYSIWYG (TipTap) | — | ⛔ orphan component | — |
| Media manager | ✅ API | ⛔ no view | — |
| Public site theme | backend views | ⛔ separate from admin SPA | `paginium_home.png` etc. |
| SEO admin UI | settings groups | 🟡 in SettingsView | `seo_site.png` |

---

## 4. API client modules (`frontend/src/api/`)

| Module | Used in UI | Notes |
|--------|------------|-------|
| `client.ts` | ✅ all | Axios + CSRF |
| `auth.ts` | ✅ | Full auth surface |
| `dashboard.ts` | ✅ | Iteration 7 |
| `analytics.ts` | 🟡 | Via dashboard only |
| `notifications.ts` | ✅ | |
| `settings.ts` | ✅ | |
| `users.ts` | ✅ | |
| `validation.ts` | ✅ | |
| `drafts.ts` | ✅ | |
| `locks.ts` | ✅ | |
| `developer.ts` | ✅ | Iteration 14 |
| `conflicts.ts` | 🟡 | Types only in panel |
| `health.ts` | 🟡 | Via dashboard |
| `codeEditor.ts` | ⛔ | CodeEditor uses raw `useApi` |
| `backup.ts` | ⛔ | BackupManager uses raw `useApi` |
| `audit.ts` | ⛔ | AuditTrail uses raw `useApi` |
| `version.ts` / `versions.ts` | 🟡 | DiffViewer only |
| `index.ts` | ⛔ | Broken barrel imports |

---

## 5. Prototype vs current UI (`screenshots/`)

| Screenshot | Target in prototype | Current SPA status |
|------------|---------------------|-------------------|
| `login.png` | Login screen | ✅ `LoginModal` – layout may differ |
| `dashboard.png` | Admin dashboard | ✅ rebuilt with charts + panels |
| `administration.png` | Admin hub | 🟡 split across routes |
| `users.png` | User management | ✅ `UsersManager` |
| `backup.png` | Backup manager | ✅ `BackupManager` |
| `system_settings.png` | System settings | ✅ schema-driven `SettingsView` |
| `seo.png` / `seo_site.png` | SEO settings | 🟡 settings groups, no dedicated page |
| `edit_article.png` | Article editor | ✅ `MarkdownEditor` (not WYSIWYG) |
| `versioning_article.png` | Version sidebar | 🟡 `VersionHistory` not mounted |
| `paginium_home.png` | Public homepage | ⛔ public theme, not admin SPA |
| `paginium_about.png` | Public about | ⛔ |
| `paginium_blog.png` | Public blog | ⛔ |
| `paginium_contact.png` | Public contact | ⛔ |

| Prototype CSS ported | `paginiumcms1/src/index.css` → `frontend/src/index.css` | prose, TipTap, fonts, animations |

---

## 6. Test coverage (new modules – Iterations 7 & 14)

### PHPUnit

| Test | Module |
|------|--------|
| `RealtimeTrackerTest` | Analytics realtime |
| `DashboardControllerTest` | Dashboard overview API |
| `AnalyticsControllerTest` | Realtime endpoint |
| `CodePolicyEngineTest` | Policy engine |
| `SecurityScannerTest` | PHP security scan |
| `SyntaxCheckerTest` | Syntax validation |
| `CodeEditorManagerTest` | Path resolution + listing |
| `CodeEditorControllerTest` | HTTP gate + 422 policy |
| `DeveloperControllerTest` | Status + token unlock |

### Vitest

| Test | Module |
|------|--------|
| `AnalyticsChart.test.tsx` | Dashboard chart |
| `HealthPanel.test.tsx` | Health widget |
| `LocksPanel.test.tsx` | Locks widget |
| `DeveloperUnlockGate.test.tsx` | Dev unlock UI |

---

## 7. Known gaps (next iterations)

1. **Iteration 8:** Media manager FE, WYSIWYG, Monaco, prototype CSS alignment
2. **Iteration 15:** PluginManager on `Http/Extensions`
3. **Audit sub-routes:** wire `useParams` in `AuditTrail`
4. **Version UI:** mount `VersionHistory` in editor sidebar
5. **API cleanup:** use typed modules instead of raw `useApi`; fix `api/index.ts`
6. **Public site:** theme rendering from `backend/resources/views/themes/`

---

## 8. Quick verification commands

```bash
# Backend (from repo root)
./vendor/bin/phpunit

# Frontend
cd frontend && npm test
```

Expected after Iteration 14 test pass: **PHPUnit 405**, **Vitest 37**.
