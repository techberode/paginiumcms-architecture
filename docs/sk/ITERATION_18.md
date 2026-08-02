---
title: Iterácia 18 – Lokalizácia admin UI a verejného webu
description: Historický záznam modulárneho SK/EN i18n systému, migrácie adminu, auditov a verejného webu
icon: material/history
---

# Iterácia 18 – Lokalizácia admin UI a verejného webu

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané vo vlnách; plugin i18n zostáva budúci rozsah |
| Release / obdobie | 2.0.44, 2.0.46, 2.0.47, 2.0.49, 2.0.50, hotfix 2.0.51 |
| Typ záznamu | historická lokalizačná iterácia |

## Cieľ

Odstrániť hardcoded slovenské texty z adminu a verejného webu, zaviesť backend `Lang.php`, modulárne katalógy SK/EN a jednotný frontend `I18nProvider/useI18n` kontrakt.

## Backend

`Support/Lang.php`, `backend/lang/{sk,en}`, `LocaleMiddleware`, `general.language` a plugin-ready `Lang::addPath()` vytvorili serverový základ. Audit správy boli v 2.0.49 re-lokalizované cez `AuditMessageFormatter` ([ISS-061](ISSUES.md#iss-061)).

## Frontend vlny

| Vlna | Rozsah |
|---|---|
| 18a | sidebar a header |
| 18b | list toolbar, pagination, content manager |
| 18c | settings UI a cache panel |
| 18d | translation editor a catalog API |
| 18e | media, navigation, dashboard |
| 18f | comments, messages, backup, trash, logs, platform a editor |
| Wave 5c | public web, auth flow, helpers |

## Incidenty a hotfixy

[ISS-059](ISSUES.md#iss-059) zaviedol spoločný test provider; [ISS-060](ISSUES.md#iss-060) opravil SK copy-paste v EN settings; [ISS-062](ISSUES.md#iss-062) dokončil public i18n. Pôvodný text označoval ISS-063–070 ako pending; register potvrdzuje ich opravu v **2.0.51**.

## Historické testovacie počty

Záznam uvádzal 210 Vitest testov v čase danej vlny. Tento počet je historický snapshot a nesmie sa zamieňať s aktuálnym 21-krokovým gate-om.

## Zostávajúci rozsah

Pluginové katalógy pod extension namespace, optional dynamic i18n endpoint a registry podporovaných locales neboli v zdroji označené ako dokončené. Neskoršia Hybrid Engine lokalizačná vetva It.73/76/77 je samostatný content-localization projekt.

