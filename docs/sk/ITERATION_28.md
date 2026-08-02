---
title: Iterácia 28 – Platformové bulk operácie
description: Historický záznam zdieľaného bulk selection UI a per-item batch API výsledkov
icon: material/history
---

# Iterácia 28 – Platformové bulk operácie

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.16 |
| Typ záznamu | historická platformová iterácia |

## Cieľ

Extrahovať bulk výber z Media Library do zdieľaného hooku/UI a zaviesť konzistentný backendový batch výsledok s čiastočnými úspechmi a chybami.

## Zdieľaný kontrakt

`useBulkSelection`, `BulkActionBar` a FE typ `BulkBatchResult` dopĺňal backendový `Http/Support/BulkBatchResult.php`. Batch odpoveď uvádza `processed`, `succeeded`, `failed` a per-item `results[]`; operácia teda nemusí byť all-or-nothing.

Frontend musí po batch operácii zobraziť aj partial failure, nie iba všeobecný zelený toast.

## Pokryté moduly

| Modul | Bulk akcie |
|---|---|
| Media | delete selected |
| Pages/Articles | publish, draft, archive, delete |
| Trash | restore selected |
| Comments | approve, reject, delete |
| Users | delete s guardom self/last-SUPER_ADMIN |
| Backups | restore/delete/import ZIP/download/verify SHA-256 |

## Bezpečnostné hranice

Bulk mutácie musia opätovne overovať permission pre každú operáciu a rešpektovať 2FA/CSRF. Backup ZIP import je neskôr krytý Zip-Slip hardeningom [ISS-088](ISSUES.md#iss-088). Per-item výsledok nesmie obísť audit trail.

## Odložené a overenie

Bulk SEO patch, messages mark-read a generický registry zostali mimo 2.0.16. Testy pokrývali aggregator, content/trash controllery a selection hook. Release: [2.0.16](../CHANGELOG.md#release-2-0-16).

