---
title: Iterácia 25 – Setup wizard a používateľské aktualizácie pred 1.0
description: Plánovaný historický návrh first-run wizardu a one-click update UX nad existujúcim deploy enginom
icon: material/history
---

# Iterácia 25 – Setup wizard a používateľské aktualizácie pred 1.0

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ⏳ Plánované; zdroj nepotvrdzuje dodanie |
| Release / obdobie | post-beta, pred 1.0 GA |
| Typ záznamu | historický plán produktu |

## Produktový cieľ

Doplniť first-run onboarding a dashboardové „Update available / Update now“ UX pre SUPER_ADMINA. Technický deploy engine bol podľa zdroja dodaný v It.63 (`v2.1.0-beta.18`), ale It.25 mala z neho vytvoriť bezpečný používateľský flow pred stabilnou 1.0.

## Plánovaný setup wizard

| Krok | Obsah |
|---|---|
| 1 | vytvorenie alebo potvrdenie prvého SUPER_ADMINA |
| 2 | názov webu, locale a voliteľná stock téma |
| 3 | detekcia git/package inštalácie, GitHub repo/token a deploy permissions checklist |
| 4 | voliteľný seed stock obrázkov |
| dokončenie | atomický zápis settings + `installed: true`, redirect na dashboard |

## Plánované update UX

Dashboard banner, explicitné potvrdenie aktualizácie, reuse whitelisted deploy jobu, čitateľný progress log, backup prompt a jasné správanie pre demo alebo non-git inštalácie. Automatické aktualizovanie bez potvrdenia zostalo mimo scope.

## Bezpečnostný kontrakt

SUPER_ADMIN + 2FA, CSRF, šifrované secrets, zákaz arbitrary shell a skrytie update UI v demo režime. Package-based updater bez gitu bol iba stretch goal.

## Aktuálny stav a acceptance

V dodaných materiáloch nie je dôkaz, že `/setup`, `POST /api/setup/complete` alebo dashboardový one-click update flow boli dokončené. Preto dokument zostáva plánom. Acceptance vyžadoval fresh-install wizard, zachovanie `storage/app/content/`, skrytý update CTA v demo režime a aktualizované Installation/Admin Guide.

