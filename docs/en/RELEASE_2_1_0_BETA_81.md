# Release `v2.1.0-beta.81` — System update check + webhook ack

> **Date:** 2026-09-17  
> **Tag:** `v2.1.0-beta.81`  
> **Type:** hotfix — admin version check 403 · GitHub webhook retries when auto-deploy is off

---

## One-line summary

Checking for a new CMS version is a GET (no CSRF); a GitHub release ping is acknowledged with 200 when webhook auto-deploy is disabled, so logs stop showing false 403s.

---

## What shipped

| Area | Change |
|------|--------|
| **Check remote** | `GET /api/admin/system/update/check` (POST kept). SUPER_ADMIN still required. Toast shows the API error text on failure. **Dashboard UX (beta.90+):** one automatic compare per browser session on first banner mount; see [DEPLOY.md §12.5](deploy/DEPLOY.md#125-admin-ui-deploy-platform--system-update--dashboard-banner). |
| **GitHub webhook** | `webhookDeployEnabled=false` → **200** `{ ignored: true, reason: "webhook_disabled" }` instead of 403. Enable the setting only if you want auto-deploy on *Release published*. |
| **Logs** | `http_access` 4xx lines append JSON `error` or plain WAF body. |

This does **not** implement It.89b (capability broker).

---

## Deploy (production)

No PHP image rebuild for this tag if **beta.80** already rebuilt FPM. If you are still on **beta.79** or older, rebuild PHP (GitCli / `clear_env = no`) and prefer this tag so check+webhook land together.

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.81 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.81`.

Then as SUPER_ADMIN: Platform → System update → **Check remote**. Expect `v2.1.0-beta.81` as current after this deploy (or `update available` if health still shows an older fallback).

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.81 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] `/api/health` version is `2.1.0-beta.81`
- [ ] SUPER_ADMIN **Check remote** returns 200 (not 403); banner shows current or update available
- [ ] GitHub → webhook recent delivery for this release is **200** (ignored) unless auto-deploy is on
- [ ] A 4xx in Admin → Logs includes the error sentence, not only “Zakázané”

---

## Related

[CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md) · [RELEASE_2_1_0_BETA_80.md](RELEASE_2_1_0_BETA_80.md)
