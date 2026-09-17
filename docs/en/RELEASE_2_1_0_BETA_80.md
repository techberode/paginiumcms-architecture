# Release `v2.1.0-beta.80` — Plugin capabilities + Docker git version

> **Date:** 2026-09-17  
> **Tag:** `v2.1.0-beta.80`  
> **Type:** It.89a extension security · production `/api/health` version from git

---

## One-line summary

ZIP plugin import now requires a capability allow-list in `plugin.json`; Docker PHP-FPM can read the checkout tag for AppVersion (no more stale `beta.78` from dubious git ownership).

---

## What shipped

| Area | Change |
|------|--------|
| **It.89a** | `PluginCapabilityCatalog` + `manifestVersion: 1` + `capabilities[]` on import/enable. Unknown capability → HTTP 422. `hello-widget` declares `content:read` and `admin-ui:editor-block`. Runtime broker is **89b** (not this tag). |
| **Git version** | `GitCli` adds `safe.directory` on PHP git calls. Compose `GIT_CONFIG_*` + FPM `clear_env = no` so workers see env. **Rebuild the PHP image.** |
| **Planning docs** | [ITERATION_94.md](ITERATION_94.md), [ITERATION_95.md](ITERATION_95.md), **58f-h** canvas slice in [ITERATION_58f.md](ITERATION_58f.md). |

---

## Deploy (production)

Rebuild PHP after this tag (new FPM pool conf). Then:

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.80 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.80`.

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.80 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] `/api/health` version is `2.1.0-beta.80` (not a fallback older constant)
- [ ] Extensions → ZIP with missing/unknown `capabilities` is rejected (422 toast)
- [ ] `hello-widget` still lists and can enable
- [ ] After PHP image rebuild, `git describe` as www-data no longer fails on dubious ownership

---

## Related

[ITERATION_89.md](ITERATION_89.md) · [CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md) · [DEPLOY.md](../deploy/DEPLOY.md)
