---
title: Iterácia 67 – Ne-dôveryhodné surfaces a defense-in-depth
description: Plán dokončenia bezpečnosti shortcode/theme/module authoringu, CSP hygiene a hostile fixtures.
icon: material/history
---

# Iterácia 67 – Ne-dôveryhodné surfaces a defense-in-depth

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Základ dodaný (2.1.0-beta.27+) |
| Release / obdobie | 2.1.0-beta.27 — ďalšie 58d/Monaco UI follow-up |
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

Základ It.67 je v produkcii: Code Policy Engine, untrusted ZIP/import parity, CSP hardening (`SecurityMiddleware`), hostile fixture pack. Origin panel má It.67 označenú ako **shipped**; zostávajúci rozsah je follow-up 58d (Monaco UI, verejný shortcode render) — nie nové číslo iterácie.
