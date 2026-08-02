---
title: It.58 – Rozhodnutie o alternatívach layout buildera
description: Rozhodovací dokument pre Paginium Layout Switch, spoločný AST, Monaco a fail-closed ochranu.
icon: material/history
---

# It.58 – Rozhodnutie o alternatívach layout buildera

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | 📐 Rozhodnutie; 58c dodané, ďalšie fázy otvorené |
| Release / obdobie | snapshot 2.1.0-beta.21; 58c neskôr beta.23 |
| Typ záznamu | historický architecture decision record |

## Cieľ

Vybrať Paginium-native page-building model bez ťažkého pixelového buildera: templates, shortcodes, optional outline a developer Monaco ako prepínateľné pohľady nad jedným layout AST.

## Rozsah a výsledok

Rozhodnutie odmietlo react-grid-layout, GrapesJS/Elementor model, arbitrary Tailwind/inline styles a role-exclusive builders. Settings switch určuje pracovný mód; rola iba povoľuje citlivejší Developer režim.

Fázy: 58c templates + preview; 58d shortcode registry/parser/Monaco; 58e `pg-*`; 58f outline; 58g compile/cache. Templates, shortcodes a outline musia byť interoperabilné cez spoločný AST.

## Architektonické a bezpečnostné hranice

Maximálna ochrana má šesť vrstiev: syntax/JSON parse, security scan, code policy, artifact schema, expand allow-list a runtime AST render bez user PHP. Untrusted paths sa validujú aj pri vypnutom core editor policy toggli. Broken alebo hostile artifact končí 422 pred zápisom a registry update.

## Overenie a súvisiace záznamy

Zdroj bol aktualizovaný pri `v2.1.0-beta.21`, keď bola 58b hotová. Neskorší hlavný dokument potvrdzuje 58c v [v2.1.0-beta.23](../CHANGELOG.md#release-2-1-0-beta-23). Otvorené zostávajú SSOT sync pri prepínaní a presný Monaco placement.

## Aktuálna interpretácia

Tento dokument je záväzný architecture decision record pre otvorené 58d–58g. Budúce plugin/theme studios môžu reuse rovnaký pattern, ale nie sú súčasťou It.58.
