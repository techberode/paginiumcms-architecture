# Iterácia 58f — Vizuálne bloky stránky (amater + developer náhľad)

> **Stav:** 🔄 prebieha — **58f-a/b hotové**; ďalej **58f-d** živý náhľad 
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

`58f-a` ✅ parse ↔ Markdown → `58f-b` ✅ paleta/formáre → **ďalej `d` live preview** → `c` DnD + starter → `e` hero obrázok/video z DAM → `f` galéria It.65 → `g` testy.

Hero video (muted, loop, poster, `prefers-reduced-motion`) je blok Core, nie izolovaný origin. 58g compile ostáva pri It.48.
