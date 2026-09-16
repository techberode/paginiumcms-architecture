# Release `v2.1.0-beta.77` — Admin chrome + denné aplikácie

> **Dátum:** 15. september 2026  
> **Tag:** `v2.1.0-beta.77`  
> **Typ:** It.93 Wave 1–3 — chrome, tímy, účet, udalosti, časovač, widgety, katalógové menu

Kanónické poznámky (EN): [../en/RELEASE_2_1_0_BETA_77.md](../en/RELEASE_2_1_0_BETA_77.md)

---

## Zhrnutie

Admin dostane chrome (tokeny, horné vs bočné menu, dok Použiť/Uložiť) a denné aplikácie na **našich** flat-file dátach: **tímy**, **účet**, **udalosti**, **časovač**, **widgety** a **katalógové bočné menu**, ktoré sa nebijú s hlavičkou.

**Nie v tomto release:** **93l** support desk, **93m** domain IMAP.

---

## Deploy

```bash
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.77 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

---

## Related

[ITERATION_93.md](ITERATION_93.md) · [CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md)
