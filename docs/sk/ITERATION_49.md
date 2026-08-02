---
title: Iterácia 49 – Zjednotená cache vrstva
description: Produktová cache vrstva file/Redis s auto-detekciou a bezpečným fallbackom, absorbovaná do It.69.
icon: material/history
---

# Iterácia 49 – Zjednotená cache vrstva

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ⏳ Plánované; absorbované do It.69 |
| Release / obdobie | bez samostatného release |
| Typ záznamu | historický cache návrh |

## Cieľ

Zjednotiť request memory, file cache a voliteľný Redis do jedného hosting-aware kontraktu a sprístupniť driver, diagnostiku a purge v administrácii.

## Rozsah a výsledok

Navrhované režimy boli `auto`, `file`, `redis` a `memory`. `CacheCapabilityProbe` mal overiť extension/client, ping, timeout a profil hostingu; `CacheDriverFactory` mal zostaviť reťazec a `CacheChecker` reportovať driver, hit rate a latenciu.

Mimo content cache návrh pokrýval queue, rate-limit, session a edit locky. Flat-file obsah ostával SSOT a Redis mal vždy file fallback.

## Architektonické a bezpečnostné hranice

Auto mode nesmie rozhodovať iba podľa dostupnosti Redis portu. Potrebuje úspešný capability test, validnú konfiguráciu, namespace/prefix a overenú degradačnú cestu. Cache obsah nesmie byť autoritatívny ani obsahovať secrets.

## Overenie a súvisiace záznamy

Zdroj explicitne uvádza, že implementácia je sledovaná v [It.69](ITERATION_69.md) a It.49 zahŕňa celý rozsah It.45. Samostatný release preto neexistuje.

## Aktuálna interpretácia

It.49 je detailný produktový referenčný návrh. Kanonická implementácia a Definition of Done patria do It.69 Hybrid Engine wave.
