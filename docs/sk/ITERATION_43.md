---
title: Iterácia 43 – Rozšírené vyhľadávanie a admin command palette
description: Historický záznam scoped public/admin search, Ctrl+K palety a test-artifact cleanupu
icon: material/history
---

# Iterácia 43 – Rozšírené vyhľadávanie a admin command palette

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Implementované; staré `Unreleased` označenie je neaktuálne |
| Release / obdobie | po 2.0.26, presný prvý tag zdroj neuvádza |
| Typ záznamu | historická search a test infra iterácia |

## Cieľ

Dodať scoped fulltext search pre public web a admin command palette bez rozbitia legacy flat odpovede `/api/search` pre verejných klientov.

## Backend kontrakt

| Scope | Auth | Typy | Tvar |
|---|---|---|---|
| `public` | bez session | page, article | legacy flat `data: SearchResult[]` |
| `admin` | session | page, article, media, route | `{ query, scope, results, counts }` |

Query parametre: `q` min 2, `scope`, comma-separated `types`, limit max 20 per type. Admin scope zahŕňal drafty a role-filtered route catalog.

## Frontend

`AdminCommandPalette` sa otvárala cez Ctrl/Cmd+K, `ResponsiveLayout` registroval hotkey a recent jumps sa ukladali do localStorage. Verejný `SiteSearchModal` explicitne používal `scope=public`.

## Test infra doplnok

V rovnakom release train vznikol `TestStorageCleaner`, CLI `test-artifacts.php`, izolované `settings.testing.json` a cleanup krok v test runneri. Dnešný test kontrakt používa per-run izolované storage roots; cleanup podľa generických email prefixov je len prechodná ochrana starého shared storage.

## Stav a incidenty

Zdroj označoval release track ako `Unreleased`, ale funkcia bola implementovaná a neskôr roadmapa/feature overview potvrdili dokončenie It.43. Presný prvý release tag však zdroj neuvádza, preto ho nevymýšľame. Flaky admin draft search bol neskôr opravený v [ISS-023](ISSUES.md#iss-023).

## Overenie

PHPUnit pokrýval search controller a cleaner, Vitest command palette. Aktuálny test workflow je v [TESTING.md](developer/TESTING.md).

