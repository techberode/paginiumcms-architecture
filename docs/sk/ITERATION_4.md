---
title: Iterácia 4 – Settings Engine, error handler a zdieľaná validácia
description: Historický záznam flat-file nastavení, centralizovaných API chýb a zdieľaných validačných pravidiel
icon: material/history
---

# Iterácia 4 – Settings Engine, error handler a zdieľaná validácia

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.6 – základ jadra; neskoršie doplnenie 2.0.56 |
| Typ záznamu | historická základná iterácia |

## Cieľ

Vytvoriť schema-driven Settings Engine bez databázy, jednotný JSON error handler a pravidlá validácie použiteľné backendom aj frontendovým formulárom.

## Dodaný základ

| Komponent | Úloha |
|---|---|
| `SettingsSchema.php` | Skupiny a field definície |
| `SettingsRepository.php` | `data/settings.json`, ukladanie delt, `flock` |
| `SettingsController.php` | Admin CRUD, public slice a reset |
| `Validator.php` / `ValidationRules.php` | Stateless validácia a katalóg pravidiel |
| `ValidationController.php` | `GET /api/validation/rules` |
| `ApiErrorHandler.php` | Mapovanie výnimiek na stabilné JSON odpovede |

## API a frontend

Základné odpovede rozlišujú validačnú chybu `422` s `errors`, všeobecnú `4xx/5xx` chybu a public settings slice. Frontend používa `settings.ts`, `validation.ts`, `SettingsView`, `SettingsContext`, `useSettings` a mirror validátora.

V release 2.0.56 pribudla koordinovaná kontrola `passwordConfirm`; ide o neskoršie rozšírenie pôvodnej iterácie.

## Bezpečnostná poznámka

Secret fields nie sú verejné nastavenia a pri ukladaní sa musia riadiť aktuálnym encryption kontraktom. Aktuálne pravidlá precedence, migrácií a reloadu sú v [SETTINGS.md](architecture/SETTINGS.md).

