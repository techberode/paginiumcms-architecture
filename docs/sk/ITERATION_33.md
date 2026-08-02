---
title: Iterácia 33 – Rozšírenie analytiky
description: Historický záznam klasifikácie refererov, maskovania IP, geo návštev a SPA pageview beaconu
icon: material/history
---

# Iterácia 33 – Rozšírenie analytiky

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Implementované |
| Release / obdobie | release neurčený v zdroji |
| Typ záznamu | historická analytická iterácia |

## Cieľ

Rozšíriť existujúcu flat-file analytiku o čitateľné zdroje návštevnosti, krajinu/mesto, maskované ukážkové IP a posledné geo návštevy bez zavedenia databázy.

## Backend a frontend

| Komponent | Úloha |
|---|---|
| `RefererAnalyzer` | direct/search/social/referral + ľudský label |
| `AnalyticsIpMasker` | GDPR-aware maskovanie IPv4/IPv6 pre UI |
| Reporter | top referers, geo stats a recent geo visits |
| Frontend | country flag util, Sources/Geo taby a typed API payloady |

## SPA pageview oprava

Statický nginx frontend neprechádzal cez PHP `AnalyticsMiddleware`. Riešením bol verejný `POST /api/analytics/pageview`, path validator, 3-sekundový dedupe cez cache a `useAnalyticsPageview()` pri React Router navigácii. Beacon rešpektuje analytics consent, ak je cookie banner aktívny.

## Bezpečnostné a privacy hranice

Endpoint je CSRF-exempt iba preto, že je verejný beacon; stále musí byť rate-limited, path-validated a nesmie akceptovať `/api/*` ani traversal. Maskované IP v UI nemenia požiadavky na retenciu a ochranu raw analytických dát.

## Overenie

Acceptance pokrývalo labels zdrojov, flags/mesto/maskované IP, unit testy analyzéra/maskera/reporteru a Vitest country flag. Zdroj tiež uvádza súvisiace CI hotfixy, ktoré nie sú samotnou funkciou analytiky.

