---
title: Iterácia 17e – API barrel a CONTRIBUTING
description: Historický záznam Wave 5e MVP pre API↔FE zákon a CI kontrolu exportov
icon: material/history
---

# Iterácia 17e – API barrel a CONTRIBUTING

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané |
| Release / obdobie | 2.0.55 |
| Typ záznamu | historická doplnková vlna 5e |

## Cieľ

Dodať minimálne vynútiteľný základ API↔FE zákona: dokumentovaný contribution workflow, úplný API barrel a automatickú CI kontrolu.

## Dodaný rozsah

| Prvok | Súbor / výsledok |
|---|---|
| Contributing | `docs/developer/CONTRIBUTING.md` |
| API barrel | `frontend/src/api/index.ts` – historicky 39 modulov a 16 klientov |
| Lint | `frontend/scripts/lint-api-barrel.mjs` |
| npm | `npm run lint:api-barrel` |
| CI | samostatný frontend job step |

## Overenie

```bash
cd frontend
npm run type-check
npm run lint:api-barrel
npm test -- --run
```

## Mimo MVP

Scaffold wizard, migrácia všetkých raw klientov a úplný API inventory zostali mimo tejto vlny. Pozri hlavný [It.17](ITERATION_17.md).

