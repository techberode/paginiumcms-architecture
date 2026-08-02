---
title: Iterácia 52 – Dashboard v2, kontakt a firemné údaje
description: Dodávka dashboard prehľadu, kontaktnej konfigurácie a editovateľných firemných údajov.
icon: material/history
---

# Iterácia 52 – Dashboard v2, kontakt a firemné údaje

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené vo vlnách 52a–52c |
| Release / obdobie | 2.0.35–2.0.36 |
| Typ záznamu | historický admin UX delivery record |

## Cieľ

Rozšíriť admin dashboard o aktivitu, flat-file štruktúru a KPI a zároveň pridať konfigurovateľné predmety kontaktu, firemné údaje a mapový embed.

## Rozsah a výsledok

It.52a rozšírila `/api/admin/dashboard/overview` o counts a storage údaje. It.52b pridala `contact.subjects` a voľný vlastný predmet. It.52c doplnila editovateľné firemné polia a Google Maps embed URL v public settings.

Verejný panel sa zobrazil iba pri povolení a vyplnených údajoch; dashboard používal existujúce counts a health údaje namiesto druhého zdroja.

## Architektonické a bezpečnostné hranice

Externý mapový embed musí byť allow-listovaný a validovaný; URL nie je dôveryhodný HTML fragment. Dashboard flat-file strom je prehľad, nie všeobecný file browser ani obchádzka Path ACL.

## Overenie a súvisiace záznamy

Kontakt bol dodaný v [2.0.35](../CHANGELOG.md#release-2-0-35), firemné údaje v [2.0.36](../CHANGELOG.md#release-2-0-36).

## Aktuálna interpretácia

It.52 je dokončený dashboard/contact základ. Neskorší system overview alebo host metrics nesmie duplikovať jeho counts a storage kontrakty.
