# PaginiumCMS – API Reference

> **Version:** 2.0.26 · **Contract:** [API_CONTRACT.md](API_CONTRACT.md)

Canonical JSON shapes: `{ success, data?, error?, errors?, meta? }`. Auth endpoints use a **legacy flat envelope** (`user` at root) — see API_CONTRACT §2.6. WAF blocks may return plain **403** — see §2.8.

---

## Public (no session)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check (`data.status`, version, PHP) |
| GET | `/api/pages` | List pages (pagination: `?page=&per_page=`) |
| GET | `/api/pages/{slug}` | Single page (published only if unauthenticated) |
| GET | `/api/articles` | List articles (pagination) |
| GET | `/api/articles/{slug}` | Single article |
| GET | `/api/search?q=&scope=public\|admin&types=` | Search — public: published content; admin: palette (auth) — [It.43](../ITERATION_43.md) |
| GET | `/api/navigation` | Public navigation tree |
| GET | `/api/comments` | Approved comments |
| POST | `/api/comments` | Submit comment (guest if allowed) |
| POST | `/api/contact` | Contact form |
| GET | `/api/seo/{type}/{slug}` | Public SEO meta |
| GET | `/api/feeds/{type}` | RSS/Atom feeds |
| POST | `/api/auth/login` | Login (legacy envelope) |
| POST | `/api/auth/register` | Register (+ OTP verify if enabled) |
| POST | `/api/auth/reset-password` | Password reset request |
| POST | `/api/auth/verify-reset-token` | Complete password reset |
| GET | `/api/auth/csrf-token` | CSRF token |
| GET | `/api/validation/rules` | Shared validation rules export |
| GET | `/api/settings/public` | Public settings slice |
| GET | `/storage/{path}` | Media/static files |
| POST | `/api/debug/client-event` | Debug telemetry (204 when disabled) |

---

## Authenticated (session cookie + CSRF)

| Method | Endpoint | Permission / role |
|--------|----------|-------------------|
| POST | `/api/auth/logout` | any |
| GET | `/api/auth/me` | any |
| POST | `/api/auth/change-password` | any |
| POST/PATCH/DELETE | `/api/pages`, `/api/articles` | `content:*` (EDITOR+) |
| GET/POST/PATCH/DELETE | `/api/media/*` | `media:*` (EDITOR+) |
| PUT/GET/DELETE | `/api/drafts/{type}/{slug}` | EDITOR+ |
| POST/GET/DELETE | `/api/locks/*` | EDITOR+ |
| POST | `/api/workflows/otp/verify` | EDITOR+ (OTP step) |
| POST | `/api/workflows/otp/resend` | EDITOR+ |

---

## Admin (`/api/admin/*`, ADMIN + 2FA)

| Area | Key routes |
|------|------------|
| Settings | `GET/PUT/DELETE /api/admin/settings`, `PUT /api/admin/settings/{group}` |
| Users | `/api/admin/users` CRUD + bulk |
| Trash | `/api/admin/trash` list, restore, bulk purge/backup, empty |
| Backups | `/api/admin/backups` CRUD + schedule |
| Versions | `/api/admin/versions/{contentId}`, compare, restore, cleanup |
| Audit | `/api/admin/audit/*`, CSV export |
| Conflicts | `GET/DELETE /api/admin/conflicts` |
| Health | `/api/admin/health`, `/api/admin/health/{name}` |
| Dashboard | `GET /api/admin/dashboard/overview` |
| Counts | `GET /api/admin/counts` — sidebar badges |
| Analytics | `/api/admin/analytics/*` |
| **Logs** | `GET /api/admin/logs`, `/stats`, `POST /purge` (2.0.26) |
| **Firewall** | `/api/admin/firewall/*` — stats, bans, whitelist, incidents (It.50) |
| Code editor | `/api/admin/code-editor/*` (developer gate) |
| Developer | `/api/admin/developer/unlock`, `/api/admin/developer/logs` |
| GitHub | `/api/admin/github/*` |
| Messages | `/api/admin/messages/*` + bulk workflow |
| Comments mod | `/api/admin/comments/*` + bulk workflow |
| Navigation | `PUT /api/admin/navigation` |
| Notifications | overview, test connector, SMTP |
| Jobs | `/api/admin/jobs/*` — scheduler CRUD, run, queue |

---

## Response examples

**Success (paginated):**

```json
{ "success": true, "data": [], "meta": { "page": 1, "per_page": 20, "total": 0, "total_pages": 0 } }
```

**OTP pending (200):**

```json
{ "success": true, "requires_otp": true, "challenge_id": "otp_…" }
```

**Validation (422):**

```json
{ "success": false, "error": "…", "errors": { "email": ["…"] } }
```

**Conflict (409):**

```json
{ "success": false, "error": "…", "conflict": { } }
```

---

## Frontend clients

| Module | File |
|--------|------|
| Auth | `frontend/src/api/auth.ts` |
| Content | `frontend/src/api/content.ts` |
| Media | `frontend/src/api/media.ts` |
| Settings | `frontend/src/api/settings.ts` |
| Firewall | `frontend/src/api/firewall.ts` |
| Logs | `frontend/src/api/logs.ts` |
| Workflows (OTP) | `frontend/src/api/workflows.ts` |
| Barrel | `frontend/src/api/index.ts` |

Dev mocks: `VITE_MSW=true npm run dev` → `frontend/src/mocks/handlers.ts`

---

## Testing

- Contract: `backend/tests/Http/Contract/ApiResponseShapeTest.php`
- Postman: `docs/api/PaginiumCMS.postman_collection.json`
- Newman: `./scripts/run-api-smoke.sh`
- CI: `.github/workflows/ci.yml` — pozri [ISSUES.md](../ISSUES.md)

---

## Related

- [CONTENT_API.md](CONTENT_API.md) – pagination, search, published rules
- [CORE_HARDENING.md](CORE_HARDENING.md) – RBAC, maintenance, trash, WAF
- [BACKEND.md](BACKEND.md) – middleware, route discovery
- [user/FIREWALL.md](../user/FIREWALL.md) – WAF admin guide
- [user/LOGGING.md](../user/LOGGING.md) – logy admin guide
