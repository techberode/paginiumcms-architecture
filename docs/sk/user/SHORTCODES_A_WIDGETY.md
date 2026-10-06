---
title: Shortcodes a widgety — príručka
description: Ako fungujú bloky na stránke, príklady pre každý bundled shortcode (beta.96)
icon: material/code-braces
---

# Shortcodes a widgety — príručka

> **Pre koho:** upravuješ stránky v **Outline**, **Shortcodes** alebo **Developer** režime — bez PHP.  
> **Anglická verzia (detail):** [SHORTCODE_COOKBOOK.md](../../en/user/SHORTCODE_COOKBOOK.md) · [SHORTCODES_AND_WIDGETS.md](../../en/user/SHORTCODES_AND_WIDGETS.md)

---

## Ako to funguje (reštaurácia)

1. **Ty (hosť)** — v editore píšeš Markdown a tagy typu `[feature-card]…[/feature-card]`. To sa uloží do súboru stránky.
2. **Kuchár (PHP backend)** — pri zobrazení stránky **ShortcodeExpander** tagy premení na bezpečné HTML (`pg-*` triedy).
3. **Čašník (React na webe)** — niektoré bloky majú `data-island="…"`; verejný bundle doplní interaktivitu (lightbox galérie, karusel).

Ak na webe vidíš surový text `[showcase-hero`, tag je vypnutý, preklep, alebo atribúty sú **mimo** úvodnej `[ zátvorky ]` — pozri [syntax](../../en/user/SHORTCODES_AND_WIDGETS.md#2-shortcode-syntax-rules).

**Outline** a **Shortcodes** editujú **ten istý** reťazec v tele stránky.

---

## Syntax v skratke

**Samozatváracie** (jeden blok, len atribúty):

```markdown
[stat-item value="2FA" label="Admin"/]
```

**Párové** (vnútorný obsah medzi otvorením a zatvorením):

```markdown
[feature-grid columns="3"]
[feature-card title="Média"]Knižnica súborov na disku.[/feature-card]
[/feature-grid]
```

---

## Marketing — príklady

| Shortcode | Na čo |
|-----------|--------|
| `showcase-hero` | Veľký hero s badge, terminálom, 2× CTA |
| `landing-hero` | Jednoduchší hero (+ voliteľné médium z DAM) |
| `section-head` | Kotva + nadpis sekcie |
| `section-band` | Obal sekcie (vnútri ďalšie bloky) |
| `stats-row` + `stat-item` | KPI riadok (voliteľne count-up animácia) |
| `feature-grid` + `feature-card` | Mriežka kariet, vnútri Markdown |
| `stack-grid` + `stack-tag` | Technologické „čipy“ |
| `testimonial` | Citát + autor |
| `pricing-table` + `pricing-plan` + `pricing-feature` | Cenník |
| `cta-banner` | Pás s výzvou |
| `alert-box` | Info / varovanie / úspech |
| `faq-list` + `faq-item` | FAQ (`<details>`) |
| `link-row` + `link-chip` | Riadok pill odkazov |
| `coming-soon` | „Pripravujeme“ panel |
| `visual-frame` | Zarovnanie / max šírka + **efekt scrollu** (`motion="fade-up"` / `stagger`) |

Ukážka hero:

```markdown
[showcase-hero badge="CMS" title="Headline" subtitle="Jedna veta." cta="Kontakt" href="/contact" cta2="Blog" href2="/blog"/]
```

---

## Galérie (beta.95)

### `[feature-gallery]` — katalóg z admin **Galéria funkcií**

Fotky pridávaš v `/gallery`; shortcode ich len **zobrazí**.

```markdown
[feature-gallery title="Vybrané práce" tag="" layout="grid" columns="3"/]
```

- `tag=""` → všetky publikované položky; `tag="web"` → filter podľa štítku v admin formulári.
- `layout`: `grid`, `slider`, `hero-strip`.
- Lightbox: rovnaký modal ako pri obrázkoch v článku; funguje aj **video** z katalógu.
- Zdieľateľný odkaz: `/features?slide=ID_POLOŽKY` (id z adminu).

Viac: [GALLERY.md](GALLERY.md).

### `[media-gallery]` — vybrané súbory z Médií (nie katalóg funkcií)

```markdown
[media-gallery title="Prehliadka" ids="media/a.jpg|media/b.mp4" columns="3" layout="masonry"/]
```

- `ids` — cesty v knižnici (`media/…`), oddeľ `|` alebo `,`.
- Obrázky aj **nahraté video** (mp4/webm); klik → lightbox s prehrávaním.

### `[gallery-carousel]`

Karusel cez React island — atribúty cez insert modal v editore.

---

## Widgety (KPI, grafy, mapa)

```markdown
[widget type="kpi" title="Návštevy" value="12k"/]
[widget type="map-embed" src="https://www.google.com/maps/embed?pb=…"/]
[widget type="form-cta" href="/contact" subject="Dotaz z landingu"/]
```

Zoznam typov: [SHORTCODES_AND_WIDGETS §4](../../en/user/SHORTCODES_AND_WIDGETS.md#4-widgets-vs-shortcodes).

---

## Obrázky a video v texte (nie shortcode)

| Insert v editore | Lightbox |
|------------------|----------|
| Obrázok z knižnice | Klik → galéria (vypni: **Open in lightbox** off) |
| Video z knižnice | Klik → prehrávanie v lightboxe |
| YouTube/Vimeo embed | Povolené domény; zoskupenie cez **Gallery group** |

Skupina albumu: v media pickeri **Gallery group** = rovnaké `data-gallery="portfolio"` na viacerých médiách.

---

## Po upgrade na beta.96

1. Admin → **Shortcodes** — otvor raz (`section-band` v3, `visual-frame` v2).
2. Ulož znova dôležité landing stránky (motion / galérie).
3. Na produkcii rebuild frontendu.
4. Prípadne purge content cache.

Anglický cookbook: [SHORTCODE_COOKBOOK.md](../../en/user/SHORTCODE_COOKBOOK.md)

---

## Súvisiace

- [GALLERY.md](GALLERY.md) · [CONTENT_EDITOR.md](CONTENT_EDITOR.md) · [LANDING_PAGE](../../en/user/LANDING_PAGE.md)
