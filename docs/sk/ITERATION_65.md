---
title: Iterácia 65 – Galéria funkcií
description: Flat-file galéria admin screenshotov s public grid/slider/modal UX a layout-block kontraktom.
icon: material/history
---

# Iterácia 65 – Galéria funkcií

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Fázy 1–3 dokončené; ops seed zostal |
| Release / obdobie | 2.1.0-beta.21 + neskoršia Phase 3 na main |
| Typ záznamu | historický module delivery record |

## Cieľ

Dodať samostatný Gallery modul pre screenshoty PaginiumCMS s titulkom, popisom, tagom, poradím a publikovaním na home alebo vlastnej `/features` route.

## Rozsah a výsledok

Flat-file model používal `data/gallery/index.json` a `items/{id}.json`, pričom Media ostalo source of binaries. Admin `/gallery` poskytol CRUD, reorder, media picker, settings a live preview; public API vracal iba published položky.

Phase 1 dodala grid a modal, Phase 2 slider/hero-strip, autoplay, effect presets, tags a dynamic single-segment route, Phase 3 deep links, Ken Burns, export/import a kontrakt `featureGallery` pre It.58. Implementácia zvolila CSS scroll-snap bez novej carousel dependency.

## Architektonické a bezpečnostné hranice

Mutácie vyžadujú `gallery:manage`, public read filtruje status, captions sú sanitizované a media paths prechádzajú existing storage allow-list. Modal musí mať focus trap, ESC, keyboard navigation a reduced-motion. JSON import nesmie importovať binárne súbory ani arbitrary paths.

## Overenie a súvisiace záznamy

Phase 1–2 sú viazané na [v2.1.0-beta.21](../../CHANGELOG.md#release-2-1-0-beta-21); zdroj uvádza Phase 3 „on main (next release)“, preto jej prvý tag z tohto dokumentu nie je potvrdený. Zostal ops krok naplniť 3–5 screenshotov na prod/demo.

## Aktuálna interpretácia

It.65 je samostatný modul a SSOT. It.58 `featureGallery` blok smie iba renderovať ten istý public API/component; nesmie vytvoriť druhé gallery úložisko.
