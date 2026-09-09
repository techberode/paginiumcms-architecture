# Release `v2.1.0-beta.68` — Project Site Planner, pagination hotfix

> **Date:** 2026-09-09  
> **Tag:** `v2.1.0-beta.68`  
> **Type:** It.87 (planner + editorial UX), admin list pagination (ISS-169), landing SEO hero (ISS-168)

---

## One-line summary

Ships the **Project Site Planner** (It.87a–m), restores **Next/Previous** on admin lists that sync `?page=` into the URL, and applies the landing OG-hero plus editor slug/landing fixes that were waiting in the tree after `beta.67`.

---

## What shipped

| Area | Change |
|------|--------|
| **Project planner** | Flat-file plans, `/api/admin/project-plans`, `/platform/project-planner`, content linking, dashboard KPIs |
| **Theme JS** | Allow-list + SRI + CSP hashes (`appearance.themeScriptsEnabled` default **false**) |
| **Admin UX** | List skeletons, empty-state CTAs, getting-started tour, public `srcset` |
| **ISS-169** | Pages/articles/media/comments/trash pagination no longer snaps back to page 1 |
| **ISS-168** | Landing/home hero uses SEO / OG image |

---

## Admin list pagination (ISS-169)

URL-synced lists keep `page` in the query string (`/pages?page=2`). **Next** / **Previous** must stay on that page. Changing search, status, sort, or **page size** still resets to page 1. A bookmarked `?page=N` survives reload.

Affected modules: pages, articles, media, comments, trash (`useAdminListQueryParams` / `useMediaListQueryParams`).

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.68 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.68 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

Post-deploy:

```bash
curl -fsS https://paginiumcms.com/api/health
```

Confirm `version` is `2.1.0-beta.68`. In admin → Pages, with more rows than the page size, **Next** must show the second slice and keep `?page=2`.

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green before tag
- [ ] Admin → Pages → Next stays on page 2 (URL `?page=2`)
- [ ] Articles, media, comments, trash: same pagination behaviour
- [ ] Changing page size or a filter returns to page 1
- [ ] Workspace → Project planner loads when `projectPlanner.enabled` is true
- [ ] Landing/home with SEO image shows the hero photo

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-68)
- [ISS-169](../ISSUES.md#iss-169) · [ISS-168](../ISSUES.md#iss-168)
- [PROJECT_PLANNER.md](user/PROJECT_PLANNER.md)
- [ITERATION_87.md](ITERATION_87.md)
- [DEPLOY.md](../deploy/DEPLOY.md)
