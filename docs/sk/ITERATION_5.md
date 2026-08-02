---
title: Iterácia 5 – Používatelia a hardening autentifikácie
description: Historický záznam správy používateľov, session autentifikácie, rolí a 2FA pravidiel
icon: material/history
---

# Iterácia 5 – Používatelia a hardening autentifikácie

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené; neskôr opakovane hardenované |
| Release / obdobie | 2.0.6 foundation; UI 2.0.18; confirmation 2.0.56 |
| Typ záznamu | historická bezpečnostná iterácia |

## Cieľ

Dodať administráciu používateľov, role `USER/EDITOR/ADMIN/SUPER_ADMIN`, session autentifikáciu cez HttpOnly cookie a vynútenie 2FA na administrátorských trasách.

## Backend a API

- `UserController` – vytvorenie, úprava, deaktivácia a role;
- `AuthenticationManager` + `SessionManager` – cookie session, bez Bearer tokenu v SPA;
- `TwoFactorMiddleware` – ochrana staff/admin operácií;
- `CsrfProtectionManager` a neskôr globálny synchronizer-token middleware;
- `PasswordPolicy` + zdieľaná validácia.

Historický login/register endpoint používal legacy flat envelope s `user` v koreňovej odpovedi. Tento rozdiel zostáva evidovaný v [API_CONTRACT.md](architecture/API_CONTRACT.md).

## Admin používateľský formulár

Formulár obsahoval `username`, `name`, `email`, `password`, `passwordConfirm`, `role`, `active` a 2FA stav. Pri vytvorení bolo heslo povinné; pri editácii iba pri zmene. Od 2.0.56 backend aj frontend vyžadujú zhodné potvrdenie hesla.

Historické API umožňovalo získať 2FA secret v detailnom admin flow. Aktuálny bezpečnostný kontrakt minimalizuje expozíciu secretov a zakazuje ich zapisovať do logov.

## Neskorší hardening

Login loop, provisioning 2FA, serializácia `twoFactorVerifiedAt`, session keepalive, trusted proxy a rate-limit regresie boli riešené v [ISSUES.md](ISSUES.md#iss-025), [ISS-029](ISSUES.md#iss-029) až [ISS-034](ISSUES.md#iss-034) a ďalších bezpečnostných hotfixoch.

