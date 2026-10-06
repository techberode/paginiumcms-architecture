---
title: "Scroll efekty bez programovania: visual-frame a section-band (beta.96)"
slug: scroll-efekty-visual-frame-section-band
perex: "V insert modale zvolíte fade-up alebo stagger; na section-band rozšírené reveal a hover presety — stále len CSS a IntersectionObserver, nie autorov JavaScript."
kategoria: Novinky
tagy: paginiumcms, motion, landing, shortcode, beta96, pristupnost
serie: "PaginiumCMS po beta.89"
serie_diel: 35
release_anchor: "v2.1.0-beta.96"
---

# Scroll efekty bez programovania: visual-frame a section-band (beta.96)

Release **beta.96** dokončuje **Experience Phase B1–B2**: pohyb na verejnom webe bez porušenia CSP a bez vkladania skriptov do Markdownu.

---

## B1 — `[visual-frame]`

Pri vkladaní bloku môžete zapnúť **Scroll effect**:

- `fade-up` — jemný nábeh pri scrolli do viewportu.
- `stagger` — postupné odkrytie priamych detí (karty, riadky).
- `motion-delay="short"` — kratšie oneskorenie.

Obal funguje okolo existujúcich shortcodov (alert-box, feature-card, …).

---

## B2 — `[section-band]` reveal a hover

Atribút **`reveal`:**

| Hodnota | Efekt |
|---------|--------|
| `scroll` | klasický fade-up (predvolené správanie) |
| `scroll-stagger` | stagger vnútorných prvkov |
| `slide-left` / `slide-right` | posun z boku |
| `scale-in` | mierne zväčšenie |
| `none` | vypnuté |

Atribút **`hover-effect`** na vnorených kartách: `lift`, `glow`, **`tilt-3d`**, **`border-sweep`**.

Všetko rešpektuje **`prefers-reduced-motion`**.

---

## Technická dokumentácia (GitHub)

| Téma | Odkaz |
|------|--------|
| Príklady v cookbooku | [SHORTCODE_COOKBOOK.md — section-band & visual-frame](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/SHORTCODE_COOKBOOK.md) |
| Roadmap Phase B | [PUBLIC_EXPERIENCE_ROADMAP.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/architecture/PUBLIC_EXPERIENCE_ROADMAP.md) |
| B3+ nápady (progress bar, View Transitions) | [PRODUCT_GROWTH_PROPOSALS.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/architecture/PRODUCT_GROWTH_PROPOSALS.md) |
| Release beta.96 | [RELEASE_2_1_0_BETA_96.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/RELEASE_2_1_0_BETA_96.md) |

---

## Po upgrade

1. Deploy + rebuild frontendu.  
2. Admin → **Shortcodes** (seed `section-band` v3).  
3. Skontrolujte landing so `[section-band reveal="scroll-stagger"]`.

---

## Ďalší diel

[Shortcode cookbook a budúca dokumentácia v CMS](ARTICLE_36_shortcode-cookbook-dokumentacia.md)
