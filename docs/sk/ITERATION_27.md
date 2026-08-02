---
title: Iterácia 27 – Režimy admin zoznamov a SEO metadata panel
description: Historický záznam list/list-preview/grid režimov, SEO editácie a health badgeov
icon: material/history
---

# Iterácia 27 – Režimy admin zoznamov a SEO metadata panel

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené; SEO audit endpoint zostal odložený |
| Release / obdobie | 2.0.15 · doplnky do 2.0.23 |
| Typ záznamu | historická admin UX/SEO iterácia |

## Cieľ

Zjednotiť tri režimy zobrazenia v Media, Pages a Articles a umožniť editorom spravovať SEO polia bez ručného editovania front matteru.

## View modes

| Režim | Použitie |
|---|---|
| `list` | tabuľka, sort a bulk výber |
| `list-preview` | riadok s thumbnailom/featured image |
| `preview` | karty alebo masonry grid |

`useAdminViewMode` ukladal preferenciu per modul do `localStorage`. Serverová používateľská synchronizácia bola iba voliteľný budúci smer.

## SEO workflow

`SeoMetadataPanel`, `MediaMetadataModal` a `SeoHealthBadge` sprístupnili title, meta description, OG image, robots, canonical, alt text a ďalšie sidecar/front-matter polia. Pravidlom bolo reuse `SeoMetaBuilder`, nie vytváranie druhej SEO logiky.

Zelený/žltý/červený health stav bol editor assist, nie bezpečnostná garancia ani automatický publish gate.

## Nedokončené časti

`GET /api/content/seo-audit` a jeho PHPUnit pokrytie zostali explicitne odložené. Rovnako neboli súčasťou It.27 full-page iframe preview, bulk SEO patch, AI meta descriptions ani server-side admin pagination.

## Overenie

Test plan zahŕňal perzistenciu režimu, featured-image fallback, uloženie SEO do API, missing-alt badge, filter SEO problémov a media metadata modal. Zdroj iterácie uvádza release `2.0.15`, ale dodaný changelog nemá pre túto verziu samostatný záznam ani stabilný release anchor.

