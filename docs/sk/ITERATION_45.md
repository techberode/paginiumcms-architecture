---
title: Iterácia 45 – Redis ako voliteľná infraštruktúrna vrstva
description: Historický návrh Redis drivera, zdieľanej cache, fronty a lockov; rozsah absorbovaný do It.69.
icon: material/history
---

# Iterácia 45 – Redis ako voliteľná infraštruktúrna vrstva

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ⏳ Plánované; absorbované do It.69 |
| Release / obdobie | bez samostatného release |
| Typ záznamu | historický infraštruktúrny návrh |

## Cieľ

Zaviesť Redis iba ako voliteľnú zdieľanú vrstvu pre viac PHP workerov alebo replík. Flat-file obsah, nastavenia a médiá mali zostať autoritatívnym zdrojom pravdy.

## Rozsah a výsledok

Zdroj navrhoval reťazec `MemoryDriver → RedisDriver → FileDriver`, voliteľný `RedisJobQueueStore`, TTL locky, zdieľaný rate-limit a až neskôr session handler. Feature flag `scheduler.queueDriver` mal prepínať `flatfile|redis`; admin mal vedieť vykonať ping a zobraziť aktívny queue driver.

Redis nebol blockerom pre single-node profil. Aktivácia mala zmysel až pri 2+ PHP procesoch/replikách, contention na queue/lock súboroch alebo merateľne drahej diskovej cache.

## Architektonické a bezpečnostné hranice

Redis nesmie byť primárnym content store ani podmienkou bootu. Výpadok musí skončiť overeným fallbackom na file driver, nie výpadkom CMS. Heslo/TLS patria do encrypted settings alebo environmentu a kľúče nesmú obsahovať secrets.

## Overenie a súvisiace záznamy

Pôvodný dokument neuvádza implementačný release. Detailný návrh bol neskôr zlúčený s produktovou vrstvou It.49 a kanonicky presunutý do [It.69](ITERATION_69.md). Súvisiaci pôvodný scheduler je v [It.29](ITERATION_29.md).

## Aktuálna interpretácia

It.45 je dnes referenčný návrh, nie samostatný aktívny backlog. Implementácia patrí do It.69 a musí používať capability probe; Redis sa nesmie „magicky“ zapnúť iba preto, že je dostupný socket.
