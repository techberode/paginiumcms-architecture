---
title: Shortcodes and widgets
description: Compose pages with shortcode blocks, inner text, outline mode, and dashboard widgets
icon: material/code-braces
---

# Shortcodes and widgets

PaginiumCMS pages and articles share one **body** field. Marketing sections use **shortcodes** (tags like `[feature-card]…[/feature-card]`). Dashboard-style blocks use **widgets** (`[widget type="…" …/]`). Both expand to HTML on the **server** when the public site loads content — the editor stores the shortcode source, not the final layout.

**Related:** [Shortcode cookbook](SHORTCODE_COOKBOOK.md) (every bundled tag + examples) · [Landing page guide](LANDING_PAGE.md) · [Content editor](CONTENT_EDITOR.md) · [Galleries](GALLERY.md) · Admin **Shortcodes** and **Widgets** screens.

---

## 0. How tags become the public page (plain language)

1. You save **shortcode text** in the page body (Outline is the same string with forms).
2. On preview and on the public site, PHP **expands** tags to HTML (classes like `pg-showcase-hero`, islands like `data-island="media-gallery"`).
3. The public React app **hydrates islands** (feature gallery lightbox, media gallery, carousel). Everything else is static HTML + `pgLayout.css`.

You do not need to understand PHP or React to compose landings — only valid tag syntax and published media/gallery items.

---

## 1. Three ways to edit the same body

| Mode | Settings → Layout → Builder mode | Best for |
|------|----------------------------------|----------|
| **Block outline** | `outline` | Drag-and-drop blocks, forms for attributes, landing palette |
| **Shortcodes** | `shortcodes` | Paste or insert tags, full control, copy from docs |
| **Developer** | `developer` | Raw markdown + shortcodes, Git-friendly |

Outline and shortcodes edit the **same string**. Switching modes does not duplicate content.

**Live preview (right pane)** calls the backend expander — it matches the public site. If preview looks correct but the site does not, purge **content cache** and re-save the page.

### Visual insert (Settings → Editor + per-browser)

| Layer | Effect |
|-------|--------|
| **Visual insert modals (shortcodes & widgets)** | Site-wide gate: when off, only the compact insert panel (dropdown / inline picker) is available. |
| **Modal workspace** (checkbox in the page/article editor) | Per-browser preference (`localStorage`). On = full dialog with live preview; off = quick Markdown insert for experienced editors. |
| **Layout & typography in insert modals** | Alignment, max width, text size, emphasis, tone, highlight. Non-default choices wrap markup in `[visual-frame …]…[/visual-frame]`. |
| **Include field** (in modal / widget picker) | Each attribute is included in the generated tag by default; uncheck to **omit** that attribute from the inserted shortcode or widget. |

Shortcode preview uses `POST /api/admin/shortcodes/render-markup`. Widget catalog uses the existing widget preview API.

Embed (YouTube/Vimeo) uses its own modal with player width/alignment — see [Media in content](MEDIA_IN_CONTENT.md#layout-alignment-and-width).

**Roadmap:** server-side **slot toggles** on bundled blocks (e.g. `showcase-hero` without terminal strip) and an admin **shortcode composer** from an internal parts library — see [Shortcode composer roadmap](../architecture/SHORTCODE_COMPOSER.md).

---

## 2. Shortcode syntax (rules)

### Self-closing (one block, attributes only)

Attributes use **double quotes**. Close with `/>` or `/]`:

```markdown
[showcase-hero badge="HYBRID CMS" title="Headline" subtitle="One sentence." terminal="curl /api/health" cta="Overview" href="#section" cta2="Blog" href2="/blog"/]
```

```markdown
[stat-item value="2FA" label="Admin protection"/]
```

### Paired (wrapper + inner content)

Opening tag: **`[name attr="value" …]`** — attributes stay **inside** the opening bracket, not after `]`.

```markdown
[feature-grid columns="3"]
[feature-card title="Flat-file SSOT"]Paragraph text and **markdown** inside the card body.[/feature-card]
[feature-card title="Admin SPA"]Second card.[/feature-card]
[/feature-grid]
```

**Wrong (will not expand):**

```markdown
[showcase-hero] title="…" [/showcase-hero]
```

**Wrong (second link):** use `href2="/blog"`, not a second `href`.

### Inner text formatting

| Block type | Inner content |
|------------|----------------|
| `feature-card`, `alert-box`, `pricing-plan` body | Plain text or markdown (paragraphs, `**bold**`, lists) |
| `stats-row`, `feature-grid`, `stack-grid` | Only nested shortcodes, no free text |
| `showcase-hero`, `stat-item`, `testimonial` | Attributes only (self-closing) |

Markdown inside paired blocks is converted when the page HTML is built. Keep one idea per `feature-card` paragraph for readable landing layout.

---

## 3. Bundled marketing shortcodes (cheat sheet)

| Shortcode | Syntax | Notes |
|-----------|--------|--------|
| `showcase-hero` | self-closing | Hero + optional terminal strip + two CTAs; bool attrs `show-badge`, `show-terminal`, `show-cta`, `show-cta2` (default `true`) hide slots on the public site |
| `landing-hero` | self-closing | Simpler hero; optional media attrs (see admin definition) |
| `stats-row` + `stat-item` | paired + self-closing | KPI row |
| `section-head` | self-closing | Anchor + eyebrow + title + subtitle |
| `feature-grid` + `feature-card` | paired | `columns="2"` or `"3"` |
| `stack-grid` + `stack-tag` | paired + self-closing | Tech stack chips |
| `testimonial` | self-closing | `quote`, `author`, `role` |
| `cta-banner` | self-closing | Bottom band CTA |
| `pricing-table` + `pricing-plan` + `pricing-feature` | paired | See [LANDING_PAGE.md](LANDING_PAGE.md) |
| `feature-gallery` | self-closing | Admin **Feature gallery** catalog — grid/slider, lightbox, `?slide=` deep links — [GALLERY.md](GALLERY.md) |
| `media-gallery` | self-closing | Hand-picked DAM **images + video** — `ids="media/a.jpg\|media/b.mp4"`, `columns`, `layout="grid\|masonry"` — [SHORTCODE_COOKBOOK.md](SHORTCODE_COOKBOOK.md#media-gallery--hand-picked-dam-files-not-the-feature-catalog) |
| `gallery-carousel` | self-closing | Carousel island — attrs in admin catalog |
| `section-band` | paired | Full-width section wrapper + nested blocks |
| `latest-articles` | self-closing | Dynamic blog ticker/list |
| `staff-card` / `staff-team` | self-closing | Team/contact cards from users registry |
| `document-link` | self-closing | Download link to allow-listed file URL |
| `visual-frame` | paired | Alignment / max-width wrapper (insert modal) |
| `coming-soon` | self-closing | Restricted / teaser panel |
| `alert-box` | paired | `tone="info|warn|success"` + inner markdown |
| `faq-list` + `faq-item` | paired + self-closing | FAQ accordion via native `<details>` (`question`, `answer`) |
| `link-row` + `link-chip` | paired + self-closing | Pill link row (`label`, `href`) |
| `landing-hero` | self-closing | Optional DAM image/video; bool toggles `show-subtitle`, `show-cta`, `show-media` |

Full landing walkthrough: [LANDING_PAGE.md](LANDING_PAGE.md). Seed body: `backend/resources/content-seeds/paginium-cms-landing.sk.md`.

**Per-shortcode copy-paste examples:** [SHORTCODE_COOKBOOK.md](SHORTCODE_COOKBOOK.md). Slovak summary: [docs/sk/user/SHORTCODES_A_WIDGETY.md](../../sk/user/SHORTCODES_A_WIDGETY.md).

---

## 4. Widgets vs shortcodes

| | **Shortcodes** | **Widgets** |
|---|----------------|-------------|
| **Purpose** | Page/article body sections (hero, grids, CTA) | Reusable KPI / chart / list blocks |
| **Tag** | `[feature-card]`, `[showcase-hero]`, … | `[widget type="kpi" title="…" value="…"/]` |
| **Admin** | **Shortcodes** — edit expand HTML templates | **Widgets** — catalog + preview |
| **CSS** | `pgLayout.css` (`pg-*` classes) | Widget renderer classes (`pg-widget-*`) |

Widgets are intended for structured data displays; shortcodes are for marketing layout. Both pass through the same expand pipeline (max **8** nested passes).

### Bundled widget types (pages and articles)

| `type` | Example | Use |
|--------|---------|-----|
| `kpi`, `kpi-row`, `progress` | KPI cards | Stats bands |
| `bar-chart` | `labels="Mon \| Tue"` `values="40 \| 55"` | Simple horizontal bars (CSS) |
| `data-table` | `headers="Plan \| Price"` `rows="Basic \| 9 \| Pro \| 29"` | Comparison tables |
| `map-embed` | `src="https://www.google.com/maps/embed?pb=…"` | Google embed only (same rule as contact settings) |
| `form-cta` | `href="/contact"` optional `subject="…"` | CTA to contact form |
| `checklist` | `items="A \| B \| C"` | Checkmarked bullet list (CSS) |
| `stat-duo` | `label1` / `value1` / `label2` / `value2` | Two-up stat strip |
| `brand`, `cta`, `quote`, `timeline`, `icon-box`, `profile`, `list` | see **Widgets** admin | Marketing / dashboard blocks |

Editor charts (Mermaid, etc.) remain in the **article editor**; body widgets above expand on the server for public HTML.

---

## 5. Custom 404 and 500 pages

**Settings → Layout → Rozloženie stránky**

- **notFoundPageSlug** — published page slug shown when a public slug does not exist (nav + footer intact). Also used for missing blog articles and deep unknown paths (e.g. `/old/nested/link`).
- **serverErrorPageSlug** — published page shown when the public React tree throws during render.

Leave empty to use the built-in Paginium error panels (`PublicSystemErrorPanel`). Create dedicated pages (e.g. layout `landing`) for branded errors.

**Alternative (no custom slug field):** publish a normal page (e.g. slug `404`) and add a **Redirect manager** rule `301/302` from `/404` only if needed; for missing URLs the SPA resolves `notFoundPageSlug` automatically — do not rely on nginx alone unless hybrid static mode is configured.

**Checklist when custom 404 “does nothing”:** page **published**; slug in settings matches exactly (no leading `/`); hard refresh after save; confirm `/api/settings/public` includes `layout.notFoundPageSlug`; reserved routes (`blog`, `features`, `cookies`) are not used as the missing slug target.

---

## 6. Public site checklist

1. **Layout template** `landing` for marketing home (with template `home` or slug `home` / `index`).
2. Shortcode **enabled** in admin **Shortcodes** (bundled catalog seeds on first open).
3. After CMS upgrade, open **Shortcodes** once (runs `seedMissingBundled`) and **re-save** the page.
4. **Purge content cache** if HTML looks stale.
5. Production frontend build includes `pgLayout.css` (bundled via `main.tsx`).

---

## 7. Troubleshooting

| Symptom | Fix |
|---------|-----|
| Raw `[showcase-hero` on site | Typo, disabled shortcode, or invalid syntax (see §2) |
| Empty hero band, stats visible | Old `pg-reveal` on hero — upgrade shortcode catalog v2, refresh CSS |
| Primary CTA unreadable (solid fill, no label) | Fixed in FE: prose link color must not override `.pg-btn-primary` |
| Preview OK, public not | Content cache; hard refresh; confirm `html` in `/api/pages/{slug}` contains `pg-showcase-hero` |

---

## 8. Custom shortcodes

Admin **Shortcodes** → Monaco editor for `expand` template and attribute schema. Templates are validated (no `<script>`, allow-listed `pg-*` classes). Use `{{attrName}}` placeholders and `{{content}}` for paired inner body.

---

## Related docs

- [LANDING_PAGE.md](LANDING_PAGE.md) — step-by-step landing
- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) — home + landing routing
- [THEMES.md](THEMES.md) — appearance and Theme Studio
- [PLUGINS.md](PLUGINS.md) — extensions (separate from body widgets)
