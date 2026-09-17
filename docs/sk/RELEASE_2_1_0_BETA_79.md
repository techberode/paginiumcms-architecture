# Release `v2.1.0-beta.79` — Pošta, 58f bloky, editor workspace

> **Dátum:** 2026-09-17  
> **Tag:** `v2.1.0-beta.79`  
> **Typ:** It.93m-5 mail UX · It.58f vizuálne bloky · celoobrazovkový editor · šifrovanie at-rest

---

## Jedna veta

Doménová pošta dostáva štítky, vysypanie lokálneho koša, vylepšené písanie a podpisy s avatarom; stránky 58f hero/gallery render; editor môže ísť na celú obrazovku; tajomstvá bez `APP_KEY` sa neukladajú plaintext.

---

## Deploy (produkcia)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.79 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

---

## Súvisiace

[CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md) · anglický [RELEASE_2_1_0_BETA_79.md](../en/RELEASE_2_1_0_BETA_79.md)
