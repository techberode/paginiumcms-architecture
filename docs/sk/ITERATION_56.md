---
title: Iterácia 56 – Rozšírené položky navigácie
description: Popisy, Lucide/media ikony, thumbnails a hover preview v flat-file navigácii.
icon: material/history
---

# Iterácia 56 – Rozšírené položky navigácie

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.1.0-beta.5 |
| Typ záznamu | historický navigation UX record |

## Cieľ

Rozšíriť `navigation.json` o popis, ikonu alebo thumbnail a desktopový hover preview pri zachovaní mobile a reduced-motion správania.

## Rozsah a výsledok

Model pridal `description`, `iconType`, `iconValue`, `previewOnHover`, `previewScale` a `thumbnailSize`; globálne defaults boli v `navigationUi`. Admin dostal media picker a live row preview, public Navbar sekundárny text a voliteľný tooltip.

Legacy `icon` sa migroval pri načítaní a validátor kontroloval dĺžku, typ ikony a media path.

## Architektonické a bezpečnostné hranice

Media cesty musia prejsť existujúcou allow-list/ACL vrstvou. Hover nemôže byť jediný spôsob prístupu k informácii; mobile a keyboard UI potrebujú ekvivalent. `prefers-reduced-motion` sa rešpektuje.

## Overenie a súvisiace záznamy

Release: [v2.1.0-beta.5](../../CHANGELOG.md#release-2-1-0-beta-5). Dynamické Lucide lookup a desktop popis boli dotiahnuté v [ISS-085](ISSUES.md#iss-085).

## Aktuálna interpretácia

It.56 je dokončený navigation kontrakt. Budúce plugin icon packs musia rozširovať registry bezpečne a nesmú vkladať neoverené React komponenty z runtime ZIP-u.
