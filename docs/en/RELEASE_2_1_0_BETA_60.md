# Release `v2.1.0-beta.60` — Admin UX polish (It.86) + Origin manifest automation

> **Date:** 2026-08-30  
> **Tag:** `v2.1.0-beta.60`  
> **Iterations:** It.86 (admin UX), It.82e (Origin deploy/checklist automation), It.87 (spec only)  
> **Incidents:** [ISS-158](../ISSUES.md#iss-158), [ISS-159](../ISSUES.md#iss-159)

---

## One-line summary

Editors get **command palette session search**, optional **article print**, and **bulk “X of Y selected”** counters; maintainers get **Origin Panel deploy badges** and **checklist-driven release slices**; **ISS-158/159** fix admin search 401 and follow-up HTTP 500.

---

## What's new for editors

| Feature | Where | Notes |
|---------|-------|-------|
| **Command palette** | Admin header search; **Ctrl+Shift+K** | Session auth on `GET /api/search?scope=admin`; local module shortcuts when query empty |
| **Article print** | Public blog detail | Setting `content.articlePrintEnabled` (default **off**); `@media print` CSS |
| **Bulk counter** | Pages, articles, messages, comments | `BulkActionBar` + confirm dialogs show **“:selected of :total selected”** |

---

## Origin Panel (maintainer)

| Feature | Notes |
|---------|-------|
| **Deploy badges** | `CatalogDeployStatusResolver` — `live` / `pending_deploy` / `partial_live` vs `AppVersion::current()` |
| **Release slices** | `implementation-checklist.json` merged into overview |
| **Runtime row** | App version, environment, live/pending deploy counts |
| **Five new probes** | It.83 themes, It.86 admin UX items |

Manifest SSOT: [`docs/manifest/README.md`](../manifest/README.md).

---

## Bug fixes

| ID | Symptom | Fix |
|----|---------|-----|
| [ISS-158](../ISSUES.md#iss-158) | Admin search 401 while logged in | `SearchController` session fallback via `AuthenticationInterface` |
| [ISS-159](../ISSUES.md#iss-159) | HTTP 500 after ISS-158 (DI) | `services.php` — add `AuthenticationInterface` before `JsonResponder` in `SearchController` factory |

---

## Planning (docs only — not shipped code)

- [ITERATION_87.md](ITERATION_87.md) — Project Site Planner (Full CMS) + UX audit deferrals + optional theme static JS allow-list (`87k`–`87m`).

---

## Production deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.60 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

After deploy:

1. Hard refresh admin (**Ctrl+Shift+R**).
2. **Ctrl+Shift+K** → search `set` → settings/modules appear.
3. Optional: **Settings → Content** → enable **Article print**; verify print button on public article.
4. Bulk-select pages → toolbar shows **N of M selected**.
5. Maintainer: **Origin Panel** → verify running version and It.86 deploy badge **live** when instance ≥ `2.1.0-beta.60`.

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green on tag commit
- [ ] `SearchControllerTest::testAdminSearchResolvesUserFromSessionWithoutRequestAttribute`
- [ ] `BulkActionBar.test.tsx`, `AdminCommandPalette.test.tsx`
- [ ] `CatalogDeployStatusResolverTest`, `ProjectCatalogMergeServiceTest`
- [ ] `./scripts/validate-project-catalog.sh`

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-60)
- [ADMIN_GUIDE.md](user/ADMIN_GUIDE.md) — command palette, print, bulk counter
- [CONTENT_API.md](architecture/CONTENT_API.md) — admin search auth note
