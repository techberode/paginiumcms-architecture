# Release `v2.1.0-beta.62` — It.25 setup wizard and update UX

> **Date:** 2026-09-03  
> **Tag:** `v2.1.0-beta.62`  
> **Type:** Feature (stable-release blocker — basic phase)

---

## One-line summary

Fresh PaginiumCMS instances can be onboarded in the browser at **`/setup`**; SUPER_ADMIN gets a dashboard **update available** banner and a **backup prompt** before deploy.

---

## What shipped (It.25 §5.1 basic phase)

| ID | Deliverable | Status |
|----|-------------|--------|
| R1 | `/setup` wizard — admin, site/locale, `installed: true`, dashboard redirect | ✅ |
| R2 | Browser-first install (CLI optional via `first-run.sh`) | ✅ |
| R3 | Dashboard update banner + backup prompt before deploy | ✅ |
| R4 | CSRF-exempt setup POST only; SUPER_ADMIN update UX; demo hidden | ✅ |
| R5 | INSTALLATION + FIRST_STEPS SK/EN updated | ✅ |
| R6 | PHPUnit + `scripts/smoke-it25.sh` | ✅ |

**Deferred:** optional stock-image seed, git/deploy checklist wizard step, automated rollback UI.

---

## Production deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.62 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

After deploy on an **existing** instance: no `/setup` redirect (users already exist). For a **new** instance: open `/` → `/setup`.

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green on tag commit
- [ ] `./scripts/smoke-it25.sh` (status endpoint; `FRESH_INSTALL=1` on empty users dir)
- [ ] Clean clone → `/setup` → preflight green → dashboard
- [ ] `./scripts/smoke-it25.sh` includes `GET /api/setup/preflight` (since `beta.65`)

**Extended in `v2.1.0-beta.65`:** server preflight + infra step — [RELEASE_2_1_0_BETA_65.md](RELEASE_2_1_0_BETA_65.md).

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-62)
- [STABILIZATION_PHASE.md](STABILIZATION_PHASE.md) §5.1
- [RELEASE_2_1_0_BETA_65.md](RELEASE_2_1_0_BETA_65.md) — M1+ server preflight (follow-up)
- [RELEASE_2_1_0_BETA_61.md](RELEASE_2_1_0_BETA_61.md) — previous release
