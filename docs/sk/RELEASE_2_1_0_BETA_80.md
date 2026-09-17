# Release `v2.1.0-beta.80` — Plugin capabilities + Docker git verzia

> **Dátum:** 2026-09-17  
> **Tag:** `v2.1.0-beta.80`  
> **Typ:** It.89a bezpečnosť rozšírení · produkčná verzia z git tagu

---

## Jedna veta

ZIP import pluginu vyžaduje allow-list `capabilities[]`; Docker PHP-FPM vie prečítať tag checkoutu (žiadny starý fallback z „dubious ownership“).

---

## Deploy (produkcia)

Po tomto tagu **rebuild PHP image** (nový FPM `clear_env = no`). Potom:

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.80 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

---

## Súvisiace

[CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md) · anglický [RELEASE_2_1_0_BETA_80.md](../en/RELEASE_2_1_0_BETA_80.md)
