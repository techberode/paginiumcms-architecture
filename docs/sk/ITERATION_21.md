---
title: Iterácia 21 – API kontrakt, automatizované testovanie a frontendová parita
description: Historický záznam JsonResponder kontraktu, MSW, smoke testov a schema-driven validácie
icon: material/history
---

# Iterácia 21 – API kontrakt, automatizované testovanie a frontendová parita

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.9 |
| Typ záznamu | historická kontraktová a testovacia iterácia |

## Cieľ

Zjednotiť JSON odpovede HTTP controllerov, zlepšiť frontend-only vývoj cez MSW, zaviesť Postman/Newman smoke testy a mapovať backend validáciu do React Hook Form + Zod.

## Dodaný kontrakt

| Oblasť | Dodávka |
|---|---|
| Backend | `JsonResponder` pre success, error, validation, conflict, paginated a všeobecnú odpoveď |
| Frontend | typed clients, MSW handlers, RHF + Zod v nastaveniach a mapovanie `422` cez `setError()` |
| Tooling | Postman collection, Newman script, GitHub Actions CI a aktualizované API dokumenty |
| Testy | response-shape testy, MSW handler testy a `zodFromRules` testy |

## Aktuálna interpretácia

Historický cieľ „všetky odpovede majú rovnaký JSON envelope“ dnes platí iba pre aplikačné API vrstvy. WAF alebo reverse proxy môžu odpovedať plain-text alebo prázdnym telom ešte pred routingom; frontend preto musí kontrolovať status a `Content-Type` pred JSON parsingom. Legacy auth envelope je tiež zdokumentovaná výnimka.

Kanonický kontrakt je v [API_CONTRACT.md](architecture/API_CONTRACT.md).

## Odložený rozsah

OpenAPI 3.1 export a úplná migrácia všetkých komponentov z generického `useApi` na typed clients zostali v zdroji odložené. Postman kolekcia sa má chápať ako smoke subset, nie úplná špecifikácia.

## Historické testy

Zdroj uvádzal viac než 503 PHPUnit testov a PHPStan L8. Ide o snapshot release 2.0.9, nie aktuálny počet. Súčasný release gate je definovaný v [TESTING.md](developer/TESTING.md) a [RELEASE.md](developer/RELEASE.md).

