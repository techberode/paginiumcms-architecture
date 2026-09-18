# Release `v2.1.0-beta.82` — Plugin capability runtime (It.89b–e)

> **Dátum:** 2026-09-18  
> **Tag:** `v2.1.0-beta.82`  
> **EN:** [../en/RELEASE_2_1_0_BETA_82.md](../en/RELEASE_2_1_0_BETA_82.md)

## Zhrnutie

Dokončenie **It.89**: broker pre hooky, `SafeHookRunner` (izolácia + auto-disable), rozšírený scanner, CLI **`plugin:create`** / **`plugin:scan`**. Vyžaduje **beta.80** (manifest capabilities pri importe).

## Deploy (prod)

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.82 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Overenie

- Health → `2.1.0-beta.82`
- Import pluginu bez deklarovanej capability → 422
- `php backend/bin/console plugin:scan hello-widget`
