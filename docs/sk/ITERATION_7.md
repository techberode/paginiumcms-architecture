---
title: Iterácia 7 – Plánované monitoring reporty a incidenty z logov
description: Historický záznam plánovaných reportov, log scanneru, schedulera a notifikačného UI
icon: material/history
---

# Iterácia 7 – Plánované monitoring reporty a incidenty z logov

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.17 |
| Typ záznamu | historická prevádzková iterácia |

## Cieľ

Pridať hodinové, denné a týždenné reporty, HTML digest, alerty z `ERROR/WARNING` logov a CLI príkaz vhodný pre hosting cron.

## Backend

| Komponent | Úloha |
|---|---|
| `MonitoringReportBuilder` | Textový a HTML report |
| `MonitoringReportScheduler` | Due logika a odoslanie |
| `LogIncidentScanner` | Sken nových aplikačných logov |
| `MonitoringScheduler` | Orchestrácia reportu a incident scan |
| `SchedulerStateStore` | `data/scheduler-state.json` |
| `IncidentNotifier::notifyViaConnectorDetailed()` | Konkrétny konektor a preflight dôvody |

## CLI, API a UI

Historický CLI príkaz:

```bash
php backend/bin/console monitoring:run-schedule
```

Admin API poskytlo overview, schedule, manuálne odoslanie reportu, simuláciu cron behu a test konektora. `/notifications` zobrazuje blockers, odoslanie reportu a simuláciu plánovača.

## Produkčný cron – aktuálna interpretácia

Pôvodný záznam odporúčal spájať backup a monitoring v jednom minútovom riadku. Aktuálny deploy kontrakt preferuje wrapper alebo kontajnerový príkaz s `flock`, jednoznačnou runtime identitou, redigovaným logom a zachovaným exit kódom. Pozri [CRON.md](deploy/CRON.md).

## Rozsah reportu

Digest zahŕňal návštevnosť, IP, top stránky/články/referrery, health a flat-file počty. Host-level CPU, RAM, disk, Docker, SSH a mail queue neboli súčasťou tejto iterácie.

