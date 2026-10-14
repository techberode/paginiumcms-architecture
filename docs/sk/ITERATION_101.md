---
title: Iterácia 101 — Verejný form builder
description: Plánované definície formulárov, shortcode [form] a odoslanie do Message Desk
---

# Iterácia 101 — Verejný form builder (widgety a shortcodes)

> **Stav:** ⏳ plánované · **Anglická špecifikácia:** [ITERATION_101.md](../en/ITERATION_101.md)

## V skratke

Operátori potrebujú **opakovateľné verejné formuláre** (kontakt, lead, hlásenie chýbajúceho obsahu, newsletter) vložiteľné do stránok a článkov cez **shortcode alebo widget**, bez písania jednorazového Reactu pre každý prípad.

It.101 **nenahradí** existujúci kontaktný modul — **rozšíri** ho o deklaratívne schémy a preset-y.

## Ciele (101 v1)

| ID | Dodávka |
|----|---------|
| **101a** | Schéma definície formulára (flat-file) + admin CRUD (`forms:manage`) |
| **101b** | Predvolené formuláre: kontakt-lite, broken-content (kontextové polia), newsletter |
| **101c** | Shortcode `[form id="…"]` + widget `form-embed` (React island) |
| **101d** | Odoslanie (validácia, rate limit, LogSanitizer, Message Desk) |

## Už dnes (pred It.101)

- **404:** vlastná stránka (`layout.notFoundPageSlug`) + automatický **formulár nahlásenia** s URL a refererom → `/api/contact` → Message Desk.
- **Kontakt:** šablóna stránky + `/api/contact` + widget `form-cta`.
- **Shortcodes:** katalóg v admin **Shortcodes** — pozri [SHORTCODES_A_WIDGETY.md](user/SHORTCODES_A_WIDGETY.md).

## Súvisiace

- [SHORTCODES_AND_WIDGETS.md](../en/user/SHORTCODES_AND_WIDGETS.md) (EN)
- [LANDING_PAGE.md](user/LANDING_PAGE.md)
