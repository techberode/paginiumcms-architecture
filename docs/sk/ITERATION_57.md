---
title: Iterácia 57 – Automatické tagy a generátor popisu
description: Deterministické návrhy tagov a SEO popisu bez povinného externého AI providera.
icon: material/history
---

# Iterácia 57 – Automatické tagy a generátor popisu

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané |
| Release / obdobie | 2.1.0-beta.4 |
| Typ záznamu | historický content-assist delivery record |

## Cieľ

Pomôcť editorovi navrhnúť tagy a krátky popis z titulku a tela, pričom používateľ musí výsledok vidieť a explicitne aplikovať.

## Rozsah a výsledok

`ContentMetaGenerator` stripol Markdown/Tiptap na plain text, použil SK/EN stopwords, frekvenciu a title overlap a vytvoril popis pri vetnej hranici. Endpoint `POST /api/admin/content/suggest-meta` prijal typ, title, body a format.

Sidebar poskytol „Navrhnúť tagy“ a „Generovať popis“ s preview diffom. Core v1 nemal sieťovú závislosť; AI hook bol iba budúce plugin rozšírenie.

## Architektonické a bezpečnostné hranice

Návrhy nesmú automaticky prepísať front matter. Endpoint potrebuje size limit a rate limit. Neskoršia AI vrstva musí zachovať Translate/Diff/Apply princíp a nové permission checky.

## Overenie a súvisiace záznamy

Release: [v2.1.0-beta.4](../../CHANGELOG.md#release-2-1-0-beta-4). Zdroj uvádza unit testy bez network callov a podporu Markdown aj Tiptap plain-text extraction.

## Aktuálna interpretácia

It.57 je hotový deterministický základ. It.75 AI agent ho môže rozšíriť, ale nesmie ho nahradiť povinnou cloud závislosťou ani autonómnym Apply/Publish.
