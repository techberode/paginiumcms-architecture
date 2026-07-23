# Produkcia — cron a plánovač úloh

> **Wave 6 / It.29** · Posledná aktualizácia: júl 2026 · verzia **2.0.58**

PaginiumCMS **nepoužíva SQL** — scheduled publish, zálohy a monitoring bežia cez **flat-file job registry** (`data/jobs/registry.json`) a CLI `scheduler:run`.

Bez cronu na serveri **nefungujú automaticky:**

| Job ID | Handler | Cron (default) | Čo robí |
|--------|---------|----------------|---------|
| `content-scheduled-publish` | `content.scheduled_publish` | `* * * * *` | It.59 — publikuje obsah so stavom `scheduled` |
| `monitoring-pipeline` | `monitoring.pipeline` | `* * * * *` | Monitoring reporty + scan logov (It.7) |
| `backup-scheduled` | `backup.scheduled` | `0 2 * * *` | Nočná záloha CMS |

Admin UI: **Plánovač** (`/scheduler`) — zap/vyp jobov, úprava CRON, manuálny beh.

---

## Odporúčaný crontab (produkcia)

Jeden riadok každú minútu — spúšťa due joby z registry **a** spracuje frontu:

```cron
* * * * * cd /var/www/paginiumcms && /usr/bin/php backend/bin/console scheduler:run >> /var/log/paginium-scheduler.log 2>&1
* * * * * cd /var/www/paginiumcms && /usr/bin/php backend/bin/console worker:process >> /var/log/paginium-worker.log 2>&1
```

**Dôležité:**

- `cd` musí smerovať na **koreň repozitára** (kde je `backend/` a `vendor/`).
- PHP worker a web (nginx/FPM) musia vidieť **rovnaké** `backend/storage/` — flat-file dáta.
- Použi absolútnu cestu k `php` (`which php`).

---

## Overenie po nasadení

```bash
# Health
curl -s https://your-cms.example/api/health

# Diagnostika flat-file (index, orphans)
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --fix   # oprava indexu/cache

# Simulácia cron (manuálne)
php backend/bin/console scheduler:run
php backend/bin/console worker:process

# Legacy CLI (stále podporované, ale scheduler je preferovaný)
php backend/bin/console backup:run-schedule
php backend/bin/console monitoring:run-schedule
```

**Scheduled publish smoke:** v editore nastav článok na publikáciu o +2 min → po minúte spusti `scheduler:run` → stav `published`, verejný web zobrazí obsah.

---

## Demo režim (voliteľné)

Len na `demo.paginiumcms.com` — **nie** na zákazníckej inštancii:

```bash
# Ak DEMO_MODE=true v .env
php backend/bin/console demo:reset-if-due
```

Pridaj do cronu alebo nechaj v registry cez admin Plánovač.

---

## Riešenie problémov

| Symptóm | Príčina | Riešenie |
|---------|---------|----------|
| Scheduled publish nikdy neprebehne | Chýba cron | Crontab vyššie + over `scheduler:run` |
| Job beží, obsah sa nezmení | Iný `storage/` path ako web | Zjednotiť mount / symlink |
| `registry.json` prázdna | Prvý beh | Otvor admin **Plánovač** alebo spusti API — seed defaults |
| Backup len manuálne | `backup-scheduled` vypnutý | Zapni v `/scheduler` |
| Logy plné chýb | PHP cesta / permissions | Skontroluj `>> log` a práva na `storage/` |

---

## Súvisiace

- [ITERATION_29.md](../ITERATION_29.md) — job queue architektúra
- [ITERATION_59.md](../ITERATION_59.md) — scheduled publish
- [user/INSTALLATION.md](../user/INSTALLATION.md) — inštalácia + cron krok
- [developer/BETA_INFRA.md](../developer/BETA_INFRA.md) — beta checklist pre maintainerov
- [NGINX_API.md](./NGINX_API.md) — produkčný nginx
