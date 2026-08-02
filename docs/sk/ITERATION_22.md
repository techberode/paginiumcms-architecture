---
title: Iterácia 22 – Prevádzkové dokončenie a verejná objaviteľnosť
description: Historický záznam koša, brute-force lockoutu, RSS, sitemap a same-origin deploy guardu
icon: material/history
---

# Iterácia 22 – Prevádzkové dokončenie a verejná objaviteľnosť

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené; jeden smoke bod zostal v zdroji neoznačený |
| Release / obdobie | 2.0.10 |
| Typ záznamu | historická prevádzková iterácia |

## Cieľ

Uzavrieť zostávajúce produkčné medzery po stabilizácii API: používateľské obnovenie z koša, ochranu loginu proti brute force a verejné RSS/sitemap endpointy.

## Dodaný rozsah

| Funkcia | Kontrakt |
|---|---|
| Kôš | typed `trashApi`, `/trash`, restore akcia a admin sidebar link |
| Lockout | počítadlá podľa IP a emailu vo flat-file store, nastavenia limitu a HTTP `429` |
| RSS | `GET /feed.xml`, RSS 2.0 z publikovaných článkov |
| Sitemap | `GET /sitemap.xml`, publikované stránky a články |
| Nastavenia | skupiny `feeds` a `security` |
| Deploy | same-origin production build/proxy guard |

## Dôležitá redakčná korekcia

V zdroji sú niektoré backend bloky označené slovom „planned“, hoci hlavička, scope a release stav ich označujú ako dokončené. Kanonický historický záznam preto rešpektuje release stav **Complete**, ale nevydáva neoznačený Newman smoke test za potvrdený: `GET /feed.xml` a `GET /sitemap.xml` v Newman kolekcii boli stále `⏳`.

## Bezpečnostné hranice

Login lockout musí používať bezpečné file locking a nesmie zdieľať stav medzi izolovanými testami. Feed a sitemap sú verejné, ale čítajú iba publikovaný obsah. Same-origin deploy ostáva preferovaný, aby session, CSRF a media URL nepoužívali nekonzistentné origins.

## Overenie a nadväznosť

Release je v [CHANGELOG 2.0.10](../CHANGELOG.md#release-2-0-10). Iterácia nadväzuje na [It.21](ITERATION_21.md) a pripravuje SEO meta engine v [It.23](ITERATION_23.md).

