---
title: Iterácia 26 – Media preview lightbox a binárny hotfix
description: Historický záznam Fit/1:1 náhľadu médií a opravy poškodzovania binárnych uploadov
icon: material/history
---

# Iterácia 26 – Media preview lightbox a binárny hotfix

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené vrátane hotfixu |
| Release / obdobie | 2.0.14 |
| Typ záznamu | historická media UX a storage oprava |

## Cieľ

Dodať lightbox s režimami Fit a natívne 1:1, metadátami a navigáciou v aktuálnom filtri a následne opraviť dve príčiny nefunkčných náhľadov.

## Frontendový rozsah

`MediaPreviewLightbox.tsx` podporoval zatvorenie cez backdrop/X/Esc, prev/next cez šípky, zobrazenie natural dimensions, veľkosť a MIME. `MediaManager` otváral Fit kliknutím na thumbnail a 1:1 cez samostatnú akciu. PDF a neobrázkové súbory sa otvárali mimo lightboxu.

## Hotfix 2.0.14

| Vrstva | Oprava |
|---|---|
| Binary I/O | `writeBinary()` / `readBinary()` bez UTF-8 normalizácie |
| Validácia | prípona + MIME + magic bytes pre JPEG, PNG, GIF, WebP, SVG a PDF |
| API | autentifikované `GET /api/media/file/{path}` a zoznam formátov |
| URL | admin same-origin `/api/media/file/...`, public embed `/storage/...` |
| UI | `accept` podľa API a fallback správanie |

## Prevádzková poznámka

Zdroj požadoval zmazať a znovu nahrať médiá uploadnuté pred 2.0.14, pretože binárne dáta mohli byť nenávratne poškodené normalizáciou. Takýto krok musí nasledovať až po zálohe a overení, že ide o staré poškodené súbory.

## Overenie a hranice

Testy pokrývali lightbox, MediaFormats, repository/controller a URL helpers. Thumbnail generation, zoom/pan a širšie editor preview zostali mimo scope. Release: [2.0.14](../../CHANGELOG.md#release-2-0-14).

