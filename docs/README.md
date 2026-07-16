# 🏛️ PaginiumCMS

> **Version:** 2.0.8  
> **Last updated:** 16 July 2026  
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
| **Admin tools** | ✅ Wired | Code editor (gated), versioning, audit, backups, developer logs |
| **PHPUnit** | ✅ **488 passing** | 1223 assertions, PHPStan level 8 (0 errors) |
| **Frontend** | 🟡 Strong base | Admin SPA, public site, preview, role guard; It. 21 = API contract + MSW |
| **Next iteration** | 🔴 It. 21 | API_CONTRACT.md, Postman/Newman, JsonResponder everywhere |

### Recent releases

| Version | Focus |
|---------|--------|
| **2.0.8** | Core hardening — RBAC, maintenance, `/storage`, trash API, backup cron, FE preview |
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
| [architecture/CONTENT_API.md](architecture/CONTENT_API.md) | Pagination, search, published rules |
| [architecture/CORE_HARDENING.md](architecture/CORE_HARDENING.md) | RBAC, maintenance, trash, storage |
| [architecture/STORAGE.md](architecture/STORAGE.md) | Flat-file layout |
| [developer/TESTING.md](developer/TESTING.md) | PHPUnit, PHPStan, test layout |
| [developer/DEVELOPMENT.md](developer/DEVELOPMENT.md) | Contributor workflow |
| [deploy/DEV.md](deploy/DEV.md) | Local dev stack |
| [deploy/NGINX_API.md](deploy/NGINX_API.md) | Production nginx |

---

## ⚠️ Known Limitations

* **It. 21 pending:** unified `API_CONTRACT.md`, Postman/Newman CI, MSW on frontend
* **Backup create tests** skipped under vfsStream (ZipArchive); schedule/cron logic tested on real temp dirs
* **Trash admin UI** in React not yet built (API ready)
* **Brute-force per-email lockout** planned in ROADMAP, not yet implemented

---

> **Documentation First:** When code and docs differ, update docs to reflect reality, then align code in the next change.
