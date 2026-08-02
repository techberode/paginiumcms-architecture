---
title: Iterácia 58 – Page layout builder a farebné schémy
description: Čiastočne dodaný Layout Switch: schémy a template builder hotové, shortcode/outline/compile vetvy zostávajú.
icon: material/history
---

# Iterácia 58 – Page layout builder a farebné schémy

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | 🟡 Čiastočne dokončené: 58b/58c ✅, 58d–58g ⏳ |
| Release / obdobie | 58c: 2.1.0-beta.23 |
| Typ záznamu | historický product/architecture record |

## Cieľ

Dodať viac layout builderov prepínateľných v Settings, ktoré zapisujú jeden kanonický layout AST, a spojiť ich s farebnými schémami, light/dark/system režimom a live preview.

## Rozsah a výsledok

Dodané 58b: päť presetov s light/dark tokenmi, `appearance` settings, swatch a `SchemePreviewFrame`, public application a visitor toggle. Dodané 58c: builder switch, template catalog, page template výber a `LayoutPreviewFrame`; release [v2.1.0-beta.23](../CHANGELOG.md#release-2-1-0-beta-23).

Plánované 58d–58g: shortcode engine + Monaco definitions, safe `pg-*` utility pack, optional outline/DnD a compile/cache HTML spolu s It.48. `featureGallery` má reuse It.65 API bez druhého store.

## Architektonické a bezpečnostné hranice

Všetky režimy musia čítať/zapisovať rovnaký AST a switching nesmie mazať obsah. Non-core definitions sú fail-closed: `ShortcodeDefinitionPolicy` + `CodePolicyEngine::validateUntrusted`, žiadne `eval`, runtime PHP ani arbitrary Tailwind/classes. Preview používa rovnaké validators.

## Overenie a súvisiace záznamy

Rozhodnutia a phased plan sú v [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md). Bezpečnostné dokončenie 58d je previazané s plánovanou [It.67](ITERATION_67.md); write-time baseline priniesla [It.66](ITERATION_66.md).

## Aktuálna interpretácia

It.58 nie je uzavretá iterácia. Aktuálne sú hotové iba 58b a 58c; 58d–58g sa nesmú v dokumentácii alebo UI prezentovať ako dodané. Compile/cache musí byť koordinované s It.48/69 a public render nesmie načítavať admin bundle.
