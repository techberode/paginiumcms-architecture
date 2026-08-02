---
title: Iterácia 53 – Plynulé SPA načítanie a admin navigácia
description: React Query cache, skeletony, scroll restoration a odstránenie tvrdých reloadov.
icon: material/history
---

# Iterácia 53 – Plynulé SPA načítanie a admin navigácia

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.39 |
| Typ záznamu | historický frontend delivery record |

## Cieľ

Odstrániť zbytočné full-page reloady a prázdne blokujúce spinnery pri prechode medzi admin modulmi.

## Rozsah a výsledok

Dodávka zaviedla `QueryClientProvider`, stabilné query keys, cache pre dashboard/listy/media/extensions/counts, skeleton komponenty a reset admin scroll kontajnera. Version restore v editore prešiel na `loadContent()` namiesto `window.location.reload()`.

Session refresh ostal event-based a verejný `/login` prešiel cez React Router. Debug tracker meral trvanie route transition.

## Architektonické a bezpečnostné hranice

Cache nesmie obchádzať autorizáciu ani držať staré citlivé odpovede po logout/role change. Query invalidácia musí byť viazaná na mutácie a session boundary; hard redirect nemá nahrádzať riadený auth stav.

## Overenie a súvisiace záznamy

Release: [2.0.39](../CHANGELOG.md#release-2-0-39). Zdroj odkazuje na session a hard-redirect incidenty [ISS-025](ISSUES.md#iss-025) a [ISS-033](ISSUES.md#iss-033).

## Aktuálna interpretácia

It.53 je frontendový základ pre ďalšie buildery a extensions. Runtime plugin UI však stále podlieha build-time hranici Vite, ktorú SPA cache nerieši.
