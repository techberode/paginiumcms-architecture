# Release `v2.1.0-beta.33` — Deploy pipeline & AppVersion z git

> **Dátum:** 2026-08-11  
> **Tag:** `v2.1.0-beta.33`  
> **Predchádzajúci:** [`v2.1.0-beta.32`](../../CHANGELOG.md#release-2-1-0-beta-32)

## Zhrnutie

Oprava admin deployu (npm v PHP kontajneri) a `/api/health` verzia z git tagu namiesto zastarelej konštanty.

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.33 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  ./scripts/deploy-instance-update.sh
```

Overenie: `curl -s http://127.0.0.1:8089/api/health | jq .data.version`
