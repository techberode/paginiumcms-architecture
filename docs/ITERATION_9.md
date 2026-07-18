# Iteration 9 – Prototype Backend Port + Admin FE Wiring

**Status:** Complete  
**Version:** 2.0.5  
**Release track:** Bridge from `prototype/backend/` scripts to production Slim modules

## Summary

Mapped legacy prototype PHP endpoints (`/backend/v1/...`) to typed Slim routes under `/api/*` with flat-file storage. Shipped missing **real** features (navigation, comments, contact inbox, GitHub sync panel) and wired corresponding React admin + public components in the same release.

> **Note on roadmap numbering:** Early roadmap drafts listed “Iteration 9 – SEO meta engine”. That work was delivered later as **[Iteration 23](ITERATION_23.md)** (2.0.11). This document describes **Iteration 9 as shipped in 2.0.5** — the prototype port.

## Logical sequence

```
Prototype scripts → It.9 (port to /api/* + flat-file modules)
                 → It.8 FE media (same release polish)
                 → It.23 SEO meta engine (separate iteration)
```

## Scope

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | Navigation module (public read + admin update) | ✅ |
| 2 | Comments module (public submit/list + admin moderation) | ✅ |
| 3 | Contact form + admin Messages inbox | ✅ |
| 4 | GitHub sync admin API (via `GitHubService`) | ✅ |
| 5 | Settings group `comments` | ✅ |
| 6 | Frontend API clients + admin views | ✅ |
| 7 | Public site wiring (nav, comments, contact) | ✅ |
| 8 | PHPUnit + Vitest coverage | ✅ |

---

## Part 1 – Prototype → production mapping ✅

| Prototype (`/backend/v1/...`) | Current API | Status |
|---|---|---|
| `navigation.php` | `GET /api/navigation`, `PUT /api/admin/navigation` | ✅ Ported |
| `comments.php` | `GET/POST /api/comments`, admin `/api/admin/comments/*` | ✅ Ported |
| `contact.php` | `POST /api/contact` | ✅ Ported |
| `messages.php` | `GET/PATCH/DELETE /api/admin/messages/*` | ✅ Ported |
| `github-sync.php` | `GET/POST/PUT /api/admin/github/*` | ✅ Ported |
| `pages`, `blog`, `media`, `auth`, `users`, `settings`, `dashboard`, `analytics` | Existing `/api/*` | Already implemented |
| `debug-toast(s)`, `index.php` mocks, `smtp.php` secrets | — | Skipped (insecure / obsolete) |

**Route files:** `backend/app/Http/Routes/navigation.php`, `comments.php`, `contact.php`, `messages.php`, `github.php` (auto-discovered via `bootstrap/app.php`).

---

## Part 2 – Backend modules ✅

### Navigation

| Item | Detail |
|------|--------|
| Storage | `data/navigation.json` |
| Public | `GET /api/navigation` — tree for public site header/footer |
| Admin | `PUT /api/admin/navigation` — ADMIN / SUPER_ADMIN + 2FA |
| Service | `Modules/Navigation/Services/NavigationRepository.php` |

### Comments

| Item | Detail |
|------|--------|
| Storage | `data/comments.json` |
| Public | `GET /api/comments?article=…` — approved only; `POST /api/comments` — submit |
| Admin | `GET /api/admin/comments`, `PUT /api/admin/comments/{id}`, `DELETE /api/admin/comments/{id}` |
| Settings | `comments.enabled`, `requireApproval`, `allowGuestComments`, `maxLength` |
| Service | `Modules/Comments/Services/CommentsRepository.php` |

### Contact & Messages

| Item | Detail |
|------|--------|
| Public | `POST /api/contact` — contact form submission |
| Storage | `data/messages/{id}.json` (one file per message) |
| Admin | `GET /api/admin/messages`, `PATCH /api/admin/messages/{id}` (mark read), `DELETE …` |
| Services | Contact controller + `Modules/Messages/Services/MessageRepository.php` |

### GitHub sync

| Item | Detail |
|------|--------|
| Admin | `GET /api/admin/github/status`, `POST …/export`, `…/import`, `…/sync`, `PUT …/auto-sync` |
| Service | `Core/GitHub/Services/GitHubService.php` (existing; routes exposed in It.9) |
| Env | See `.env.example`: `GITHUB_ENABLED`, `GITHUB_TOKEN`, `GITHUB_REPO`, `GITHUB_BRANCH`, `GITHUB_AUTO_SYNC`, `GITHUB_CONTENT_PATH` |

---

## Part 3 – Frontend wiring ✅

### API clients

| File | Purpose |
|------|---------|
| `frontend/src/api/navigation.ts` | Load/update navigation tree |
| `frontend/src/api/comments.ts` | Public list/submit + admin moderation |
| `frontend/src/api/contact.ts` | Contact form POST |
| `frontend/src/api/messages.ts` | Admin inbox |
| `frontend/src/api/github.ts` | GitHub sync panel |

### Admin views

| Component | Route | Role |
|-----------|-------|------|
| `NavigationManager.tsx` | `/navigation` | Drag-order nav editor |
| `CommentsManager.tsx` | `/comments` | Approve/reject/delete |
| `MessagesViewer.tsx` | `/messages` | Contact inbox |
| `GitHubSyncPanel.tsx` | `/github` | Export/import/sync status |

### Public site

| Component | Integration |
|-----------|-------------|
| `PublicSiteContext.tsx` | Loads nav from `/api/navigation` (replaces hardcoded builder) |
| `ArticleComments.tsx` | Lists/submits via `/api/comments` |
| `ContactForm.tsx` | Posts to `/api/contact` |

### Same-release polish (It.8 completion)

- `utils/apiBaseUrl.ts` — same-origin LAN deploy fix
- `MarkdownEditor` — Markdown / WYSIWYG toggle, `MediaPickerModal`, TipTap build fix

---

## Part 4 – Settings ✅

**Group `comments`** in `SettingsSchema`:

| Key | Type | Purpose |
|-----|------|---------|
| `enabled` | bool | Master switch for comments |
| `requireApproval` | bool | Moderation queue |
| `allowGuestComments` | bool | Anonymous submissions (enforcement → It.38) |
| `maxLength` | int | Max comment body length |

---

## Part 5 – Tests ✅

| Suite | Files |
|-------|-------|
| PHPUnit repositories | `NavigationRepositoryTest`, `CommentsRepositoryTest`, `MessageRepositoryTest` |
| PHPUnit controllers | Navigation, Comments, Contact, GitHub controller tests |
| Vitest | `NavigationManager.test.tsx` |

---

## Test plan (manual)

1. **Navigation:** Admin → Navigation → reorder items → public site header reflects order after refresh.
2. **Comments:** Public article → submit comment → Admin → Comments → approve → visible on site.
3. **Contact:** Public contact form → Admin → Messages → message appears, mark read, delete.
4. **GitHub:** Admin → GitHub → status loads; export/import only when env vars set.

---

## Deploy

Backend + frontend (`paginium-deploy`). Optional GitHub env vars for sync panel. No schema migration — flat JSON only.

See also: `docs/deploy/NGINX_API.md` (added in 2.0.5 for same-origin `/api` proxy).

---

## Related

- [ITERATION_8.md](ITERATION_8.md) — Media Manager FE (completed in same release)
- [ITERATION_23.md](ITERATION_23.md) — SEO meta engine (originally planned under a separate “SEO track”)
- [CHANGELOG.md](../CHANGELOG.md) — `[2.0.5]`
- [ROADMAP.md](ROADMAP.md) — iteration map

## Next (historical)

- ~~Wire FE admin views~~ ✅ 2.0.5
- ~~Replace `PublicSiteContext` nav builder~~ ✅ 2.0.5
- SEO admin UX → deferred to [Iteration 27](ITERATION_27.md) (builds on It.23 backend)
