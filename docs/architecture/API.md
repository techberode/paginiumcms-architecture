# PaginiumCMS – API Reference

> **Version:** 2.0.9 · **Contract:** [API_CONTRACT.md](API_CONTRACT.md)

Canonical JSON shapes: `{ success, data?, error?, errors?, meta? }`. Auth endpoints use a **legacy flat envelope** (`user` at root) — see API_CONTRACT §2.6.

---

## Public (no session)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check (`data.status`, version, PHP) |
| GET | `/api/pages` | List pages (pagination: `?page=&per_page=`) |
| GET | `/api/pages/{slug}` | Single page (published only if unauthenticated) |
| GET | `/api/articles` | List articles (pagination) |
| GET | `/api/articles/{slug}` | Single article |
| GET | `/api/search?q=` | Fulltext search (min 2 chars, published) |
| GET | `/api/navigation` | Public navigation tree |
| GET | `/api/comments` | Approved comments |
| POST | `/api/comments` | Submit comment (guest if allowed) |
| POST | `/api/contact` | Contact form |
| POST | `/api/auth/login` | Login (legacy envelope) |
| POST | `/api/auth/register` | Register (if `allowRegistration`) |
| POST | `/api/auth/reset-password` | Password reset request |
| POST | `/api/auth/verify-reset-token` | Complete password reset |
| GET | `/api/auth/csrf-token` | CSRF token |
| GET | `/api/validation/rules` | Shared validation rules export |
| GET | `/api/settings/public` | Public settings slice |
| GET | `/storage/{path}` | Media/static files |

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

---

## Admin (`/api/admin/*`, ADMIN + 2FA)

| Area | Key routes |
|------|------------|
| Settings | `GET/PUT/DELETE /api/admin/settings`, `PUT /api/admin/settings/{group}` |
| Users | `/api/admin/users` CRUD |
| Trash | `GET /api/admin/trash`, `POST /api/admin/trash/{id}/restore` |
| Backups | `/api/admin/backups` CRUD + schedule |
| Versions | `/api/admin/versions/{contentId}`, compare, restore, cleanup |
| Audit | `/api/admin/audit/*`, CSV export |
| Conflicts | `GET/DELETE /api/admin/conflicts` |
| Health | `/api/admin/health`, `/api/admin/health/{name}` |
| Dashboard | `GET /api/admin/dashboard/overview` |
| Analytics | `/api/admin/analytics/*` |
| Code editor | `/api/admin/code-editor/*` (developer gate) |
| Developer | `/api/admin/developer/unlock`, `/api/admin/developer/logs` |
| GitHub | `/api/admin/github/*` |
| Messages | `/api/admin/messages/*` |
| Comments mod | `/api/admin/comments/*` |
| Navigation | `PUT /api/admin/navigation` |

---

## Response examples

**Success (paginated):**

```json
{ "success": true, "data": [], "meta": { "page": 1, "per_page": 20, "total": 0, "total_pages": 0 } }
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
| Barrel | `frontend/src/api/index.ts` |

Dev mocks: `VITE_MSW=true npm run dev` → `frontend/src/mocks/handlers.ts`

---

## Testing

- Contract: `backend/tests/Http/Contract/ApiResponseShapeTest.php`
- Postman: `docs/api/PaginiumCMS.postman_collection.json`
- Newman: `./scripts/run-api-smoke.sh`

---

## Related

- [CONTENT_API.md](CONTENT_API.md) – pagination, search, published rules
- [CORE_HARDENING.md](CORE_HARDENING.md) – RBAC, maintenance, trash
- [ITERATION_21.md](../ITERATION_21.md) – iteration scope
