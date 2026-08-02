---
title: Iterácia 12 – Blueprint a Schema Engine
description: Historický záznam flat-file definícií typov obsahu, dynamickej validácie a formulárov
icon: material/history
---

# Iterácia 12 – Blueprint a Schema Engine

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené; prvý samostatný release nie je v zdroji určený |
| Release / obdobie | po 2.0.27, pred aktuálnym beta snapshotom |
| Typ záznamu | historická architektonická iterácia |

## Cieľ

Definovať typy obsahu a polia pomocou flat-file blueprintov, používať ich pri backend validácii a generovať admin formuláre bez databázy.

## Dodaný rozsah

| Prvok | Stav |
|---|---|
| `data/blueprints/{type}.json` | autoritatívna definícia custom typu |
| Built-in `page` a `article` | fallback definície |
| Typy polí | text, textarea, markdown, slug, select, bool, number, email, url, media, datetime |
| Validácia | `DynamicValidator` → zdieľaný `Validator` |
| Content save | `ContentController` validuje payload cez blueprint |
| Admin API/UI | list/show/save/validate/delete custom typov + `DynamicForm` preview |

## Hranice v1

Zdroj potvrdzuje JSON blueprints a admin-defined schémy; YAML a plne verejné custom content types neboli v v1. Samostatný release tag pre prvé dodanie zdroj neuvádza, preto ho dokumentácia nevymýšľa.

## Súvislosti

Blueprints stavajú na validácii z It.4 a storage vrstve. Aktuálne pravidlá pre SSOT, schema migrácie a atomic writes majú prednosť podľa [STORAGE.md](architecture/STORAGE.md).

