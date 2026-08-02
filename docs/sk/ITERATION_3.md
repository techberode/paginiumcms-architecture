---
title: Iterácia 3 – Riešenie konfliktov
description: Historický záznam trojcestného zlúčenia, manuálneho resolvera a registra konfliktov
icon: material/history
---

# Iterácia 3 – Riešenie konfliktov

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.6 – základ jadra |
| Typ záznamu | historická základná iterácia |

## Cieľ

Doplniť odpoveď `409` o praktický workflow: automatický line-based diff3 merge, manuálne rozhodovanie po hunkoch a administrátorský register konfliktov.

## Implementácia

| Vrstva | Komponent |
|---|---|
| Backend model | `ConflictRecord.php` |
| Backend store | `ConflictLogger.php` → `data/conflicts.json`, limit 200, `flock` |
| HTTP | `GET/DELETE /api/admin/conflicts` |
| Merge | `src/utils/merge3.ts` – line-based diff3 s LCS anchor bodmi |
| UI | `ConflictResolver.tsx` – Mine / Server / Both / Manual |
| Integrácia | `MarkdownEditor.tsx` – auto-merge po `409`, resolver pri nečistom výsledku |

## UX a audit

Používateľ dostal rozdielne toast stavy pre úspešný auto-merge, konflikt a zrušenie. `ConflictsPanel` na dashboarde čítal ten istý flat-file register. Register je diagnostická/auditná pomôcka, nie náhrada za verzie obsahu.

## Testy

`merge3.test.ts` pokrýval 13 scenárov, doplnené boli testy `ConflictResolver` a HTTP controllera. Formát `409 conflict` je zdokumentovaný v [API_CONTRACT.md](architecture/API_CONTRACT.md).

