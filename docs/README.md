# 🏛️ PaginiumCMS

> **Version:** 2.0.19  
> **Last updated:** 18 July 2026  
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
| **Job scheduler** | ✅ It. 29 | Flat-file registry, `scheduler:run`, admin `/scheduler` |
| **Monitoring** | ✅ It. 7 | Scheduled reports, log incidents, HTML email, cron CLI |
| **API contract** | ✅ It. 21 | JsonResponder everywhere, MSW, Newman CI, RHF+Zod |
| **PHPUnit** | ✅ **550+ passing** | PHPStan level 8 (0 errors) |
| **Frontend** | ✅ It. 21+ | MSW, typed clients, settings form validation, bulk actions |
| **Next iteration** | 🟡 It. 41 | Email OTP workflows — see [backlog](ITERATION_BACKLOG.md) |

### Planned (roadmap)

| It. | Feature |
|-----|---------|
| **43** | **Advanced search (FE + BE)** — command palette, quick jumps in admin & public site |
| **44** | **Filters & sorting (admin + FE)** — shared filter bar, URL-synced query params |
| **45** | **Redis (optional)** — shared cache/queue when scaling to multiple PHP workers |
| **46** | **Server metrics agent** — CPU/RAM/disk/Docker for monitoring reports (extends It.7) |

Full backlog: [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) · Main map: [ROADMAP.md](ROADMAP.md)

### Recent releases

| Version | Focus |
|---------|--------|
| **2.0.19** | Admin user management UI — username, account status, 2FA secret, system enforcement |
| **2.0.18** | It.29 — cron planner, job queue, `/scheduler`, unified CLI |
| **2.0.17** | It.7 — scheduled monitoring reports, HTML email, log incidents |
| **2.0.16** | It.28 — bulk actions platform |
| **2.0.15** | It.27 — admin view modes + SEO panel |
| **2.0.1** | It.6 — SMTP, notification connectors, analytics, auth UI |

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
    Scheduler[scheduler:run CLI] --> Core
```

### System layers

1. **Presentation:** React admin + public site (`frontend/src/`)
2. **API:** Slim routes in `backend/app/Http/Routes/*.php` (auto-discovered)
3. **Core:** FlatFile engine, cache, settings schema, backup, audit, job scheduler
4. **Modules:** Security (auth/RBAC), Media, Comments, Navigation, …
5. **Storage:** Markdown/JSON content, media, trash, settings, users

---

## 🗂️ Project Directory Structure

```text
paginiumcms-architecture/
├── backend/
│   ├── app/
│   │   ├── Core/           # FlatFile, Cache, Backup, Scheduler, Settings, …
│   │   ├── Http/           # Controllers, Middleware, Routes, Config/services.php
│   │   ├── Modules/        # Security, Media, Comments, Audit, …
│   │   └── Support/        # Lang, JsonHelper
│   ├── bootstrap/          # app.php (single bootstrap entry)
│   ├── bin/console         # audit:run, scheduler:run, worker:process, …
│   ├── lang/               # sk/en translations
│   ├── public/             # index.php → bootstrap/app.php
│   ├── storage/            # content, cache, logs, backups
│   └── tests/              # PHPUnit suite
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
| Search | `/api/search?q=` | Public, published only (It.43: advanced / scoped search) |
| Media | `/api/media/*` | EDITOR+ role; files at `/storage/app/content/media/...` |
| Jobs | `/api/admin/jobs/*` | ADMIN — cron registry, run history |
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

### Cron (production)

```bash
# Unified scheduler — every minute
* * * * * cd /path/to/paginiumcms && php backend/bin/console scheduler:run && php backend/bin/console worker:process
```

Legacy: `backup:run-schedule`, `monitoring:run-schedule` (still supported).  
See [ITERATION_29.md](ITERATION_29.md) and [deploy/DEV.md](deploy/DEV.md).

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
| [ROADMAP.md](ROADMAP.md) | Iterations 1–29+, priorities, implementation phases |
| [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | It.30+ backlog (search, filters, OTP, …) |
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
| 7 | [ITERATION_7.md](ITERATION_7.md) | ✅ Monitoring reports & log incidents |
| 8–9 | [ITERATION_8.md](ITERATION_8.md) … [ITERATION_9.md](ITERATION_9.md) | ✅ Media, prototype port |
| 19–22 | [ITERATION_19.md](ITERATION_19.md) … [ITERATION_22.md](ITERATION_22.md) | ✅ Index, hardening, contract, ops |
| 27 | [ITERATION_27.md](ITERATION_27.md) | ✅ Admin view modes + SEO panel |
| 28 | [ITERATION_28.md](ITERATION_28.md) | ✅ Bulk actions |
| 29 | [ITERATION_29.md](ITERATION_29.md) | ✅ Cron planner + job queue |
| 43–46 | [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) | ⏳ Search, filters, Redis, metrics agent |

---

## ⚠️ Known Limitations

* **Basic search only** — It.43 will add scoped quick-jump search (admin palette + public instant search)
* **List filters/sort** — partial via API pagination; full admin + FE filter bar planned in It.44
* **Backup create tests** skipped under vfsStream (ZipArchive); schedule/cron logic tested on real temp dirs

---

> **Documentation First:** When code and docs differ, update docs to reflect reality, then align code in the next change.
