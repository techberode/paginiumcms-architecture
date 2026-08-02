---
title: Iterácia 60 – Vlastné komponenty editora
description: Pluginom registrované Markdown/WYSIWYG bloky s profilovou allow-list validáciou.
icon: material/history
---

# Iterácia 60 – Vlastné komponenty editora

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Implementované; presný prvý release nepotvrdený |
| Release / obdobie | bez jednoznačného tagu v zdroji |
| Typ záznamu | historický extension/editor record |

## Cieľ

Umožniť ADMIN/EDITOR rolám rozšíriť Markdown a Tiptap editor o vlastné komponenty z plugin manifestu a riadiť ich podľa editor profilu.

## Rozsah a výsledok

`EditorComponentRegistry` spojil `plugin.json` declarations s profilmi; Markdown používal `:::component-id`, WYSIWYG dynamické Tiptap Node extensions a Settings maticu profil × komponent. Backend odmietal neznámy alebo nepovolený blok.

Referenčný `hello-widget` demonštroval MD aj WYSIWYG variant a toolbar sa zobrazil iba pri povolení.

## Architektonické a bezpečnostné hranice

Plugin manifest ani frontend UI nesmie byť jediný gate. Save path potrebuje serverovú registry/profile validáciu a extension import policy. Nový React komponent z ZIP-u nemusí byť runtime načítateľný bez frontend build/redeploy.

## Overenie a súvisiace záznamy

Zdroj označuje iteráciu ako implementovanú, ale neuvádza release. Integrácia referenčného pluginu neskôr spôsobila duplicate-class fatal [ISS-075](ISSUES.md#iss-075), ktorý bol opravený v 2.0.54.

## Aktuálna interpretácia

It.60 je implementovaný registry a validation základ. Aktuálny extension code policy a Vite build-time hranica z It.06 majú prednosť pred zjednodušeným tvrdením „plugin ZIP pridá React UI za behu“.
