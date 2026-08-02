---
title: Iterácia 48 – PHP front matter šablóny a statický/dynamický web
description: Plán šablón, metadata formátov a deterministického statického buildu verejného webu.
icon: material/history
---

# Iterácia 48 – PHP front matter šablóny a statický/dynamický web

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ⏳ Plánované |
| Release / obdobie | bez samostatného release |
| Typ záznamu | historický render/publish návrh |

## Cieľ

Umožniť vlastné verejné šablóny, YAML/JSON/INI metadata a prepínateľný `dynamic|static|hybrid` verejný režim, pričom admin ostáva React SPA.

## Rozsah a výsledok

Návrh používal `PhpTemplateRenderer`, `StaticSiteGenerator`, metadata resolver a joby `static:rebuild-page|all`. Publikovaný obsah mal zostať v Markdown/JSON SSOT a generované HTML v `storage/static/` bolo iba odvodený artefakt.

Admin mal ponúkať render mode, template editor v Monaco, rebuild progress a badge fresh/stale. Nginx mal servírovať statický strom a ponechať `/admin`, `/api` a interaktívne hybridné routy dynamické.

## Architektonické a bezpečnostné hranice

PHP šablóny môžu byť iba allow-listed artefakty; bez `eval`, bez prístupu k ľubovoľnému filesystemu a po syntax/policy kontrole. Static output nesmie byť spustiteľný ako PHP. Sanitizácia a CSP platia aj na build výstup.

## Overenie a súvisiace záznamy

Zdroj je plán, nie dôkaz implementácie. Neskorší plán dokumentácie požaduje navrhovať It.48 spolu s Git publish pipeline [It.70](ITERATION_70.md), aby nevznikli dve konkurenčné publish fronty.

## Aktuálna interpretácia

It.48 zostáva cieľovou statickou build vrstvou. Musí byť zosúladená s It.58 layout AST, It.69 cache invalidáciou a It.70 publish stavmi; „Save“, „Build“, „Git publish“ a „Deploy“ ostávajú samostatné kroky.
