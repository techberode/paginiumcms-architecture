# Release `v2.1.0-beta.64` — Admin deploy readiness + dashboard update banner

> **Date:** 2026-09-05  
> **Tag:** `v2.1.0-beta.64`  
> **Type:** Ops / admin UX (system update)

---

## One-line summary

Admin deploy becomes **actionable from the dashboard**: auto remote version check, **Deploy {tag}** when ready, and explicit **blockers** when `stackDir` / settings are missing (ISS-161).

---

## What shipped

| Area | Change |
|------|--------|
| Settings | `stackDir`, `backendPort` in System update (fallback to env) |
| API | `deploy_readiness.blockers[]` on status + check |
| Dashboard | Auto-check banner, deploy CTA, configure link |
| Platform | Deploy blockers panel; buttons disabled until ready |
| Docs | DEPLOY.md §12.5 |

---

## Production setup (one-time)

**Settings → System update:**

- Enable admin deploy ✅  
- Allow deploy from semver tags ✅  
- **Docker stack directory:** `/var/lib/docker/compose/paginiumcms`  
- **Backend health port:** `8089`  
- GitHub owner / repo / token filled  

Then: Dashboard → banner shows update → **Deploy v2.1.0-beta.64** (when a newer tag exists).

---

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.64 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

---

## Verification

- [ ] SUPER_ADMIN dashboard — banner auto-checks GitHub
- [ ] With stackDir set — **Deploy {tag}** enabled when update available
- [ ] `./scripts/iteration-gate.sh` green on tag

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-64)
- [ISS-161](ISSUES.md#iss-161)
- [DEPLOY.md §12.5](../deploy/DEPLOY.md)
