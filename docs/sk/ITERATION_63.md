---
title: Iterácia 63 – Admin systémová aktualizácia
description: SUPER_ADMIN-only production code deploy z GitHub tagu s privileged job policy a webhookom.
icon: material/history
---

# Iterácia 63 – Admin systémová aktualizácia

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ MVP + v2/v3 dodané; v4 UX odložené |
| Release / obdobie | 2.1.0-beta.18 |
| Typ záznamu | historický privileged-deploy record |

## Cieľ

Umožniť SUPER_ADMIN spustiť produkčný code deploy z admin UI bez bežného SSH workflow, pričom demo a customer bundle mali byť podľa zdroja explicitne vylúčené.

## Rozsah a výsledok

Dodávka zahŕňala status/check/run API, `system:deploy --ref`, allow-listed deploy script, encrypted GitHub settings, compare/release notes UI a privileged `system-deploy` job. v2 pridala one-click latest-tag a commit compare; v3 release-published webhook s HMAC, idempotentným enqueue a maintenance/WAF výnimkami.

v4 Grav-like onboarding, pre-update backup prompt a human-readable progress boli presunuté do It.25 pred Final 1.0.

## Architektonické a bezpečnostné hranice

Code deploy je oddelený od content Git sync. Ref musí byť allow-listed, job nesmie byť spustiteľný cez generický ADMIN jobs endpoint a webhook potrebuje HMAC, replay/idempotency a outbound guard. Deploy script je jediný privileged execution bridge; žiadny arbitrary shell argument.

## Overenie a súvisiace záznamy

Release: [v2.1.0-beta.18](../CHANGELOG.md#release-2-1-0-beta-18). Privilege bypass bol uzavretý v [ISS-104](ISSUES.md#iss-104). Zdroj v related docs uvádza ISS-105, no aktuálny register priraďuje ISS-105 GeoIP; relevantný GitHub outbound nález je [ISS-108](ISSUES.md#iss-108).

## Aktuálna interpretácia

It.63 je technický deploy engine, nie kompletný end-user updater. Current release engineering stále vyžaduje backup, clean evidence, health/smoke a rollback; klik v UI nesmie tieto gates obísť.
