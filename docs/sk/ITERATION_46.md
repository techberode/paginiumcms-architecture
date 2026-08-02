---
title: Iterácia 46 – Agent hostiteľských metrík
description: Plán ľahkého agenta pre CPU, RAM, disk, uptime a Docker metriky mimo PHP requestu.
icon: material/history
---

# Iterácia 46 – Agent hostiteľských metrík

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ⏳ Plánované |
| Release / obdobie | bez samostatného release |
| Typ záznamu | historický monitoring návrh |

## Cieľ

Rozšíriť aplikačný monitoring o hostiteľské metriky, ktoré bežný health check CMS neposkytuje: uptime, load, CPU, RAM, disk a stav kontajnerov.

## Rozsah a výsledok

Agent mal bežať ako host cron alebo sidecar každých 1–5 minút a ukladať validovaný snapshot do `data/metrics/host-latest.json`. `MonitoringReportBuilder` a `GET /api/admin/metrics/host` mali z rovnakého snapshotu napájať report aj dashboard.

Navrhnuté nastavenia: `hostMetricsEnabled`, maximálny vek snapshotu a ingest token. Zdroj uvádzal Bash/PHP CLI variant bez potreby Redis.

## Architektonické a bezpečnostné hranice

Zber nesmie prebiehať v anonymnom HTTP requeste. Ingest má byť localhost/Docker-network only alebo chránený tokenom/HMAC a IP allowlistom. Payload má obsahovať agregované čísla, nie environment, príkazové riadky alebo secrets.

## Overenie a súvisiace záznamy

Zdroj definuje iba návrh a testovacie fixtures; neobsahuje release ani dôkaz nasadenia. Nadväzuje na aplikačné reporty z [It.7](ITERATION_7.md) a system-overview backlog.

## Aktuálna interpretácia

It.46 zostáva samostatnou plánovanou schopnosťou. Nemá sa zamieňať s externým Prometheus/node-exporter monitoringom: tento návrh opisuje CMS-ingested snapshot a jeho zobrazenie v PaginiumCMS.
