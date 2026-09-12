# Iterácia 90 — Editor Workbench (toolbar builder + sprievodcovia publikovania)

> **Stav:** ⏳ plánované  
> **Priorita:** 🟡 P1 — UX publikovania pre netechnických autorov  
> **Vlna:** Editor & obsah (po It.79)  
> **Plná špecifikácia (EN):** [../en/ITERATION_90.md](../en/ITERATION_90.md)

## Cieľ

Nahradiť rigidné **editor profily** **nastaviteľným toolbarom** pre **Markdown aj Tiptap (WYSIWYG)**, plus **sprievodcovia vloženia** (tabuľka, callout, mermaid, graf), aby autor nemusel hľadať syntax v externých návodoch. Voliteľné **plugin nástroje editora** cez manifest v rámci bezpečnostného baseline.

**Invariant:** SSOT ostáva Markdown (alebo Tiptap → Markdown/HTML); verejný render cez BE CommonMark + shortcode preprocessory.

---

## Cieľové nastavenia → Editor

- Predvolený režim (Markdown / WYSIWYG)
- **Markdown toolbar** — zoradený zoznam nástrojov
- **WYSIWYG toolbar** — rovnaký model pre Tiptap
- Voliteľné plugin nástroje (opt-in v nastaveniach)
- **Presety** — bývalé profily len ako „Načítať toolbar: Blog“

V editore článku **žiadny výber profilu**.

---

## Fázy

| Fáza | Rozsah |
|------|--------|
| **90a** | Toolbar builder + presety; odstránenie profile pickeru z UI článku |
| **90b** | Sprievodcovia: tabuľka, callout |
| **90c** | `:::mermaid` + BE SVG render |
| **90d** | CodeMirror 6 markdown surface (opt-in) |
| **90e** | `:::chart` + SDK pre plugin Editor Tool |

---

## Bezpečnosť

- Žiadny raw HTML / MDX / ľubovoľný JS v obsahu
- Mermaid/graf — validácia na BE, výstup sanitizované SVG
- Plugin nástroj = manifest + capability + BE renderer

## Súvisiace

[It.79](ITERATION_79.md) · [It.55](../ITERATION_55.md) · [It.89](../en/ITERATION_89.md)
