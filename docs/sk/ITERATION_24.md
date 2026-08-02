---
title: Iterácia 24 – Full DAM v1
description: Historický záznam folder-aware Media Library, sidecar metadata, bulk operácií a stock katalógu
icon: material/history
---

# Iterácia 24 – Full DAM v1

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.12 |
| Typ záznamu | historická media/DAM iterácia |

## Cieľ

Rozšíriť pôvodný Media Manager na flat-file Digital Asset Manager s vnorenými priečinkami, sidecar metadátami, bulk delete a nastaveniami povolených MIME typov a veľkosti uploadu.

## Storage a API

| Prvok | Implementácia |
|---|---|
| Asset registry | `media/registry.json` |
| Folder registry | `media/folders.json` + `.paginium-folder` marker |
| Sidecar | `{path}.meta.json` s `altText`, `title`, `folder`, `updatedAt` |
| API | list/folders/create-folder/upload/PATCH metadata/bulk-delete/single delete |
| Settings | `media.allowedMimeTypes`, `media.maxUploadSizeKb` |

## Frontend a stock katalóg

`MediaManager` dostal breadcrumbs, folder cards, select/bulk delete a editáciu title/alt. Stock knižnica nebola SQL databáza: išlo o flat-file `stock-images.json` s témami a externými URL. Backend importoval binárny obrázok do Media Library, namiesto uloženia obyčajného externého odkazu.

## Aktuálne bezpečnostné hranice

Historický záznam predchádza neskoršiemu SSRF hardeningu. Každé sťahovanie stock assetu dnes musí prejsť outbound URL validáciou, redirect revalidáciou, limitmi veľkosti a bezpečným MIME/magic-byte overením. Sidecar metadata zostávajú autoritatívne iba pre popis assetu; fyzické binárne úložisko môže v Hybrid Engine profile používať local alebo S3 driver podľa [STORAGE.md](architecture/STORAGE.md).

## Odložené a overenie

Asset locking, thumbnails, bulk move a rozšírené caption/tags UI zostali mimo v1. Testy pokrývali repository, controller, stock catalog/importer a frontend folder navigation. Release: [2.0.12](../CHANGELOG.md#release-2-0-12).

