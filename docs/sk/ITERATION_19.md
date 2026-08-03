---
title: Iterácia 19 – Admin UX, runtime bezpečnosti a autentifikácia
description: Historický záznam administrátorského shellu, prekladovej politiky, runtime security a auth UX
icon: material/history
---

# Iterácia 19 – Admin UX, runtime bezpečnosti a autentifikácia

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie bezpečnostné opravy a zmeny stavu sú označené oddelene. Pre aktuálne pravidlá majú prednosť dokumenty v `docs/architecture/`, `docs/developer/`, `ISSUES.md`, `CHANGELOG.md` a release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané vo vlnách 19a–19d a doplnené v 2.0.46 |
| Release / obdobie | 2.0.44–2.0.46 |
| Typ záznamu | historická konsolidačná iterácia adminu |

## Cieľ

Zjednotiť administrátorskú navigáciu, nastavenia, prekladový editor, runtime bezpečnostné validátory a prihlasovacie obrazovky do jedného konzistentného admin prostredia.

## Dodaný rozsah

| Vlna | Dodávka |
|---|---|
| 19a | Sekcie sidebaru, zbalenie navigácie, kategórie nastavení, URL deep links a staged validácia prekladov |
| 19b | `UploadSecurityValidator` v MediaRepository, `ContentSecuritySanitizer` v rendereri, Monaco markers a `AdminHintCard` |
| 19c | dynamický register locales, scaffold novej lokalizácie, avatar používateľa a SuperAdmin guardy |
| 19d | `AuthShell`, TOTP vstup, konfigurovateľný login obsah a settings-backed password policy |
| 2.0.46 doplnky | audit activity, application log formatter, login background picker a ďalšie i18n migrácie |

## Incidenty a hotfixy

Táto release vlna zahŕňala opravy [ISS-044](ISSUES.md#iss-044) až [ISS-050](ISSUES.md#iss-050): parse error v DI konfigurácii, chýbajúcu property v locale scaffoldingu, chybnú kategorizáciu audit logov, prázdny activity panel, nečitateľné audit správy, prázdny denný log a rozdielne cesty readera/writera.

Historický záznam používal staré automaticky generované anchors. Kanonické odkazy teraz smerujú na stabilné `#iss-xxx` identifikátory z Iterácie 13.

## Aktuálna interpretácia

Dodané admin UX zostáva základom, ale dnešný bezpečnostný kontrakt je prísnejší: secrets sa nesmú zobrazovať ani logovať, extension kód prechádza write-time policy a frontend locale loading sa nesmie vydávať za runtime dynamiku, ak stále závisí od Vite buildu.

Zostávajúci rozsah v zdroji zahŕňal ďalšie i18n migrácie, voliteľné dynamické načítanie FE locales a napojenie `general.language` na registry. Neskoršia content-localization vetva It.73/76/77 je samostatná schopnosť Hybrid Engineu.

## Overenie a nadväznosť

Release záznamy: [2.0.44](../../CHANGELOG.md#release-2-0-44), [2.0.45](../../CHANGELOG.md#release-2-0-45) a [2.0.46](../../CHANGELOG.md#release-2-0-46). Súvisiace historické dokumenty: [Iterácia 18](ITERATION_18.md) a [Iterácia 20](ITERATION_20.md).

