# Release `v2.1.0-beta.81` — Kontrola aktualizácie + webhook ack

> **Dátum:** 2026-09-17  
> **Tag:** `v2.1.0-beta.81`  
> **Typ:** hotfix — 403 pri Check remote · GitHub retry pri vypnutom auto-deployi

---

## Jedna veta

Kontrola novej verzie je GET (bez CSRF); ping z GitHub release pri vypnutom auto-deployi vráti 200 ignored, nie 403.

---

## Deploy (produkcia)

Rebuild PHP image len ak ešte nie je z **beta.80**. Potom:

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.81 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

SUPER_ADMIN → Aktualizácia systému → **Skontrolovať remote**.

---

## Súvisiace

[CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md) · anglický [RELEASE_2_1_0_BETA_81.md](../en/RELEASE_2_1_0_BETA_81.md)
