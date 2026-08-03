---
title: Iterácia 64 – Sociálne odkazy vo footeri
description: Editovateľné social/project odkazy s platform allow-listom a verejným settings výrezom.
icon: material/history
---

# Iterácia 64 – Sociálne odkazy vo footeri

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané |
| Release / obdobie | 2.1.0-beta.19 |
| Typ záznamu | historický marketing UX record |

## Cieľ

Umožniť adminovi spravovať footer social/project odkazy bez hardcoded theme URL a zobraziť ich s platform ikonami.

## Rozsah a výsledok

Settings skupina `marketing` uložila master toggle a normalizovaný JSON zoznam. `SocialLinksNormalizer` validoval platform allow-list, URL a maximum 12 položiek; public settings exponovali iba `social.enabled` a `social.links[]`.

Admin panel podporoval add/edit/remove/reorder a footer mapoval platformy na Lucide ikony.

## Architektonické a bezpečnostné hranice

URL validácia musí blokovať `javascript:` a nebezpečné schémy; external link behavior musí používať bezpečné `rel` hodnoty. Email/RSS majú explicitne povolené schémy, nie všeobecný bypass.

## Overenie a súvisiace záznamy

Release: [v2.1.0-beta.19](../../CHANGELOG.md#release-2-1-0-beta-19). Zdroj uvádza PHPUnit/Vitest coverage a smoke cez public settings.

## Aktuálna interpretácia

It.64 je dokončený jednoduchý marketing modul. Budúce icon packs alebo custom networks musia rozšíriť allow-list/registry, nie akceptovať ľubovoľný component name alebo raw SVG.
