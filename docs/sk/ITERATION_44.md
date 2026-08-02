---
title: Iterácia 44 – Filtre, sorting a stránkovanie verejného blogu
description: Historický záznam URL-synced filtrov admin zoznamov a serverového public blog API
icon: material/history
---

# Iterácia 44 – Filtre, sorting a stránkovanie verejného blogu

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené vo vlnách 44a–44d |
| Release / obdobie | 2.0.37 |
| Typ záznamu | historická content list/API iterácia |

## Cieľ

Zjednotiť filtre a sorting vo verejnom blogu aj admin zoznamoch, synchronizovať stav do URL a presunúť public article list na server-side pagination s tag metadata.

## Vlny dodávky

| Vlna | Rozsah |
|---|---|
| 44a | `blogItemsPerPage`, URL `page/tag/sort`, tag facets, sort dropdown a article prev/next |
| 44b | shared admin filter bar, Pages/Articles query params a `ui.openLinksInNewTab` |
| 44c | URL filtre pre Media, Comments a Trash |
| 44d | backend tag/author/date filtre, paginated public articles, `tags[]` a `total_published` meta |

## API a settings kontrakt

Public endpoint používal `GET /api/articles?page=&per_page=&tag=&sort=`. `content.blogItemsPerPage` bolo oddelené od admin `content.itemsPerPage`; `showReadingTime` riadil UI a `ui.openLinksInNewTab` preview/external link behavior.

URL je zdieľateľný stav filtrov, nie zdroj autorizácie. Backend musí znova validovať filter/sort parametre a public API vracia iba publikovaný obsah.

## Incident a overenie

Backend It.44d testy pre tag/date filtre boli neskôr opravené v [ISS-038](ISSUES.md#iss-038). Test gate zahŕňal repository/controller testy a `blogArticles` utility. Release: [2.0.37](../CHANGELOG.md#release-2-0-37).

## Aktuálna interpretácia

Zdroj označuje iteráciu ako úplnú bez remaining scope. Neskoršie cache/index capability musí zachovať rovnakú filtrovanú semantiku a meta payload; derived index nikdy nesmie nahradiť flat-file SSOT.

