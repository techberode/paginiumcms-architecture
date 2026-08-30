# Release `v2.1.0-beta.61` — Origin Panel backend popisy katalógu (hotfix)

> **Dátum:** 2026-08-30 · **Tag:** `v2.1.0-beta.61`  
> **Kanónický release (EN):** [../en/RELEASE_2_1_0_BETA_61.md](../en/RELEASE_2_1_0_BETA_61.md)

## Problém

Na produkcii Origin Panel zobrazoval surové kľúče (`origin.catalog.it87`) namiesto popisov. Backend/manifest bol nový, admin JS bundle starý.

## Oprava

Backend rieši popisy cez `OriginCatalogLabelResolver` + `backend/lang/{sk,en}/origin.php`. API posiela `titleLabel` / `labelLabel` / `summaryLabel`; frontend ich preferuje pred `t(key)`.

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.61 ./scripts/deploy-instance-update.sh
```

Po deployi: Origin Panel → iterácie majú čitateľné názvy. Odporúčané aj `npm run build:prod` pre plnú FE i18n synchronizáciu.
