---
title: Iterácia 59 – Odložená publikácia
description: Plánovanie publikácie stránok a článkov cez existujúci scheduler a timezone kontrakt.
icon: material/history
---

# Iterácia 59 – Odložená publikácia

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané |
| Release / obdobie | 2.0.53 |
| Typ záznamu | historický scheduler/content record |

## Cieľ

Umožniť uložiť obsah ako `scheduled` s `scheduledAt` a automaticky ho publikovať v definovanom čase cez existujúcu job registry.

## Rozsah a výsledok

Editor dostal date-time picker, admin zoznamy scheduled filter/stĺpec a backend handler `content.scheduled_publish`. Job bol idempotentný, rešpektoval OTP publish approval a používal `AppTimezone` vrátane DST.

Scheduled obsah ostal pred due časom skrytý vo verejnom API; po úspechu sa prepol na published a odstránil schedule marker.

## Architektonické a bezpečnostné hranice

Scheduler identity nesmie obísť content permissions alebo OTP policy. Čas sa ukladá v jednoznačnom ISO formáte a interpretuje cez aplikačnú timezone; duplicate run musí byť bezpečný.

## Overenie a súvisiace záznamy

Release: [2.0.53](../CHANGELOG.md#release-2-0-53). Produkčné outcome a permission hardening plánovača bolo neskôr spracované v [It.62](ITERATION_62.md).

## Aktuálna interpretácia

It.59 je dokončená v1 pre publish; recurring publish a scheduled unpublish/archive zostávajú mimo pôvodného rozsahu.
