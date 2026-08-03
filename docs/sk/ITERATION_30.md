---
title: Iterácia 30 – Content admin polish, cache a responzívne zoznamy
description: Historický záznam opravy content cache, dvojrežimového editora a zdieľaného list toolbaru
icon: material/history
---

# Iterácia 30 – Content admin polish, cache a responzívne zoznamy

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Implementované |
| Release / obdobie | 2.0.20 · SEO doplnok 2.0.23 |
| Typ záznamu | historická content admin iterácia |

## Cieľ a priorita

Odstrániť kritický stav, keď cache ukladala PHP objekty a po refreshi mohla vyprázdniť tabuľku, a súčasne zlepšiť editor, SEO kontext a responzívne zoznamy.

## Backend

- cache ukladá serializované API polia, nie `Content` objekty;
- `null` sa neukladá ako platný cache hit;
- `ChainedDriver::increment()` číta generáciu z file vrstvy;
- index sa rebuildne, ak je prázdny a content súbory existujú;
- CLI `content:cache-purge [--reindex]`;
- failed-login email sa posiela až pri lockoute, s cooldownom a test exclusions.

## Frontend

Markdown a WYSIWYG režim s `contentFormat`, `ContentEditorShell`, SEO panel, media picker pre OG/preview image, zdieľaný `AdminListToolbar`, slovenské labely, mobile cards a nastavením riadené `itemsPerPage`.

## Prevádzkový postup

Zdroj uvádzal jednorazový `content:cache-purge --reindex` po deployi. Mazanie cache súborov je fallback, nie preferovaný postup. Reindex musí byť bezpečný, reprodukovateľný zo SSOT a nesmie mazať autoritatívny content.

## Overenie a aktuálna interpretácia

Test plan zahŕňal create/refresh, version/body load, prepínanie editorov, mobile cards a CLI purge. Later cache/index pravidlá sú konsolidované v [STORAGE.md](architecture/STORAGE.md) a [CONTENT_API.md](architecture/CONTENT_API.md). Release: [2.0.20](../../CHANGELOG.md#release-2-0-20).

