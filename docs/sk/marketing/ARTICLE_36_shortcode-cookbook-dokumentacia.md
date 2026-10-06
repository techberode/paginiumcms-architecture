---
title: "Shortcode cookbook a kam smeruje dokumentácia projektu"
slug: shortcode-cookbook-dokumentacia-v-cms
perex: "Operátorsky návod ku každému bundled bloku je v repozitári; pripravujeme modul na Markdown docs priamo v PaginiumCMS — podobne ako MkDocs, v štýle vášho webu."
kategoria: Novinky
tagy: paginiumcms, dokumentacia, shortcode, roadmap, beta96
serie: "PaginiumCMS po beta.89"
serie_diel: 36
release_anchor: "v2.1.0-beta.96"
---

# Shortcode cookbook a kam smeruje dokumentácia projektu

Ak skladáte landing z blokov, nemusíte čítať PHP. **Shortcode cookbook** je písaný pre redaktorov: príklad tagu, čo uvidí návštevník, a kedy treba React **island** (galéria, carousel).

---

## Kde čítať dnes (GitHub)

| Dokument | Účel |
|----------|------|
| [SHORTCODE_COOKBOOK.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/SHORTCODE_COOKBOOK.md) | Príklady pre každý bundled shortcode/widget |
| [SHORTCODES_AND_WIDGETS.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/SHORTCODES_AND_WIDGETS.md) | Syntax, vnorený Markdown, pravidlá |
| [LANDING_PAGE.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/LANDING_PAGE.md) | Outline a landing workflow |
| [ITERATION_98.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/ITERATION_98.md) | Plán multi-site workspace + bezpečné CLI (budúcnosť) |

Slovenský prehľad: [SHORTCODES_A_WIDGETY.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/sk/user/SHORTCODES_A_WIDGETY.md).

---

## Čo plánujeme v samotnom CMS

Krátkodobo zostávajú **architektúra a release poznámky** v gite (jeden zdroj pre vývojárov a CI).

Strednodobo chceme **modul dokumentácie** v PaginiumCMS:

- stránky v **Markdown**,
- navigácia a vyhľadávanie pod `/docs/…`,
- vzhľad z **Site Design** tokenov, nie oddelený statický generator.

Návrh: [DOCS_PORTAL_PLANNED.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/developer/DOCS_PORTAL_PLANNED.md).

Dovtedy publikujeme novinky na **paginiumcms.com** s odkazmi na súbory v `main` vetve repozitára (ako v tabuľkách vyššie).

---

## Súvisiace release

- Galéria a lightbox: **beta.95** — [RELEASE_2_1_0_BETA_95.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/RELEASE_2_1_0_BETA_95.md)  
- Motion B1–B2: **beta.96** — [RELEASE_2_1_0_BETA_96.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/RELEASE_2_1_0_BETA_96.md)

---

## Predchádzajúci diel

[Scroll efekty: visual-frame a section-band](ARTICLE_35_motion-visual-frame-section-band.md)
