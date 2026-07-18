# 🏛️ PaginiumCMS

> **Version:** 2.0.9  
> **Last updated:** 17 July 2026  
> Modern, modular, Headless Flat-File Content Management System powered by Slim Framework (PHP) & React.

---

## 🎯 Vision & Philosophy

PaginiumCMS keeps the Core intentionally minimal, secure, and fast. It moves standard features into standalone modules, putting developer experience and content ownership first.

* **Simplicity First:** Features must deliver high value without adding unnecessary complexity.
* **Flat-File First:** Content belongs to files (`.md`, `.json`). Databases are optional.
* **API First:** Every admin action is accessible via the REST API.
* **Security by Design:** Authentication, authorization, and validation are baked into the core.
* **Modular Design:** Core handles infrastructure. Features live in Modules + HTTP layer.

---

## 📊 Current Project Status (July 2026)

| Area | Status | Notes |
|------|--------|-------|
| **Backend API** | ✅ Production-ready core | Slim 4 + PHP-DI, `bootstrap/app.php`, auto-discovery routes |
| **Authentication** | ✅ Functional | Register (toggle), login, logout, 2FA, password reset, CSRF, session regeneration |
| **Authorization (RBAC)** | ✅ It. 20 | `PermissionMiddleware` on content/media writes; `RoleMiddleware` on admin |
| **Content API** | ✅ It. 19–20 | Index, pagination, search, published filter, versioning |
| **Media API** | ✅ Functional | `/api/media/*` + public `GET /storage/...` |
| **Core hardening** | ✅ It. 20 | Maintenance mode, trash restore, backup cron CLI |
| **API contract** | ✅ It. 21 | JsonResponder everywhere, MSW, Newman CI, RHF+Zod |
| **PHPUnit** | ✅ **503+ passing** | PHPStan level 8 (0 errors) |
| **Frontend** | ✅ It. 21 | MSW, typed clients, settings form validation |
| **Next iteration** | 🟡 It. 29 | Cron planner + job queue |

### Recent releases

| Version | Focus |
|---------|--------|
| **2.0.17** | It.7 — scheduled monitoring reports, HTML email, log incidents, cron CLI |
| **2.0.16** | It.28 — bulk actions platform |
| **2.0.1** | It.6 — SMTP, notification connectors, analytics, auth UI, toast settings |
| **2.0.7** | FlatFile index, pagination, search API |
| **2.0.6** | PHPStan L8, 453+ tests, security & i18n foundation |

### Tech stack

- **PHP** 8.5+ (strict types, PHPStan L8)
- **Backend:** Slim 4, PHP-DI, PSR-7/15, League CommonMark, OTPHP (2FA)
- **Frontend:** React, TypeScript, Vite 8, TailwindCSS
- **Storage:** Flat files under `backend/storage/app/content/`
- **Index:** `data/index/content.json` (flock-safe rebuild)

---

## 🗺️ Architecture Overview

```mermaid
graph TD
    Browser[Browser] --> FE[React SPA :3025]
    FE -->|/api, /storage proxy| API[Slim API :8080]
    API --> MW[Middleware chain]
    MW --> Core[Core: FlatFile, Cache, Settings, Versioning]
    Core --> Storage[(storage/app/content)]
    API --> Modules[Modules: Security, Media, Comments, …]
```

### System layers

1. **Presentation:** React admin + public site (`frontend/src/`)
2. **API:** Slim routes in `backend/app/Http/Routes/*.php` (auto-discovered)
3. **Core:** FlatFile engine, cache, settings schema, backup, audit
4. **Modules:** Security (auth/RBAC), Media, Comments, Navigation, …
5. **Storage:** Markdown/JSON content, media, trash, settings, users

---

## 🗂️ Project Directory Structure

```text
paginiumcms-architecture/
├── backend/
│   ├── app/
│   │   ├── Core/           # FlatFile, Cache, Backup, Versioning, Settings, …
│   │   ├── Http/           # Controllers, Middleware, Routes, Config/services.php
│   │   ├── Modules/        # Security, Media, Comments, Audit, …
│   │   └── Support/        # Lang, JsonHelper
│   ├── bootstrap/          # app.php (single bootstrap entry)
│   ├── bin/console         # audit:run, backup:run-schedule
│   ├── lang/               # sk/en translations
│   ├── public/             # index.php → bootstrap/app.php
│   ├── storage/            # content, cache, logs, backups
│   └── tests/              # 488 PHPUnit tests
├── frontend/               # React admin + public site
├── docs/                   # Architecture, roadmap, deploy guides
├── vendor/                 # Composer (project root)
├── composer.json
└── phpunit.xml
```

---

## 🔌 API Overview

| Group | Prefix | Access |
|-------|--------|--------|
| Auth | `/api/auth/*` | Mixed; register can be disabled via settings |
| Content | `/api/pages`, `/api/articles` | GET public (published filter); write = auth + permission |
| Search | `/api/search?q=` | Public, published only |
| Media | `/api/media/*` | EDITOR+ role; files at `/storage/app/content/media/...` |
| Static files | `/storage/{path}` | Public (path traversal blocked) |
| Settings | `/api/settings/public`, `/api/admin/settings/*` | Public slice / ADMIN |
| Trash | `/api/admin/trash/*` | EDITOR+ — list & restore soft-deleted content |
| Admin | `/api/admin/*` | Auth + role (+ 2FA where configured) |
| Health | `/api/health` | Public (allowed during maintenance) |

Routes in `backend/app/Http/Routes/*.php` are auto-loaded from `bootstrap/app.php`.

**Detailed contracts:**

- [Content API – pagination & search](architecture/CONTENT_API.md)
- [Core hardening – RBAC, maintenance, trash](architecture/CORE_HARDENING.md)

---

## 🔒 Security Principles

* **Session auth** with `session_regenerate_id()` on login (`SessionManager`)
* **Argon2id** passwords via `PasswordPolicy`
* **RBAC:** `RoleMiddleware` + `PermissionMiddleware` (`content:*`, `media:*`)
* **Maintenance mode:** blocks public API; staff session exempt
* **2FA:** TOTP via `TwoFactorManager`
* **CSRF** token endpoint + validation
* **Rate limiting:** global + login-specific
* **Path traversal:** `FileValidator` + `StorageController` realpath check
* **Registration toggle:** `general.allowRegistration`
* **Guest comments toggle:** `comments.allowGuestComments`

---

## 🚀 Getting Started

```bash
composer install
./vendor/bin/phpunit --testdox
./vendor/bin/phpstan analyse backend --level=8

# Backend
cd backend/public && php -S localhost:8080

# Frontend (separate terminal)
cd frontend && npm install && npm run dev
# → http://localhost:3025 (proxies /api and /storage to :8080)
```

See [deploy/DEV.md](deploy/DEV.md) for full local stack instructions.

### Backup cron (production)

```bash
# crontab example — hourly check
0 * * * * cd /path/to/project && php backend/bin/console backup:run-schedule
```

Schedule is stored in `backend/storage/backups/schedule.json` (create via admin backup API or `BackupManager::scheduleBackup()`).

### Developer Mode unlock (admin)

```bash
POST /api/admin/developer/unlock  { "totp_code": "123456" }
GET  /api/admin/developer/logs    # requires unlocked dev mode
```

See [user/DEVELOPER_MODE.md](user/DEVELOPER_MODE.md).

---

## 📚 Documentation Index

| Document | Purpose |
|----------|---------|
| [ROADMAP.md](ROADMAP.md) | Iterations 1–21, priorities, implementation phases |
| [CHANGELOG.md](../CHANGELOG.md) | Release notes |
| [architecture/ARCHITECTURE.md](architecture/ARCHITECTURE.md) | Deep architecture spec |
| [architecture/API_CONTRACT.md](architecture/API_CONTRACT.md) | JSON response envelopes (200/422/409/meta) |
| [architecture/CONTENT_API.md](architecture/CONTENT_API.md) | Pagination, search, published rules |
| [architecture/CORE_HARDENING.md](architecture/CORE_HARDENING.md) | RBAC, maintenance, trash, storage |
| [architecture/STORAGE.md](architecture/STORAGE.md) | Flat-file layout |
| [developer/TESTING.md](developer/TESTING.md) | PHPUnit, PHPStan, test layout |
| [developer/DEVELOPMENT.md](developer/DEVELOPMENT.md) | Contributor workflow |
| [deploy/DEV.md](deploy/DEV.md) | Local dev stack |
| [deploy/NGINX_API.md](deploy/NGINX_API.md) | Production nginx |

### Iteration docs (`docs/ITERATION_*.md`)

| It. | Document | Status |
|-----|----------|--------|
| 1 | [ITERATION_1.md](ITERATION_1.md) | ✅ Locking |
| 2 | [ITERATION_2.md](ITERATION_2.md) | ✅ Drafts & versioning |
| 3 | [ITERATION_3.md](ITERATION_3.md) | ✅ Conflict resolution |
| 4 | [ITERATION_4.md](ITERATION_4.md) | ✅ Settings & validation |
| 5 | [ITERATION_5.md](ITERATION_5.md) | ✅ Users & auth |
| 6 | [ITERATION_6.md](ITERATION_6.md) | ✅ Notifications & analytics |
| 7 | [ITERATION_7.md](ITERATION_7.md) | ✅ Dashboard & monitoring |
| 8 | [ITERATION_8.md](ITERATION_8.md) | ✅ Media manager FE |
| 9 | [ITERATION_9.md](ITERATION_9.md) | ✅ Prototype port (nav, comments, contact, GitHub) |
| 10 | [ITERATION_10.md](ITERATION_10.md) | 🚧 RSS & sitemap → [It. 22](ITERATION_22.md) |
| 11 | [ITERATION_11.md](ITERATION_11.md) | ⏳ SSO & ACL |
| 12 | [ITERATION_12.md](ITERATION_12.md) | ⏳ Blueprint engine |
| 13 | [ITERATION_13.md](ITERATION_13.md) | ⏳ Demo module |
| 14 | [ITERATION_14.md](ITERATION_14.md) | ✅ Code policy |
| 15 | [ITERATION_15.md](ITERATION_15.md) | ⏳ Plugin runtime |
| 16 | [ITERATION_16.md](ITERATION_16.md) | 🟡 Monaco / full editor |
| 17 | [ITERATION_17.md](ITERATION_17.md) | 🟡 API↔FE scaffold |
| 18 | [ITERATION_18.md](ITERATION_18.md) | 🟡 i18n UI migration |
| 19 | [ITERATION_19.md](ITERATION_19.md) | ✅ Index & pagination |
| 20 | [ITERATION_20.md](ITERATION_20.md) | ✅ Core hardening |
| 21 | [ITERATION_21.md](ITERATION_21.md) | ✅ API contract & MSW |
| 22 | [ITERATION_22.md](ITERATION_22.md) | ✅ Trash UI, lockout, RSS/sitemap |
| 23 | [ITERATION_23.md](ITERATION_23.md) | ✅ SEO meta engine |
| 27 | [ITERATION_27.md](ITERATION_27.md) | ⏳ Admin view modes + SEO panel (next) |

---

## ⚠️ Known Limitations

* **It. 23 (2.0.11):** ✅ Complete — see [ITERATION_23.md](ITERATION_23.md)
* **Backup create tests** skipped under vfsStream (ZipArchive); schedule/cron logic tested on real temp dirs

---

> **Documentation First:** When code and docs differ, update docs to reflect reality, then align code in the next change.
