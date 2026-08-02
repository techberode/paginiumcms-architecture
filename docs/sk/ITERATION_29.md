---
title: Iterácia 29 – Cron plánovač a flat-file job queue
description: Historický záznam job registry, scheduler runnera, manuálnej queue a admin Plánovača
icon: material/history
---

# Iterácia 29 – Cron plánovač a flat-file job queue

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Implementované; neskôr produkčne hardenované |
| Release / obdobie | 2.0.18 |
| Typ záznamu | historická scheduler/worker iterácia |

## Cieľ

Zjednotiť plánované úlohy v flat-file registry, poskytnúť CLI runner, voliteľnú manuálnu queue, históriu behov a admin stránku `/scheduler`.

## Architektúra a stores

| Súbor | Úloha |
|---|---|
| `data/jobs/registry.json` | definície jobov, CRON, handler, enabled |
| `data/jobs/runs.json` | append-oriented história behov |
| `data/jobs/queue.json` | manuálne/forced run požiadavky |

`ScheduledJobRunner` načítal registry, vyhodnotil CRON a delegoval cez `JobHandlerRegistry`. Seed jobs boli backup a monitoring.

## CLI, API a UI

Historický odporúčaný cron spúšťal `scheduler:run` a `worker:process` každú minútu. Admin API pokrývalo list/detail/create/update/delete/run-now/run-due/process-queue. UI umožňovalo toggle, edit CRON, run now a monitoring force report.

## Neskorší hardening

Aktuálny deploy kontrakt vyžaduje `flock`, rovnakú PHP/runtime identitu ako web, bezpečné storage oprávnenia, redigované logy a idempotentné joby. Produkčný `POST .../run` problém je evidovaný v [ISS-094](ISSUES.md#iss-094); privilege escalation cez system-deploy job bol opravený v [ISS-104](ISSUES.md#iss-104).

Worker nie je implicitný SUPER_ADMIN. Identita pôvodného používateľa a autorizovaný action scope musia byť súčasťou job payloadu.

## Overenie a ďalší rozsah

Testy pokrývali CRON evaluator a scheduled runner. Redis queue bola budúci smer a dnes patrí do capability-based Hybrid Engine kontraktu, nie do povinného základu. Release: [2.0.18](../CHANGELOG.md#release-2-0-18).

