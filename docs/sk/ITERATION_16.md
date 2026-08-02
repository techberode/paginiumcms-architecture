---
title: Iterácia 16 – Full-stack Code Editor
description: Historický záznam Monaco editora, file tree, create/delete, backup restore a safety UX
icon: material/history
---

# Iterácia 16 – Full-stack Code Editor

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Core stack dokončený; extension bundle editor nie je potvrdený |
| Release / obdobie | 2.0.22 + neskorší It.15 runtime |
| Typ záznamu | historická developer-tool iterácia |

## Cieľ

Dodať Monaco-based editor nad explicitným whitelistom, Developer Mode unlock, hierarchický file tree, bezpečný save, create/delete a obnovu backupu.

## Dodaný rozsah

| Prvok | Stav |
|---|---|
| Monaco | format, word wrap, synchronizácia témy |
| Gate | TOTP/dev token unlock + explicitné zamknutie |
| Filesystem | všetky povolené roots, hierarchický tree |
| Save | policy + syntax + pre-save backup |
| Create/Delete | samostatné HTTP akcie a potvrdenia |
| Restore | zoznam a obnova backupu |
| UX | warning banner, save/delete/lock confirmations |

## Whitelist

Povolené boli Modules, Http/Extensions, theme views a config. Core, bootstrap a vendor zostávajú mimo editora.

## Neuzavretý rozsah

Pôvodný dokument označoval plugin bundle editor ako blokovaný It.15. It.15 je už dodaná, ale zdroj nepotvrdzuje, že samostatný package editor bol následne implementovaný. Preto zostáva **nepotvrdený**, nie automaticky označený ako hotový.

## Bezpečnostný kontrakt

Uloženie súboru nie je registrácia/aktivácia pluginu ani frontend build. Current rules: [CODE_EDITOR.md](user/CODE_EDITOR.md), [DEVELOPER_MODE.md](user/DEVELOPER_MODE.md), [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md).

