---
title: Iterácia 42 – Počty položiek a ovládanie admin zoznamov
description: Historický záznam role-aware počtov, list toolbaru, pagination a trash bulk operácií
icon: material/history
---

# Iterácia 42 – Počty položiek a ovládanie admin zoznamov

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | release neurčený v zdroji |
| Typ záznamu | historická admin list UX iterácia |

## Cieľ

Nahradiť klientsky odhad sidebar badgeov role-aware backendovým endpointom a zjednotiť search/filter/sort/page-size/pagination ovládanie admin zoznamov.

## Backend

`AdminCountsService` agregoval počty z flat-file repositories. `GET /api/admin/counts` vracal EDITOROVI content/media/backups a ADMINOVI navyše comments/messages/trash/users. `ui.showListCounts` riadil badge visibility a `ui.adminListPageSize` default.

Trash API dostalo bulk purge, backup, empty a download backup endpointy.

## Frontend

`getAdminCounts()` + `useAdminCounts()` napájali sidebar. `AdminListToolbar`, `AdminListPagination`, `useAdminListPageSize()` a `applyClientListView()` vytvorili shared list UX pre Media, Pages/Articles, Comments a Trash.

## Bezpečnostné hranice

Role-aware payload nie je iba vizuálne filtrovanie; server nesmie poslať admin-only počty používateľovi bez oprávnenia. Trash purge/empty/download sú mutačné alebo citlivé operácie a musia zostať za authz, CSRF a podľa politiky 2FA.

## Overenie

Testy pokrývali editor/admin field visibility, trash bulk purge a empty a client-side filter/sort/paginate helper. Neskoršie URL-synced filtre doplnila [It.44](ITERATION_44.md).

