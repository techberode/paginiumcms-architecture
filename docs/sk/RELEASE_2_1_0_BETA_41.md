# Release `v2.1.0-beta.41` — Shortcodes, layout shell a server preview

> **Dátum:** 2026-08-13  
> **Tag:** `v2.1.0-beta.41`  
> **Iterácia:** It.58d–58e (layout builder — shortcode vetva)  
> **Súhrn série:** [CLANEK_BETA_35_45_SUHRN.md](CLANEK_BETA_35_45_SUHRN.md)

---

## Zhrnutie jednou vetou

Redaktori môžu skladať **landing stránky z bezpečných layout shortcodes** (grid, karty, hero), spravovať ich v admin UI a vidieť **rovnaký HTML výstup v preview aj na webe** — expander beží na serveri.

---

## Prečo je beta.41 prelomová

Predtým Layout Switch (beta.23) riešil **šablóny stránok a color schemes**. It.58 shortcode vetva pridáva:

- **Opakovateľné layout bloky** v tele obsahu (`[feature-grid]`, `[alert-box]`, …).
- **Jeden render pipeline** — markdown → shortcode expand → sanitizácia → cache.
- **Admin správu definícií** bez deployu kódu (JSON + CodePolicy).

To je praktický krok k „page builderu“ bez druhého page modelu a bez SQL.

---

## Čo je nové pre redaktora

### 1. Režim Shortcodes

**Settings → Appearance → Layout** → builder mode **Shortcodes**.

V editore stránky pribudne panel **Vložiť shortcode** s bundled katalógom:

| Shortcode | Účel |
|-----------|------|
| `alert-box` | Upozornenie / callout |
| `feature-grid` | Mriežka kariet (2–3 stĺpce na desktope) |
| `feature-card` | Jedna karta v gride |
| `landing-hero` | Hero sekcia |

### 2. Správa shortcodes

**Platform → Shortcodes** (`/platform/shortcodes`)

- Zoznam registrácie, JSON definícia, validácia proti CodePolicy.
- Uloženie invaliduje content cache (zmena sa prejaví na webe).

### 3. Server preview

Modal **Preview** volá `POST /api/admin/content/render-preview` — rovnaká logika ako verejný render (shortcodes + markdown + HTML allow-list). Už nie surový text shortcode tagov v preview.

### 4. Verejný layout shell

Stránky s `layoutTemplate` v front matter obalí **`PageLayoutShell`** + CSS triedy `pg-*` (`pgLayout.css`).

---

## Technický prehľad (pre vývojárov)

| Vrstva | Komponent |
|--------|-----------|
| Backend expand | `ShortcodeExpanderService` v `ContentBodyRenderer` |
| Katalóg | `ShortcodeCatalogSeeder` + `data/shortcodes/*.json` |
| Preview API | `ContentMetaController::renderPreview` |
| Frontend | `ShortcodesManager`, insert panel, `PageLayoutShell` |
| CSS | `frontend/src/theme/pgLayout.css` |
| Bezpečnosť | `CodePolicyEngine`, rozšírený HTML allow-list (`div`, `section`, …) |

---

## Servisné verzie beta.42 – beta.45

It.58 sa na produkcii ukázala citlivá na **Vite/PostCSS build**, **layout shell na homepage** a **CSP nginx**. Nasledujúce tagy sú **hotfixy**, nie nové featury:

| Verzia | Symptóm | Oprava |
|--------|---------|--------|
| **beta.42** | Chýbajúce layout CSS na produkcii | `pgLayout.css` import do `main.tsx` |
| **beta.43** | Úvodná stránka ~1/3 šírky | Home slug preskočí `PageLayoutShell` |
| **beta.44** | Shortcodes admin „Loading editor…“ | Explicitná výška Monaca (dočasne) |
| **beta.45** | Shortcodes prázdne na produkcii | Textarea namiesto Monaco CDN + `worker-src` CSP |

Incidenty: [ISS-142](../ISSUES.md#iss-142) – [ISS-146](../ISSUES.md#iss-146).

### Produkcia — nasaďte beta.45

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.45 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Po deployi: hard refresh (Ctrl+Shift+R), overte `/platform/shortcodes` (JSON v textarea).

---

## Príklad shortcode v obsahu stránky

```markdown
[landing-hero title="PaginiumCMS" subtitle="Flat-file CMS pre malé tímy"]

[feature-grid]
[feature-card title="Rýchle" icon="zap"]Bez databázy, deploy za minúty.[/feature-card]
[feature-card title="Bezpečné" icon="shield"]CSP, CSRF, audit.[/feature-card]
[/feature-grid]
```

Expand šablóny používajú triedy `pg-grid-3`, `pg-card` — definované v `pgLayout.css`.

---

## Čo ešte nie je hotové (It.58 remainder)

- **58f** — outline / drag-and-drop builder (odložené).
- **58g** — HTML compile/cache s It.48.

Plánované redakčné vylepšenia: **It.81** (duplikácia, bulk tagy, snippet knižnica).

---

## Overenie po nasadení

1. **Settings → Layout** → Shortcodes mode.
2. Upravte domovskú stránku — vložte `feature-grid`, uložte, preview + verejná URL.
3. **Platform → Shortcodes** — otvorte `alert-box`, validujte, uložte.
4. Skontrolujte, že produkčný CSS bundle obsahuje layout triedy (DevTools → `.pg-grid-3`).

---

## Odkazy

- [CHANGELOG — beta.41](../../CHANGELOG.md#release-2-1-0-beta-41)
- [ITERATION_58.md](../en/ITERATION_58.md)
- [Súhrn beta 35–45](CLANEK_BETA_35_45_SUHRN.md)
- [It.81 backlog](../en/ITERATION_81.md)
