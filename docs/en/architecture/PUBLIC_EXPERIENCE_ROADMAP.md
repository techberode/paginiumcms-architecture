# Public experience roadmap — beyond “recolored template”

> **Captured:** October 5, 2026  
> **Status:** 🟡 active product direction (post **It.58**; gallery **beta.95**, motion **beta.96**)  
> **Audience:** maintainers, agency implementers  
> **Related:** [REACT_SHORTCODE_ISLANDS.md](REACT_SHORTCODE_ISLANDS.md) · [SHORTCODE_COMPOSER.md](SHORTCODE_COMPOSER.md) · [PAGE_FIELD_COMPOSER_PLANNED.md](PAGE_FIELD_COMPOSER_PLANNED.md) · [GALLERY_LIGHTBOX_PLANNED.md](GALLERY_LIGHTBOX_PLANNED.md) · [LANDING_PAGE.md](../user/LANDING_PAGE.md)

---

## 1. Problem we refuse to repeat

Early CMS themes (e.g. classic PHP portal skins) often felt like **one fixed grid**: same rows and columns, only **colors, borders, or column counts** changed. Operators and agencies could not deliver a **distinct presentation site**—only another skin on the same spreadsheet.

**PaginiumCMS must not become:** default landing + appearance preset swap + identical block order. That is a valid **blog/docs** product, but **not** enough for presentation mandates (agency, SaaS marketing, portfolio).

**Product goal:** editors compose **recognizable, motion-aware marketing pages** from a **core library** of blocks and effects—without author-supplied JavaScript, without breaking CSP, flat-file SSOT, or the iteration gate.

This is **not** a goal to clone Webflow timelines or Elementor unlimited CSS. It **is** a goal to ship a **curated experience catalog** (blocks + motion presets + reference compositions) comparable in *client impact* to a small custom React landing—while keeping one audited public bundle.

---

## 2. Principles (security + architecture — non‑negotiable)

| Principle | Rule |
|-----------|------|
| **SSOT** | Page/article body stays Markdown + shortcodes/widgets; no parallel “layout DB”. |
| **Server expand** | Public HTML from PHP (`ShortcodeExpanderService`, dedicated renderers); policy + sanitizer on every path. |
| **No author code** | No `<script>`, no CDN behaviour libraries, no arbitrary classes in Markdown. |
| **CSP** | Public interactivity only from the **Vite `'self'` bundle** (islands, shared hooks). |
| **Effects = enums** | Motion/layout presets are **select values** (`motion="fade-up"`), not free-form CSS/JS. |
| **Reuse anywhere** | Presets apply via **wrapper** (`[visual-frame]`) or **section shell** (`[section-band]`), not one-off per page hacks. |
| **Accessibility** | `prefers-reduced-motion` disables or simplifies animation; no seizure-inducing defaults. |
| **Admin ≠ arbitrary React** | Insert modals expose schema fields; authors never paste JSX. |

Violating any row → do not ship; fix before commit ([`.cursorrules`](../../../.cursorrules) security baseline).

---

## 3. What “dynamic” means in PaginiumCMS

| Layer | Mechanism | Example today |
|-------|-----------|----------------|
| **Structure** | Different shortcodes, order, section shells | `section-band`, `feature-grid`, pricing, FAQ |
| **Media** | DAM heroes, galleries, inline media blocks | `landing-hero`, `[feature-gallery]`, `[media-gallery]` (58f-i-i) |
| **Motion (CSS)** | `pg-reveal`, section hover, layout CSS | `useLandingReveal`, `section-band` reveal/hover |
| **Motion (React)** | Registered islands | `stats-row` count-up, `gallery-carousel`, lightbox facade |
| **Brand** | Appearance schemes + `pgLayout.css` tokens | Theme Studio / layout settings |

**Gap (this roadmap):** a **single motion catalog** in the **shortcode/widget insert modal**, reference **agency-grade** published pages, and **vertical outline starters** so implementers do not clone the Paginium demo IA.

---

## 4. Target mental model for implementers

```text
Core catalog (reviewed)          Editor (modal)              Stored body              Public site
──────────────────────          ──────────────              ───────────              ───────────
Blocks + widgets        →       Pick block + effect   →     shortcode + attrs   →    expand + islands
Motion preset IDs               (enum, SK/EN)               [visual-frame …]         + shared hooks
Section shells                                              [section-band …]
Reference IA (seeds)          Outline starter packs
```

**Agency deliverable checklist** (minimum bar—not “recolored demo”):

1. Unique **information architecture** (not the bundled `paginium-cms-landing` block order).
2. Strong **DAM imagery** (hero 21:9, gallery tags, captions).
3. At least two **section-band** or layout rhythms (contained vs full-bleed, reveal/hover).
4. **Social proof** block (testimonials, stats count-up, or client logos via gallery).
5. **Conversion** path (CTA + contact / form widget + custom 404 if branded).
6. **Motion** from preset catalog (when shipped)—not zero animation, not author scripts.

User-facing walkthrough: [LANDING_PAGE.md §7](../user/LANDING_PAGE.md) (Kebite-style mapping).

---

## 5. Implementation phases (ordered)

Do **not** invent new iteration numbers unless added to [CONTINUATION.md](../CONTINUATION.md). Phases below are **product slices** that fit existing **58f-i** / composer tracks.

### Phase A — Finish gallery/composer queue (in flight)

| Slice | Deliverable | Doc |
|-------|-------------|-----|
| **58f-i-i** | `[media-gallery]` DAM paths, grid/masonry, shared lightbox | [GALLERY_LIGHTBOX_PLANNED.md](GALLERY_LIGHTBOX_PLANNED.md) |
| **58f-i-j** | Video / embed slides in unified gallery | same |
| **58f-i-d** (optional) | Section background crossfade on scroll (core hook) | [PAGE_FIELD_COMPOSER_PLANNED.md](PAGE_FIELD_COMPOSER_PLANNED.md) |

**Exit:** inline curated galleries work for portfolio sections without It.65 tags only.

### Phase B — **Motion preset library** (highest agency impact)

**Status:** ✅ **B1–B3 shipped** (`beta.97`) — motion presets, section-band effects, reading progress, View Transitions, `.pg-glass`; public `useLandingReveal` + `pgLayout.css`.

**User story:** When inserting any shortcode or widget in the visual modal, operator selects **Effect: None | Fade up | Stagger children** once; effect applies on the **public** site wherever that wrapped block appears.

**Storage:** **`[visual-frame]`** wrapper (layout/typography + motion):

- Attrs: `motion` (`none|fade-up|stagger`), `motion-delay` (`normal|short`).
- PHP: `VisualFramePresentation` → classes `pg-motion`, `pg-motion--{preset}`.
- FE public: extended `useLandingReveal` (`pg-motion-visible`); CSS stagger up to 6 direct children.
- FE admin: `VisualInsertTypographyControls`, SK/EN labels.
- **Reduced motion:** CSS `@media (prefers-reduced-motion: reduce)` + IO fallback.

**B3 shipped** (`beta.97`): reading progress bar, View Transitions on public navigate, `.pg-glass` utility — Settings → Layout. **Phase C** reference seeds shipped in same wave — see below.

**Out of scope:** custom easing editor, author keyframes, CDN animation libs.

**Exit:** two landings can share blocks but **feel** different via motion + composition, not only `primary` color.

### Phase C — **Reference compositions** ✅ shipped (post **beta.96**)

| Deliverable | Status |
|-------------|--------|
| **3 publishable seed pages** | ✅ `reference-agency.en.md`, `reference-saas.en.md`, `reference-local-craft.en.md` |
| **Outline starter packs** | ✅ `agency`, `saas`, `local` in `outlinePalette.ts` + Outline UI |
| **Demo mode** | ✅ Demo fixtures seed `/reference-agency`, `/reference-saas`, `/reference-local` |

Paths: `backend/resources/content-seeds/` · [LANDING_PAGE.md](../user/LANDING_PAGE.md).

**Exit:** a maintainer can pitch “here are three unlike demos” without forking React.

### Phase D — Composer depth (parallel / later)

From [SHORTCODE_COMPOSER.md](SHORTCODE_COMPOSER.md) Phase 2–4: internal **parts library**, more slot toggles, optional **theme shell** (ZIP/CSS only for typography/spacing—[ITERATION_SITE_DESIGN.md](../ITERATION_SITE_DESIGN.md) constraints).

**It.97** (CSP embed facades) stays **deferred**; prefer islands + allow-listed embeds.

---

## 6. Explicit non-goals

- Webflow-class interaction designer in admin.
- User-uploaded animation JS, Lottie/CDN GSAP in page body.
- Per-page React routes or author-defined component trees in Markdown.
- SQL layout engine or duplicate SSOT for “page builder state”.
- Abandoning flat-file SSOT for a headless-only “bring your own frontend” as the **default** product (API remains for integrators, but core value is **composed public SPA + admin**).

---

## 7. Verification (each phase)

- `./scripts/iteration-gate.sh` green.
- New preset → regresný test (PHP expand and/or Vitest motion registry).
- Sanitizer allow-list updated for any new `data-*` / classes.
- SK/EN for new editor strings.
- [CHANGELOG.md](../../CHANGELOG.md) + this doc updated on ship.
- Manual smoke: one page with motion preset + `prefers-reduced-motion` in browser devtools.

---

## 8. Success criteria (product)

| Metric | Target |
|--------|--------|
| **Implementer time** | New client landing **without** copying demo markdown order (starter pack + bands + gallery). |
| **Visual variance** | Two reference seeds side-by-side—obvious IA/layout difference, not only hue. |
| **Security** | No new CSP exceptions; no author script surface. |
| **Performance** | Motion uses CSS + one shared hook; islands lazy-bound; no N× CDN libs. |

---

## 9. Queue placement

Insert **Phase B** after **58f-i-i** lands (or parallel if maintainer capacity allows). Update [CONTINUATION.md](../CONTINUATION.md) checkpoint when Phase B starts.

**Working title for Phase B slice:** **It.58f-j-motion** or **Experience B1** — pick one name in CHANGELOG when coding begins; do not fork multiple numbering schemes in docs.

---

## 10. Slovak summary (maintainer pointer)

SK: Tento dokument je **smer produktu**, nie historický log. Cieľ: **nie „excelovská mriežka s inými farbami“**, ale **katalóg blokov + efektov + referenčné skladby** pri zachovaní bezpečnosti (SSOT, CSP, islands). Ďalší krok implementácie: dokončiť **58f-i-i**, potom **motion presety vo `visual-frame`** a **tri referenčné landingy**. Podrobnosti v anglických sekciách vyššie.

---

## 11. Proposed component catalog — fit for PaginiumCMS

External proposal (October 2026): rich React components fed by **semantic shortcode tags** in flat-file storage, effects in the **first-party public bundle**. Below: each item mapped to **status**, **implementation pattern**, and **security notes**.

**Architecture correction (important):** Admin stores shortcode **text** (SSOT), but **public HTML is still expanded on the server** for most blocks (SEO, sanitization, admin preview, static/hybrid modes). React **islands hydrate** markers for interactivity—they do not replace the whole pipeline with “client-only rendering.” **Redis** (It.69) is an optional **derived cache**, not part of the component model.

| Proposed name | Category | PaginiumCMS status | Implementation pattern | Security / ops notes |
|---------------|----------|--------------------|-------------------------|----------------------|
| **PricingToggle** | A | ✅ **Shipped** (`beta.94`) | `[pricing-table billing-toggle="monthly-yearly"]` + `PricingTableIsland`; plans expanded server-side | No client-side price authority; attrs from Markdown only |
| **LiveStatsCounter** | A | ✅ **Shipped** | `[stats-row animate="count-up"]` + `StatsRowIsland` | `prefers-reduced-motion`; no arbitrary targets from author JS |
| **MultiStepForm** | A | ⏳ **New island + API** | Shortcode `[widget type="form-multistep" …]` or dedicated shortcode; **POST** to existing contact/API with **CSRF**, server validation, rate limit | Not “JSON only at end” without PHP auth; reuse contact/form patterns |
| **InteractiveCalculator** | A | ⏳ **New island** | Self-closing shortcode with **enum attrs** (sliders as preset steps, not free formulas); marketing numbers **hard-coded or settings**, not user SQL | No `eval`; no outbound fetch unless allow-listed |
| **BeforeAfterSlider** | B | 📋 **Planned** (58f-i remainder) | Island + two **DAM** image paths in attrs; server emits `<section class="pg-island--before-after" data-before="…" data-after="…">` | Paths via registry / `DamMediaUrl`; no external URLs |
| **AnimateOnScroll** | B | 🟡 **Partial** | **Phase B:** `visual-frame motion="fade-up|stagger|…"` + `useLandingReveal` / shared hook; section-level via `[section-band reveal="scroll"]` | CSS + IO; reduced-motion off |
| **TypewriterEffect** | B | ⏳ **New island or CSS** | Hero attr e.g. `showcase-hero` extension or `[hero-typewriter phrases="…"]` with **max length** and phrase count cap | Avoid unbounded strings in attrs; no document.title spam |
| **ReadingProgressBar** | B | ⏳ **Layout feature** | Public layout shell (article template), not per-shortcode—toggle in settings or front matter | No third-party; article route only |
| **AsyncReviewCarousel** | C | ⚠️ **High caution** | Prefer **manual** testimonials (`[testimonial]`) or **curated JSON** in flat-file synced by **admin job**, not live Google/Facebook scrape in browser | Live third-party APIs → **SSRF**, secrets, ToS, CSP; if ever: server-only fetch + `OutboundUrlGuard` + cache |
| **ReactLeafletMap** | C | 🟡 **Alternative exists** | Today: `[widget type="map-embed"]` with **Google embed allow-list** | Leaflet = new dep + **tile URL allow-list** (SSRF); evaluate vs existing `MapEmbedUrlGuard` policy |
| **CustomAudioVideo** | C | 🟡 **Partial** | `:::video`, DAM video, lightbox **58f-i-j**; custom chrome = island wrapping allow-listed `<video src="/storage/…">` | No arbitrary src; **localStorage** position = privacy note in docs |

**Legend:** ✅ shipped · 🟡 partial / alternative · ⏳ queued · 📋 named in composer plan · ⚠️ needs security design before code

### Recommended priority (agency impact × fit)

1. **Phase B — AnimateOnScroll** as unified motion presets (covers much of category B without N islands).
2. **BeforeAfterSlider** — strong portfolio/agency demo (one island, DAM-only).
3. **MultiStepForm** — only if contact conversion is a product priority (backend work > React).
4. **InteractiveCalculator** — marketing SaaS pages (Paginium vs WordPress narrative)—keep formulas server-defined or settings-backed.
5. **TypewriterEffect** — hero polish (small island).
6. **ReadingProgressBar** — blog UX (layout, not composer).
7. **ReactLeafletMap** / **AsyncReviewCarousel** — defer until explicit ISS + outbound policy; prefer bundled alternatives.

### What not to claim

- **“100 % security because only text in editor”** — text expands to HTML; attrs must stay enum/media allow-list; mutating APIs still need CSRF/auth.
- **“Redis required for components”** — false; Redis is optional cache for derived reads.
- **“React replaces PHP render”** — islands **augment** server HTML; they do not make the backend a dumb file server for arbitrary client apps.

When implementing any row marked ⏳, add: bundled shortcode schema, PHP renderer marker, `PUBLIC_ISLANDS` entry, sanitizer attrs, insert modal fields, SK/EN, Vitest/PHPUnit, [CHANGELOG.md](../../CHANGELOG.md) entry.
