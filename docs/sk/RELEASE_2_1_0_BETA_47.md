# Release `v2.1.0-beta.47` — Redakčný workflow (It.81a–81e)

> **Dátum:** 2026-08-15  
> **Tag:** `v2.1.0-beta.47`  
> **Iterácia:** It.81a–81e  
> **Špecifikácia (EN):** [../en/ITERATION_81.md](../en/ITERATION_81.md)

---

## Zhrnutie jednou vetou

Redaktori dostávajú **duplikáciu ako koncept**, **hromadné tagy**, **uložené pohľady**, **redakčný kalendár** a **flag zastaralého obsahu** — na existujúcom flat-file indexe, bez SQL.

---

## Novinky v admin UI

| Slice | Kde | Čo |
|-------|-----|-----|
| **81a** | Zoznam stránok/článkov → **Duplikovať** | Kópia ako draft; vyčistené schedule polia |
| **81b** | Bulk panel → **Tagy…** | Pridať / odstrániť / nahradiť tagy |
| **81c** | Chipy nad zoznamom | Uložené filtre v `localStorage` (max 5 vlastných) |
| **81d** | **Redakčný kalendár** | `/platform/editorial-calendar` — nie cron plánovač |
| **81e** | Badge, filter **Len zastaralé**, dashboard | Nastavenie `staleReviewMonths`; **Označiť ako skontrolované** |

---

## Nastavenia

**Nastavenia → Obsah → Prah zastarávajúceho obsahu (mesiace)** — default 12, `0` = vypnuté.

---

## Deploy na produkciu

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.47 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Po deployi: hard refresh, overte duplikáciu, kalendár, dashboard widget so zastaralým obsahom.

---

## Odkazy

- [CHANGELOG — beta.47](../../CHANGELOG.md#release-2-1-0-beta-47)
- [RELEASE EN](../en/RELEASE_2_1_0_BETA_47.md)
- Zostáva: **81f** knižnica snippetov
