---
title: Iterácia 54 – Modulárny Markdown/WYSIWYG editor
description: Editor profiles riadiace toolbar, Tiptap extensions a backendovú validáciu obsahu.
icon: material/history
---

# Iterácia 54 – Modulárny Markdown/WYSIWYG editor

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.42 |
| Typ záznamu | historický editor delivery record |

## Cieľ

Dodať dual editor stack Markdown + Tiptap, ktorého schopnosti riadia profily pre company, blog, minimal a developer použitie.

## Rozsah a výsledok

`EditorProfileService` a `EditorContentValidator` definovali povolené bloky; settings vybrali default profil pre stránky a články. Front matter ukladal `editorProfile` a `editorMode`; frontend dynamicky zobrazoval toolbar a Tiptap extensions podľa capabilities.

Disallowed paste/import sa mal zastaviť vo FE aj na serveri. Profilový switch nemal vyžadovať reload.

## Architektonické a bezpečnostné hranice

Frontendové skrytie tlačidla nie je bezpečnostná kontrola; backend musí validovať každý save. Developer profil stále nesmie automaticky povoliť script/iframe alebo raw executable content.

## Overenie a súvisiace záznamy

Release: [2.0.42](../CHANGELOG.md#release-2-0-42). Neskorší blog-profile problém s fenced code blockom bol opravený v [ISS-079](ISSUES.md#iss-079).

## Aktuálna interpretácia

It.54 je dokončený profilový základ pre It.55 a It.60. Aktuálny extension kontrakt vyžaduje rovnakú serverovú allow-list validáciu pre custom komponenty.
