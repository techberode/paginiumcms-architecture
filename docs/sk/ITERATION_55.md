---
title: Iterácia 55 – Tiptap JSON úložisko a media upload
description: Štruktúrované Tiptap JSON v flat-file storage, serverový render a editor media upload.
icon: material/history
---

# Iterácia 55 – Tiptap JSON úložisko a media upload

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.43 |
| Typ záznamu | historický content-format delivery record |

## Cieľ

Ukladať WYSIWYG dokumenty ako štruktúrovaný `tiptap_json`, validovať ich podľa profilu a renderovať sanitizované HTML pre public výstup bez regresie Markdownu.

## Rozsah a výsledok

`TiptapHtmlRenderer`, `ContentBodyRenderer` a `JsonContentStorage` zabezpečili round-trip JSON → disk → HTML cache. API používalo `contentFormat`, frontend ukladal `getJSON()` a media paste/drop/file-picker smeroval do existujúceho DAM endpointu.

Public read vracal cached HTML pre Tiptap záznamy; legacy Markdown a raw HTML vetvy ostali kompatibilné.

## Architektonické a bezpečnostné hranice

Node whitelist musí kopírovať profilové capabilities, unsafe URL sa odmietajú a script/iframe nodes nie sú povolené. Cached HTML je derived data a musí sa regenerovať zo SSOT pri poškodení alebo zmene renderer verzie.

## Overenie a súvisiace záznamy

Release: [2.0.43](../CHANGELOG.md#release-2-0-43). Súčasťou dodávky bol auth retry fix [ISS-042](ISSUES.md#iss-042).

## Aktuálna interpretácia

It.55 je autoritatívny základ structured editor storage. Layout AST, shortcodes a AI nesmú vytvoriť druhý nekonzistentný content body model.
