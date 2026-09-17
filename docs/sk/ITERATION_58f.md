# Iterácia 58f — Vizuálne bloky stránky (amater + developer náhľad)

> **Stav:** ✅ hotové — **58f-a–g** (paleta, živý náhľad, DnD, DAM hero, galéria, i18n). 58g compile ostáva pri It.48.  
> **Kanónická špecifikácia (EN):** [../en/ITERATION_58f.md](../en/ITERATION_58f.md)

## Prečo 58f, nie 94

Po It.88–91 je diera stále tá istá: amater nevie poskladať landing/portfolio z vizuálnych blokov. Shortcody, wizards a Theme Studio už sú. **58f je zvyšok It.58** (outline/DnD). Nové číslo nezakladáme.

## Dva režimy, jeden Markdown

| Kto | UI |
|-----|-----|
| Amater | Paleta (Hero, karty, CTA, galéria, video…) → formulár → náhľad. Presun myšou. |
| Developer | Ten istý dokument v Monaco + **živý náhľad**. Nové bloky = Shortcodes Manager. |

SSOT ostáva telo stránky. Outline je pohľad, nie druhá databáza.

## Slicey

`58f-a` ✅ parse ↔ Markdown → `58f-b` ✅ paleta/formáre → `58f-d` ✅ live preview → `58f-c` ✅ DnD + starter → `58f-e` ✅ DAM hero obrázok/video na `landing-hero` → `58f-f` ✅ galéria It.65 (`feature-gallery`) → `58f-g` ✅ i18n / nápoveda builderMode.

Mimo 58f: editor stránky/článku má voliteľný celoobrazovkový **Workspace** (tlačidlo v chrome, predvolené v Settings → Editor).

Hero video (muted, loop, poster, `prefers-reduced-motion`) je blok Core, nie izolovaný origin. 58g compile ostáva pri It.48.
