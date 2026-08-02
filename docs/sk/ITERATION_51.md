---
title: Iterácia 51 – Live preview, tagy a dátumové štítky
description: Fullscreen náhľad draftu, viditeľné tagy a jednotné štítky vytvorenia/úpravy.
icon: material/history
---

# Iterácia 51 – Live preview, tagy a dátumové štítky

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.32 |
| Typ záznamu | historický editor UX record |

## Cieľ

Umožniť editorovi vidieť celú stránku s Navbar a Footer pred publikovaním a sprístupniť tagy aj dátumové štítky priamo v blog UX.

## Rozsah a výsledok

`SitePreviewModal` podporil viac mierok a preview mód bez odchodu z modalu. `ArticleTagsEditor` synchronizoval `tags[]` so SEO hodnotami a `formatContentDateLabels()` zjednotil vytvorené/upravené dátumy na kartách aj detaile.

Táto iterácia doplnila editor UX, ale nenahradila pôvodne plánovaný shareable draft-token preview.

## Architektonické a bezpečnostné hranice

Preview musí používať rovnaký renderer, sanitizáciu a capability pravidlá ako verejný výstup; nesmie mať „preview-only“ bypass. Draft obsah sa nesmie sprístupniť anonymne bez samostatného bezpečného tokenového kontraktu.

## Overenie a súvisiace záznamy

Dodávka je evidovaná v [2.0.32](../CHANGELOG.md#release-2-0-32). Zdroj uvádza frontendové testy `SitePreviewModal` a `contentDates`.

## Aktuálna interpretácia

It.51 je hotový preview základ. Neskoršie layout a color-scheme preview z It.58 naň nadväzujú, ale zdieľaný draft URL ostáva samostatnou schopnosťou.
