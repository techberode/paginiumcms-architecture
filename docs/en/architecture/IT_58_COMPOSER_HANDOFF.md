# It.58 complete — composer & islands handoff (October 2026)

> **Audience:** maintainers continuing layout/composer work after **It.58** closure.  
> **It.58 core:** ✅ **58b–58g** + **58f-a–h** — [ITERATION_58.md](../ITERATION_58.md), [ITERATION_58f.md](../ITERATION_58f.md).  
> **Post-58 product wave:** **It.58f-i** (page field composer) + **React shortcode islands** — not part of the original It.58 numbering.

---

## 1. What “It.58 complete” means

| Phase | Scope | Status |
|-------|--------|--------|
| **58b** | Color schemes, appearance settings, public theme | ✅ |
| **58c** | Layout switch, page templates, preview frames | ✅ |
| **58d–58e** | Shortcode expander, bundled catalog, `pg-*` CSS | ✅ |
| **58f-a–h** | Outline canvas, live preview, DAM hero, feature-gallery block, i18n | ✅ |
| **58g** | Static HTML compile/cache with [It.48](../ITERATION_48.md) | ✅ `beta.89` |

**Not included in original It.58 close:** per-block gallery layouts beyond It.65 settings — shipped later as **58f-i** (lightbox, pricing/stats motion, islands). User-facing drafts: `docs/marketing/ARTICLE_30–32_*.md`.

---

## 2. Shipped after It.58 close (58f-i + islands)

| Slice | Deliverable | Doc |
|-------|-------------|-----|
| Islands **A–F** | `PUBLIC_ISLANDS`, generic split, admin preview parity | [REACT_SHORTCODE_ISLANDS.md](REACT_SHORTCODE_ISLANDS.md) |
| **58f-i-a** | `[feature-gallery]` v2 attrs | [PAGE_FIELD_COMPOSER_PLANNED.md](PAGE_FIELD_COMPOSER_PLANNED.md) |
| **58f-i-b/e** | `[section-band]` + reveal/hover presets | same |
| **gallery-carousel** | Island + shortcode | same |
| **58f-i-e** | `[pricing-table]` billing toggle island | same |
| **58f-i-f** | `[stats-row]` count-up island | same |
| **58f-i-g** | `PaginiumMediaGallery` (YARL) — prose + feature gallery lightbox | [GALLERY_LIGHTBOX_PLANNED.md](GALLERY_LIGHTBOX_PLANNED.md) |
| **58f-i-h** | Prose insert: `data-gallery` groups + `data-slideshow="off"` | [GALLERY_LIGHTBOX_PLANNED.md](GALLERY_LIGHTBOX_PLANNED.md) |

**Release:** **`v2.1.0-beta.96`** (2026-10-06) — Experience B1–B2 motion — [RELEASE_2_1_0_BETA_96.md](../RELEASE_2_1_0_BETA_96.md) · gallery/lightbox **`beta.95`**: [RELEASE_2_1_0_BETA_95.md](../RELEASE_2_1_0_BETA_95.md).

**Admin:** after deploy, open editor or `GET /api/admin/shortcodes` once so `seedMissingBundled()` upgrades **feature-gallery v2**, **section-band v2**, **pricing-table/plan v2**, **stats-row v2**.

---

## 3. Next queue (not It.58)

| Priority | Item | Doc |
|----------|------|-----|
| 1 | **PUBLIC_EXPERIENCE Phase B** — motion presets (**58f-i-g…k** lightbox ✅) | [PUBLIC_EXPERIENCE_ROADMAP.md](PUBLIC_EXPERIENCE_ROADMAP.md) |
| 2 | **58f-i-d** (optional) — section background crossfade | [PAGE_FIELD_COMPOSER_PLANNED.md](PAGE_FIELD_COMPOSER_PLANNED.md) |
| 3 | Callout colors → `--color-primary` (optional) | [SHORTCODE_COMPOSER.md](SHORTCODE_COMPOSER.md) |

**It.97** (CSP embed facades) remains **deferred** — prefer islands + allow-listed embeds.

---

## 4. Verification

- `./scripts/iteration-gate.sh` before every push.  
- SK/EN user-facing strings for new editor fields.  
- [CHANGELOG.md](../../CHANGELOG.md) `[Unreleased]` for shipped slices.

---

## 5. Slovak pointer

SK summary of It.58 status: [ITERATION_58.md](../../sk/ITERATION_58.md).
