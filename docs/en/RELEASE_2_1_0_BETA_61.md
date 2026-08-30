# Release `v2.1.0-beta.61` — Origin Panel backend catalog labels (hotfix)

> **Date:** 2026-08-30  
> **Tag:** `v2.1.0-beta.61`  
> **Type:** Hotfix (It.82e follow-up)

---

## One-line summary

Production **Origin Panel** shows human-readable iteration/item labels again — backend resolves catalog strings from `backend/lang/{locale}/origin.php` even when the admin JS bundle is behind the manifest.

---

## Problem

After **beta.60**, maintainers on production saw raw keys such as `origin.catalog.it86` and `origin.catalog.it87_plan_schema` in the Origin roadmap. Dev/test showed correct Slovak/English descriptions because the local admin bundle included the new i18n keys; production had updated PHP/manifest but a stale `dist/` build.

Phase labels (`Plánované`, `Čiastočné`) still worked — only nested **catalog** keys were missing from the old bundle.

---

## Fix

| Layer | Change |
|-------|--------|
| **Backend** | `OriginCatalogLabelResolver` loads `backend/lang/{sk,en}/origin.php` |
| **API** | Merged catalog returns `titleLabel`, `labelLabel`, `summaryLabel` |
| **Frontend** | `OriginPanelView` uses API label when present, else `t(key)` |
| **Validation** | `validate-project-catalog.sh` checks backend lang catalog keys |

---

## Production deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.61 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

After deploy:

1. Hard refresh admin (**Ctrl+Shift+R**).
2. **Origin Panel** → roadmap iterations show titles like **It.87 Plánovač projektu + UX audit**, not raw keys.
3. Recommended: ensure deploy runs **`npm run build:prod`** so FE i18n stays in sync for future keys.

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green on tag commit
- [ ] `OriginCatalogLabelResolverTest`
- [ ] `ProjectCatalogMergeServiceTest`
- [ ] `./scripts/validate-project-catalog.sh`

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-61)
- [RELEASE_2_1_0_BETA_60.md](RELEASE_2_1_0_BETA_60.md) — previous release
