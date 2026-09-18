# Release `v2.1.0-beta.82` — Plugin capability runtime (It.89b–e)

> **Date:** 2026-09-18  
> **Tag:** `v2.1.0-beta.82`  
> **Type:** It.89 extension security — broker, safe hooks, scanner, CLI

---

## One-line summary

Plugins now run through a **capability broker** and **SafeHookRunner**; import scan catches more indirection patterns; **`plugin:create`** / **`plugin:scan`** match the CMS ZIP pipeline.

**Prerequisite:** **beta.80+** (89a manifest + capabilities on import). **beta.81** (system update GET check) is compatible.

---

## What shipped

| Area | Change |
|------|--------|
| **It.89b** | `PluginCapabilityBroker` + `PluginRuntimeContext` injects scoped APIs into hook handlers (`content:read` / `content:write` / `content:write:own`, `media:read`). Undeclared use → `PluginCapabilityDeniedException`. Legacy one-arg handlers still work. |
| **It.89c** | `SafeHookRunner` wraps hook execution (`catch (Throwable)`). Per-invocation time/memory quota. Fatal `Error` or 3 consecutive failures → plugin **auto-disabled** + security audit + Extensions admin notice. Capability use audited via `LogSanitizer`. |
| **It.89d** | `SecurityScanner` + import path: `$fn()`, `$$`, `extract()`, `array_map('system', …)`; manifest vs `content()` / `media()` / outbound HTTP heuristic. Pack in `scripts/security-regression.sh`. |
| **It.89e** | CLI `plugin:create`, `plugin:scan` (aliases `paginium:plugin:*`). Same `PluginScanService` as ZIP import. Scaffold writes manifest + `Hooks.php`; does not auto-enable. |
| **Reference** | `hello-widget` uses broker for `content:read`. |
| **Admin** | Extensions manager shows health/disable notices; security audit includes plugin capability events. |

Spec: [ITERATION_89.md](ITERATION_89.md). Policy: [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md).

---

## Deploy (production)

No mandatory PHP image rebuild (same as **beta.81**). Standard deploy:

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.82 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.82`.

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.82 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] Import a plugin ZIP with valid `capabilities[]` → enable → hook runs.
- [ ] Import ZIP calling `content()` without `content:read` in manifest → **422**.
- [ ] Platform → Extensions: disabled plugin shows notice after forced failure (dev only).
- [ ] `php backend/bin/console plugin:scan hello-widget` → exit 0.
- [ ] `./scripts/security-regression.sh` → OK.

---

## Developer CLI

```bash
php backend/bin/console plugin:create my-plugin --capabilities=content:read,admin-ui:editor-block
php backend/bin/console plugin:scan my-plugin
php backend/bin/console plugin:scan /path/to/plugin --json
```

See [TESTING.md](developer/TESTING.md).
