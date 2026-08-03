---
title: Iterácia 20 – Analytika, dashboard storage a riadenie indexovania
description: Historický záznam analytického dashboardu, storage štatistík a robots/noindex prepínača
icon: material/history
---

# Iterácia 20 – Analytika, dashboard storage a riadenie indexovania

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.46 · 2026-07-21 |
| Typ záznamu | historická analytická iterácia |

## Cieľ

Dodať samostatnú admin stránku Analytika, rozšíriť dashboard o rýchle odkazy a veľkosť flat-file obsahu a umožniť globálne vypnúť indexovanie verejného webu.

## Backend a frontend

| Vrstva | Hlavné prvky |
|---|---|
| Analytics backend | enrichment návštevy o zariadenie, browser, krajinu a mesto; periódy 7/14/30 dní; `geo`, `browsers`, `top_articles` |
| Dashboard backend | `ContentStorageStatsService` a blok `storage.content` |
| SEO | `seo.allowSearchIndexing`, `RobotsTxtGenerator` a globálny `noindex` v `SeoMetaBuilder` |
| Frontend | `/analytics`, KPI karty, päť tabov, dashboard quick links a disk panel |

## Bezpečnostné a dátové hranice

Analytika používa flat-file dáta a nepredstavuje nový databázový subsystém. Riadenie indexovania je globálny fallback; per-page robots pravidlá zostávajú samostatnou vrstvou. Geolokačné a IP údaje boli neskôr doplnené o maskovanie a consent-aware SPA beacon v [Iterácii 33](ITERATION_33.md).

## Overenie

Historický checklist vyžadoval funkčné KPI/tabs na `/analytics`, štyri quick links a disk panel na `/dashboard`, `Disallow: /` po vypnutí indexovania a verejný meta `noindex`. Release je evidovaný v [CHANGELOG 2.0.46](../../CHANGELOG.md#release-2-0-46).

## Súvisiace záznamy

Iterácia nadväzuje na [Iteráciu 19](ITERATION_19.md). Incidenty [ISS-046](ISSUES.md#iss-046) až [ISS-050](ISSUES.md#iss-050) patria do rovnakého release trainu, ale riešia audit/logging, nie samotné analytické výpočty.

