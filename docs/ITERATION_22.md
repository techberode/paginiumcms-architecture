# Iteration 22 – Ops Finish & Public Discoverability

**Status:** Complete  
**Version:** 2.0.10  
**Release track:** post-2.0.9 (closes It. 20 gaps + delivers It. 10 feeds)

## Summary

After API contract hardening (It. 21), production CMS still needs **operational admin UI** for trash restore, **login brute-force protection**, and **public RSS/sitemap** for discoverability. Iteration 22 closes these gaps in one release so deploys are safe end-to-end without manual file recovery.

## Why now (logical sequence)

```
It.19 (index + published filter)
  └─► It.20 (RBAC, trash API, maintenance) — FE trash + lockout deferred
        └─► It.21 (JsonResponder, CI, same-origin deploy)
              └─► It.22 ← YOU ARE HERE
                    ├─ Trash admin UI (It. 20 remaining)
                    ├─ Brute-force lockout (It. 20 remaining)
                    └─ RSS + sitemap (It. 10 scope)
                          └─► It. 6–7 polish / It. 9 SEO (next)
```

| Depends on | Reason |
|------------|--------|
| ✅ It. 19 | Feeds/sitemap read **published** entries from `ContentIndexService` |
| ✅ It. 20 | Trash API + soft-delete meta already exist; only FE + lockout missing |
| ✅ It. 21 | Stable `{ success, data }` envelope for trash + settings endpoints |

## Scope

| # | Deliverable | Source iteration | Status |
|---|-------------|------------------|--------|
| 1 | Trash admin UI | It. 20 | ✅ |
| 2 | Brute-force lockout (email + IP) | It. 20 | ✅ |
| 3 | RSS `GET /feed.xml` | It. 10 | ✅ |
| 4 | Sitemap `GET /sitemap.xml` | It. 10 | ✅ |
| 5 | Settings groups `feeds` + `security` | It. 10 | ✅ |
| 6 | Same-origin production deploy guard | It. 21 patch | ✅ (`build:prod`, `deploy-frontend-lan.sh`) |

---

## Part 1 – Trash admin UI ✅

**Backend (unchanged — It. 20):**

| Method | Route | Response |
|--------|-------|----------|
| GET | `/api/admin/trash` | `{ success, data: TrashItem[] }` |
| POST | `/api/admin/trash/{id}/restore` | `{ success, data: { originalPath }, message }` |

**Frontend (new):**

| File | Role |
|------|------|
| `frontend/src/api/trash.ts` | Typed `trashApi.list()` / `restore()` |
| `frontend/src/components/backend/TrashManager.tsx` | Table + restore action |
| `frontend/src/App.tsx` | Route `/trash` |
| `frontend/src/components/backend/AdminSidebar.tsx` | „Kôš" link (admin only) |
| `frontend/src/api/index.ts` | Barrel export + `api.trash` |

**TrashItem shape:**

```ts
{ id, originalPath, deletedAt, filename, size }
```

---

## Part 2 – Brute-force lockout ✅

**Backend (planned):**

| Component | Change |
|-----------|--------|
| `Core/Security/SecurityLogger.php` | Implement `checkBruteForceAttempt()` (currently TODO) |
| `data/security/login_attempts.json` | Flat-file counters per IP + email (`flock`) |
| `SettingsSchema` | Group `security`: `maxLoginAttempts`, `lockoutMinutes` |
| `AuthController::login` | HTTP **429** when locked; reset counter on success |
| `IncidentNotifier` | Optional alert when lockout threshold hit (It. 6) |

**Frontend:**

- Login modal shows lockout message when API returns 429

---

## Part 3 – XML feeds ✅

**Backend (planned):**

```
Core/Feeds/
├── Services/FeedGenerator.php      # RSS 2.0 from published articles
├── Services/SitemapGenerator.php   # URL set from content index
└── Config/services.php

Http/Controllers/Feeds/FeedController.php
Http/Routes/feeds.php               # public, no auth
```

| Route | Content-Type | Source |
|-------|--------------|--------|
| `GET /feed.xml` | `application/rss+xml` | Published articles (newest first, limit from settings) |
| `GET /sitemap.xml` | `application/xml` | Published pages + articles |

**Settings group `feeds` (It. 4 engine):**

- `feeds.enabled`, `feeds.title`, `feeds.description`
- `feeds.itemsLimit`, `feeds.includePages`, `feeds.includeArticles`

**Frontend:**

- Feeds fields in `SettingsView` (schema-driven)
- `<link rel="alternate" type="application/rss+xml">` in `PublicSiteLayout`

**Cache (optional):** reuse `ContentCacheService` for generated XML TTL.

---

## Tests

| Suite | File | Status |
|-------|------|--------|
| Vitest | `TrashManager.test.tsx` | ✅ 3 tests |
| Vitest | `trash.test.ts` | — |
| PHPUnit | `BruteForceLockoutTest` / `AuthControllerTest::testLoginLockout` | ✅ |
| PHPUnit | `FeedGeneratorTest`, `FeedControllerTest` | ✅ |
| HTTP | `GET /feed.xml`, `GET /sitemap.xml` in Newman smoke | ⏳ |

**Release gate:** PHPUnit + PHPStan L8 + Vitest green + CHANGELOG `[2.0.10]`.

---

## Documentation updates (this iteration)

- [ROADMAP.md](ROADMAP.md) — It. 22 section + dependency diagram
- [architecture/API.md](architecture/API.md) — trash + feeds endpoints
- [CHANGELOG.md](../CHANGELOG.md) — `[2.0.10]` on release

---

## Related

- [ITERATION_20.md](ITERATION_20.md) — trash API, RBAC
- [ITERATION_21.md](ITERATION_21.md) — JsonResponder, CI
- [ITERATION_10.md](ITERATION_10.md) — original feeds design (absorbed into It. 22)
- [deploy/NGINX_API.md](deploy/NGINX_API.md) — same-origin SPA + `/api` proxy

## Next

→ **Iteration 6–7 polish** (SMTP end-to-end, analytics alerts) or **Iteration 9** (SEO meta engine) — after It. 22 release
