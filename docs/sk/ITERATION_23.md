---
title: Iterácia 23 – SEO Meta Engine
description: Historický záznam serverového SEO payloadu, verejného endpointu a správy head tagov v Reacte
icon: material/history
---

# Iterácia 23 – SEO Meta Engine

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.11 |
| Typ záznamu | historická SEO iterácia |

## Cieľ

Generovať title, description, canonical, robots, Open Graph, Twitter Card a JSON-LD pre verejný React web z front matteru a centrálnej SEO konfigurácie.

## Backend kontrakt

`SeoMetaBuilder` skladá payload z explicitných SEO polí, titulku, excerptu/tela a fallback nastavení. Verejný endpoint `GET /api/seo/{type}/{slug}` podporuje `page|article`; anonymný používateľ nesmie získať meta údaje draftu a dostane `404`.

| Pole | Typický zdroj |
|---|---|
| title | `seoTitle` / `metaTitle` alebo title template |
| description | SEO description, description, excerpt alebo body fallback |
| canonical | front matter alebo site URL + path |
| robots | per-content `noIndex` alebo default |
| structured data | `WebPage` alebo `Article` JSON-LD |

## Frontend

`useSeoMeta` načíta SEO payload podľa aktuálneho dokumentu a spravuje `document.title`, meta tags, canonical link, OG/Twitter polia a script `#paginium-json-ld`. Integrácia bola v `PublicSiteLayout`.

## Neskoršie rozšírenia

Globálny prepínač indexovania pribudol v [It.20](ITERATION_20.md). Admin editácia SEO polí, media alt/title a SEO health badge pribudli v [It.27](ITERATION_27.md). Kanonické ukladanie content polí je popísané v [CONTENT_API.md](architecture/CONTENT_API.md).

## Overenie

PHPUnit pokrýval builder a controller, Vitest hook. Release je evidovaný v [CHANGELOG 2.0.11](../../CHANGELOG.md#release-2-0-11).

