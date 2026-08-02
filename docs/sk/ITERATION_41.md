---
title: Iterácia 41 – Email OTP workflowy
description: Historický záznam OTP pre registráciu, schválenie komentára a publikovanie obsahu
icon: material/history
---

# Iterácia 41 – Email OTP workflowy

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené; neskôr bezpečnostne hardenované |
| Release / obdobie | release neurčený v zdroji |
| Typ záznamu | historická workflow/security iterácia |

## Cieľ

Zaviesť voliteľné email OTP potvrdenie pre verejnú registráciu a citlivé editor workflowy: schválenie komentára a publish.

## Backend kontrakt

Settings skupina `workflows` riadila enable flags, TTL a max attempts. `OtpChallengeStore` používal flat-file `data/otp-challenges.json` s `flock`; `OtpWorkflowService` posielal kódy cez email channel.

API vracalo `202` + `requires_otp` a challenge ID. Samostatné verify/resend endpointy existovali pre auth aj admin workflowy.

## Frontend a role

`RegisterModal` dostal OTP krok, `OtpConfirmModal` bol reusable pre comments/publish a API helpers mapovali pending odpoveď. Registration flow bol public; comment/publish vyžadoval EDITOR/ADMIN/SUPER_ADMIN.

## Dokumentované obmedzenie a neskorší hardening

Bulk comment approve v pôvodnej iterácii OTP nevyžadoval. To je explicitné historické obmedzenie, nie všeobecné pravidlo. Neskôr pribudol dedikovaný OTP rate limit, resend counter a test isolation; pozri [ISS-058](ISSUES.md#iss-058) a [ISS-103](ISSUES.md#iss-103).

`debug_code` bol prípustný iba v testing/development pri nedostupnom SMTP. Nesmie sa objaviť v produkčnej odpovedi ani CI logu.

## Overenie

Testy pokrývali service flow, registráciu s OTP a schválenie komentára. Aktuálny auth a 2FA kontrakt je popísaný v [SECURITY.md](developer/SECURITY.md).

