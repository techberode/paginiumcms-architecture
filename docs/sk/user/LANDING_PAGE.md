---
title: Landing stránka a marketing bloky
description: Shortcodes, widgety, šablóna landing a verejné chybové stránky
icon: material/view-dashboard-outline
---

# Landing stránka a marketing bloky

> **Anglický detail:** [LANDING_PAGE.md](../../en/user/LANDING_PAGE.md) · **Shortcodes SK:** [SHORTCODES_A_WIDGETY.md](SHORTCODES_A_WIDGETY.md)

---

## Šablóna a domov

- **Template** `home` + **layout** `landing` pre marketingovú úvodnú stránku.
- Verejná `/` používa publikovanú stránku so slug **`home`** alebo **`index`**, inak prvú publikovanú stránku s template **`home`**.
- Hero z **showcase / landing shortcodes** v tele + voliteľné SEO pozadie — nie rovnaký header ako bežné stránky.

Príklad:

```markdown
[landing-hero title="PaginiumCMS" subtitle="Flat-file hybrid CMS." cta="Blog" href="/blog"/]
```

---

## Typická štruktúra (Kebite / agentúra)

| Sekcia | Shortcode / widget |
|--------|-------------------|
| Hero | `[landing-hero]` alebo `[showcase-hero]` |
| Metriky | `[stats-row]` / `[widget type="kpi-row"]` |
| Služby | `[feature-grid]` + `[feature-card]` |
| Portfólio | `[feature-gallery]` / `[gallery-carousel]` |
| Tím | staff island alebo `[feature-grid]` s fotkami |
| Referencie | `[testimonial]` v `[section-band]` |
| Cenník | `[pricing-table]` |
| FAQ | `[faq-list]` + `[faq-item]` |
| CTA | `[cta-banner]` alebo `[widget type="form-cta" href="/contact"]` |
| Blog | `/blog` + `[link-row]` / `[latest-articles]` |
| **404** | Publikovaná stránka + **Nastavenia → Layout → notFoundPageSlug** (nižšie) |

---

## Blog a 404 (verejný web)

### Blog `/blog`

- Zoznam článkov je **SPA trasa**, nie stránka pod `/pages`.
- Intro/hero: publikovaná stránka so slug **`blog`** — pozri [ADMIN_GUIDE.md](ADMIN_GUIDE.md) a [PAGE_HERO_IMAGES.md](PAGE_HERO_IMAGES.md).
- **Najnovší článok:** na vrchu zoznamu blok s titulkom a úryvkom z tela článku; vždy posledný publikovaný článok (**vrátane kategórie Archív**, ak dlho neprišla novinka).
- **Radenie:** Najnovšie, Najstaršie, A–Z, **Z–A**, Najčítanejšie.

### Vlastná 404 a hlásenie nefunkčného obsahu

**Nastavenia → Layout → Chybové stránky**

| Kľúč | Účel |
|------|------|
| **notFoundPageSlug** | Slug publikovanej stránky pri neexistujúcej URL (navigácia + pätička ostávajú). Platí aj pre chýbajúce články a hlboké cesty. |
| **serverErrorPageSlug** | Stránka pri chybe renderu Reactu na webe. |
| **brokenContentReportOn404** | Zap/vyp formulára s automatickou URL a refererom (predvolene **zap**). |
| **breadcrumbsPosition** | **Nad obsahom** alebo **Pod hero** (stránka / článok / blog). |

**Nepresmerovávaj** mŕtve URL na `/contact` — HTTP 404 a analytika 404 ostávajú; návštevník vyplní formulár **priamo na 404** (pod vlastnou stránkou alebo vestavným panelom). Správy idú do **Message Desk** s predmetom *Nahlásenie nefunkčného obsahu/odkazu*.

Detail: [SHORTCODES_A_WIDGETY.md § Vlastná 404](SHORTCODES_A_WIDGETY.md#vlastná-404-a-500).

---

## Súvisiace

- [SHORTCODE_COOKBOOK.md](../../en/user/SHORTCODE_COOKBOOK.md) (EN príklady)
- [GALLERY.md](GALLERY.md)
- [ITERATION_101.md](../ITERATION_101.md) — plánovaný form builder
