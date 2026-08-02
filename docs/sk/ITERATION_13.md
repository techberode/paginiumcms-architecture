---
title: Iterácia 13 – Izolovaný demo sandbox
description: Historický záznam demo režimu, izolovaného storage, resetu, quick-login a marketingového trial flow
icon: material/history
---

# Iterácia 13 – Izolovaný demo sandbox

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené v niekoľkých vlnách |
| Release / obdobie | 2.0.28 až v2.1.0-beta.11 |
| Typ záznamu | historická produktová a prevádzková iterácia |

## Produktová pozícia

Demo modul je určený iba pre vlastnú inštanciu `demo.paginiumcms.com`. Nie je súčasťou štandardného zákazníckeho produkčného profilu. Produkčné a demo dáta, secrets, storage, porty, logy a schedulery musia zostať oddelené.

## Dodaný rozsah

- `DEMO_MODE` a fail-closed produkčné kontroly;
- `DemoStorageService` a samostatný `backend/storage/app/demo/`;
- seed obsahu, používateľa, settings, navigácie, comments/messages/newsletter;
- periodický reset cez `demo:reset-if-due`;
- public demo strip, countdown a admin onboarding;
- `GET /api/demo/public-info`, `POST /api/demo/quick-login`, admin status/reset;
- quick-login bez publikovania hesla v public settings;
- izolácia a PHPUnit smoke.

## Bezpečnostná korekcia

Pôvodný záznam obsahoval statické demo credentials. V4 ich nahradil server-side quick-login flow a [ISS-100](ISSUES.md#iss-100) odstránil heslo z `GET /api/settings/public`. Kanonická dokumentácia preto statické heslo neopakuje; aktuálny onboarding používa quick-login.

## Prevádzka

Demo bootstrap musí vytvoriť zapisovateľný storage strom; [ISS-099](ISSUES.md#iss-099) a [ISS-102](ISSUES.md#iss-102) dokumentujú permission a missing-directory incidenty. Produkčný deployment postup je v [DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md).

## Hranice

Reset demo dát nesmie zasiahnuť produkčný SSOT. Služba musí failnúť, ak sa demo cesta prekrýva s production content path.

