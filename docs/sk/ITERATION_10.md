---
title: Iterácia 10 – XML feedy, sitemap a robots.txt
description: Historický záznam RSS, sitemap, robots.txt, cache a public discoverability
icon: material/history
---

# Iterácia 10 – XML feedy, sitemap a robots.txt

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | core v 2.0.10 (It.22); polish v neskorších releasoch |
| Typ záznamu | historická funkčná iterácia |

## Cieľ a časová stopa

Scope bol pôvodne naplánovaný ako It.10, jadro bolo reálne dodané v It.22/release 2.0.10 a následný polish doplnil cache, `robots.txt` a discoverability linky. Pôvodné označenie „Unreleased polish“ už nepredstavuje aktuálny stav.

## Dodaný rozsah

| Route | Content-Type | Historické TTL |
|---|---|---|
| `GET /feed.xml` | `application/rss+xml` | 300 s |
| `GET /sitemap.xml` | `application/xml` | 300 s |
| `GET /robots.txt` | `text/plain` | 300 s |

`FeedGenerator`, `SitemapGenerator`, `RobotsTxtGenerator`, `ContentCacheService`, controller a routes generujú výstup z publikovaného flat-file indexu. Zmena obsahu invaliduje generáciu feedov.

## Frontend a nastavenia

`feeds` settings riadia metadata a inclusion pravidlá. Public layout pridáva RSS alternate a sitemap link; Vite dev/preview proxy pozná všetky tri public routes.

## Overenie

PHPUnit pokrýva generátory a HTTP smoke; Postman kolekcia obsahuje Public Feeds folder. Nginx musí feed routes spracovať pred SPA fallbackom.

