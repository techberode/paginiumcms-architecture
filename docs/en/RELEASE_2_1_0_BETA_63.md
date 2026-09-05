# Release `v2.1.0-beta.63` — Frontend npm security + auth/setup hardening

> **Date:** 2026-09-05  
> **Tag:** `v2.1.0-beta.63`  
> **Type:** Security / deps hygiene + stabilization fixes

---

## One-line summary

Synchronized **TipTap 3.31.1** family + lockfile refresh clears **npm audit** (Tiptap GHSA + transitive `fast-uri`). Fixes **login info-panel contrast**, **setup orphan recovery** after test purge, and **stale user index** blocking `user:create`.

---

## What shipped

| Area | Change |
|------|--------|
| npm | `@tiptap/*` → **3.31.1**, `npm audit` → 0 (moderate+) |
| Auth UI | Login/register info panel text readable on Mono Zinc and similar schemes |
| Setup | `/setup` when `general.installed=true` but **zero users** (PHPUnit/dev recovery) |
| Tests | `purgeAllUsersForTesting()` rebuilds `data/index/users.json` |
| Docs | **DEPLOY.md §12** — deploy permissions bootstrap |

---

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.63 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Rebuilds `frontend/dist` on server — admin bundle only; no PHP schema changes.

**Dev recovery after local PHPUnit:** if login fails with empty `user:list` but `user:create` reports username exists, run gate once (index rebuild is automatic on next purge) or open `/setup` when no accounts remain.

---

## Verification

- [ ] `npm audit --audit-level=moderate` → 0 in `frontend/`
- [ ] Login info panel — bullets readable on Mono Zinc dark
- [ ] `./scripts/iteration-gate.sh` green on tag
- [ ] Article editor (TipTap) smoke — create/edit rich text in admin

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-63)
- [ISS-160](ISSUES.md#iss-160) — orphan user index + setup recovery
- [ISS-081](ISSUES.md#iss-081) — TipTap family bump rule
