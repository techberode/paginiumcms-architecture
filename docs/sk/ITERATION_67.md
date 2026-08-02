---
title: Iterácia 67 – Ne-dôveryhodné surfaces a defense-in-depth
description: Plán dokončenia bezpečnosti shortcode/theme/module authoringu, CSP hygiene a hostile fixtures.
icon: material/history
---

# Iterácia 67 – Ne-dôveryhodné surfaces a defense-in-depth

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ⏳ Plánované |
| Release / obdobie | bez samostatného release |
| Typ záznamu | historický security backlog record |

## Cieľ

Dokončiť defense-in-depth pre ne-dôveryhodné authoring surfaces bez zvyšovania ceny anonymného renderu.

## Rozsah a výsledok

67a má zapojiť `ShortcodeDefinitionPolicy → validateUntrusted → write → registry update` na každom Monaco/API save a rovnaké validators v preview. 67b má preniesť plugin import parity na theme/module ZIP. 67c rieši CSP inventory a dependency disposition, 67d hostile fixture corpus a jednotný security regression command.

Výslovne mimo rozsahu ostáva pixelový iframe builder, runtime user PHP a ML WAF na public traffic.

## Architektonické a bezpečnostné hranice

Hostile shortcode/plugin fixture nesmie byť zapísaný ani aktivovaný. Plugin-registered shortcode nemá výnimku, preview nesmie mať bypass a public render používa iba AST/sanitized output. CSP zmeny musia byť opatrné a residual `style-src 'unsafe-inline'` môže byť zdokumentovaný risk, nie potichu ignorovaný.

## Overenie a súvisiace záznamy

Zdroj je otvorený plán bez release. Závisí od [It.66](ITERATION_66.md), otvorenej 58d vetvy v [It.58](ITERATION_58.md) a plugin základov It.15. Dependency review odkazuje na [ISS-089](ISSUES.md#iss-089).

## Aktuálna interpretácia

It.67 zostáva aktívnym bezpečnostným backlogom. Jej časti sa majú dodávať spolu s príslušným produktovým surface, najmä 58d, aby nevznikol funkčný Monaco save bez policy wiring.
