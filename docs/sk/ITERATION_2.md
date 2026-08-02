---
title: Iterácia 2 – Automatické ukladanie, verzie a detekcia konfliktov
description: Historický záznam revízií obsahu, draftov, autosave a optimistického riadenia súbehu
icon: material/history
---

# Iterácia 2 – Automatické ukladanie, verzie a detekcia konfliktov

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.6 – základ jadra |
| Typ záznamu | historická základná iterácia |

## Cieľ

Doplniť zámky z It.1 o optimistické riadenie súbehu (OCC), automaticky ukladané drafty a históriu verzií integrovanú do editora.

## Backend

| Komponent | Úloha |
|---|---|
| `ContentRevision.php` | Deterministický fingerprint nad obsahom a kanonickým front matterom |
| `ContentConflictException.php` | HTTP `409` so serverovou verziou v `conflict` kontexte |
| `Core/Drafts/` | Drafty v `data/drafts/{type}/{slug}.json` |
| `DraftController.php` | `PUT/GET/DELETE /api/drafts/{type}/{slug}` |
| `ContentController` | Kontrola `baseRevision`, commit message a nová revízia |
| `VersionManager.php` | Hydratácia a história verzií |

## Frontend a kontrakt

`versions.ts`, `drafts.ts`, `useAutoSave.ts`, `DiffViewer.tsx` a `MarkdownEditor.tsx` vytvorili jeden editovací lifecycle. Historický interval autosave bol 60 sekúnd a neskôr sa stal nastaviteľným cez Settings Engine.

Revision fingerprint používa SHA-1 ako **identifikátor zmeny pre OCC**, nie ako kryptografický podpis integrity. Aktuálny kontrakt je v [VERSIONING.md](architecture/VERSIONING.md).

## Overenie a ďalší krok

Testy pokrývali revízie, draft manager/controller a `VersionManager`. Samotné automatické alebo manuálne zlúčenie konfliktu doplnila [Iterácia 3](ITERATION_3.md).

