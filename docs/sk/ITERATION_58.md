---
title: Iterácia 58 – Page layout builder a farebné schémy
description: Layout Switch dokončený — schémy, šablóny, shortcodes, outline canvas a compile/cache s It.48.
icon: material/history
---

# Iterácia 58 – Page layout builder a farebné schémy

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ 58b–58f + **58f-h** canvas dodané; **58g** compile/cache s It.48 v `beta.89` |
| Release / obdobie | 58c: 2.1.0-beta.23 |
| Typ záznamu | historický product/architecture record |

## Cieľ

Dodať viac layout builderov prepínateľných v Settings, ktoré zapisujú jeden kanonický layout AST, a spojiť ich s farebnými schémami, light/dark/system režimom a live preview.

## Rozsah a výsledok

Dodané 58b: päť presetov s light/dark tokenmi, `appearance` settings, swatch a `SchemePreviewFrame`, public application a visitor toggle. Dodané 58c: builder switch, template catalog, page template výber a `LayoutPreviewFrame`; release [v2.1.0-beta.23](../../CHANGELOG.md#release-2-1-0-beta-23).

Dodané 58d: shortcode expand pipeline (`ShortcodeExpanderService` pri renderi), bundled catalog seeder, admin `ShortcodesManager` (Monaco JSON + policy preview), insert panel v page editore pri režime Shortcodes a verejný `PageLayoutShell` z front matter `layoutTemplate`.

Dodané 58e: allow-list `pg-*` layout utility v `frontend/src/theme/pgLayout.css` pre expand šablóny shortcodov.

Dodané 58f–58g: outline/DnD canvas ([ITERATION_58f](ITERATION_58f.md), slicey **58f-a–h**), compile/cache HTML s [It.48](../en/ITERATION_48.md). Blok `feature-gallery` reuse It.65 API bez druhého úložiska.

## Architektonické a bezpečnostné hranice

Všetky režimy musia čítať/zapisovať rovnaký AST a switching nesmie mazať obsah. Non-core definitions sú fail-closed: `ShortcodeDefinitionPolicy` + `CodePolicyEngine::validateUntrusted`, žiadne `eval`, runtime PHP ani arbitrary Tailwind/classes. Preview používa rovnaké validators.

## Overenie a súvisiace záznamy

Rozhodnutia a phased plan sú v [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md). Bezpečnostné dokončenie 58d je previazané s plánovanou [It.67](../en/ITERATION_67.md); write-time baseline priniesla [It.66](../en/ITERATION_66.md).

## Aktuálna interpretácia (September 2026)

**It.58 je uzavretá** pre produktové slicey. **58b–58e** dodané. UX publikovania **[ITERATION_58f](ITERATION_58f.md)** (**58f-a–h**: vizuálne plátno, formuláre, live preview, DAM hero video, feature-gallery, i18n). **58g** compile/cache s [It.48](../en/ITERATION_48.md) v `v2.1.0-beta.89`. Nevymýšľaj 58i ani It.94 pre page bloky.

Rozšírenia landing/portfolio **po uzavretí It.58** (section-band, React islands, per-block gallery attrs) sú samostatná vlna **It.58f-i** — [PAGE_FIELD_COMPOSER_PLANNED.md](../en/architecture/PAGE_FIELD_COMPOSER_PLANNED.md), [REACT_SHORTCODE_ISLANDS.md](../en/architecture/REACT_SHORTCODE_ISLANDS.md); nie sú súčasťou pôvodného čísla 58.
