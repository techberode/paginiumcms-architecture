---
title: Shortcode cookbook
description: Plain-language examples for every bundled shortcode and widget (beta.95)
icon: material/book-open-page-variant
---

# Shortcode cookbook

> **Audience:** operators who edit pages in Outline, Shortcodes, or Developer mode — not PHP developers.  
> **Companion:** [Shortcodes and widgets](SHORTCODES_AND_WIDGETS.md) (syntax rules) · [Landing page](LANDING_PAGE.md) · [Galleries](GALLERY.md) · [Media & lightbox](MEDIA_IN_CONTENT.md#unified-lightbox-prose-and-galleries)

---

## How it works (one pipeline)

Think of three roles:

| Role | What it is | Your job |
|------|------------|----------|
| **Storage** | Page body = Markdown + shortcode **text** | Write or insert tags; save |
| **Kitchen (PHP)** | `ShortcodeExpanderService` turns tags into **HTML** | Nothing — happens on every public request / preview |
| **Waiter (React)** | Some HTML marks **islands**; the public bundle hydrates them | Nothing — e.g. gallery lightbox, carousel |

You never paste finished HTML for marketing blocks. If the site shows raw `[feature-card`, the tag is disabled, misspelled, or attributes are outside the opening `[…]`.

**Islands** (need JS on the public site): `feature-gallery`, `gallery-carousel`, `media-gallery`, dynamic blocks like `latest-articles`, staff cards. Static blocks (hero, grids, FAQ) are HTML + CSS only.

---

## Marketing shortcodes (examples)

### `showcase-hero` — presentation hero

```markdown
[showcase-hero badge="HYBRID CMS" title="Ship pages without a database" subtitle="Flat files, admin SPA, one expand pipeline." terminal="curl -s /api/health" cta="Features" href="/features" cta2="Blog" href2="/blog"/]
```

Hide parts with bool attrs: `show-terminal="false"`, `show-cta2="false"`.

### `landing-hero` — simpler hero (+ optional DAM media)

```markdown
[landing-hero title="Agency site in a week" subtitle="Compose with blocks, publish as files." cta="Contact" href="/contact" show-media="false"/]
```

Media attrs (`image`, `src` video) come from the insert modal when enabled.

### `section-head` — anchor + title block

```markdown
[section-head anchor="pricing" eyebrow="Plans" title="Simple pricing" subtitle="No seat tax on self-host."/]
```

Link visitors with `https://yoursite/pricing#pricing`.

### `section-band` — full-width wrapper (nested blocks inside)

```markdown
[section-band tone="muted" padding="lg"]
[stats-row animate="count-up"]
[stat-item value="99.9%" label="Uptime"/]
[stat-item value="SK/EN" label="Locales"/]
[/stats-row]
[/section-band]
```

Open the insert modal for allowed `tone`, `padding`, and motion attrs.

### `stats-row` + `stat-item`

```markdown
[stats-row animate="count-up"]
[stat-item value="24/7" label="Monitoring"/]
[stat-item value="2FA" label="Admin login"/]
[/stats-row]
```

### `feature-grid` + `feature-card`

```markdown
[feature-grid columns="3"]
[feature-card title="Editor"]WYSIWYG + Markdown in one body field.[/feature-card]
[feature-card title="Media"]Upload once in the library; reuse in shortcodes.[/feature-card]
[feature-card title="Security"]CSRF on writes; allow-listed public HTML.[/feature-card]
[/feature-grid]
```

Inner text supports `**bold**` and paragraphs.

### `stack-grid` + `stack-tag`

```markdown
[stack-grid]
[stack-tag label="PHP 8.5"/]
[stack-tag label="React"/]
[stack-tag label="Flat-file"/]
[/stack-grid]
```

### `testimonial`

```markdown
[testimonial quote="We replaced three plugins with shortcodes." author="Ops lead" role="Hosting partner"/]
```

### `pricing-table` + `pricing-plan` + `pricing-feature`

```markdown
[pricing-table columns="2" billing-toggle="monthly-yearly"]
[pricing-plan name="Starter" price="€29" period="/mo" cta="Start" href="/contact" variant="default"]
[pricing-feature text="5 pages"/]
[pricing-feature text="Gallery block"/]
[/pricing-plan]
[pricing-plan name="Studio" price="€79" period="/mo" cta="Talk to us" href="/contact" variant="featured"]
[pricing-feature text="Unlimited pages"/]
[/pricing-plan]
[/pricing-table]
```

### `cta-banner`

```markdown
[cta-banner title="Ready?" subtitle="Book a demo call." cta="Contact" href="/contact" tone="primary"/]
```

### `alert-box`

```markdown
[alert-box tone="info"]
Migration note: re-save the page after upgrading CMS so bundled shortcode versions refresh.
[/alert-box]
```

### `faq-list` + `faq-item`

```markdown
[faq-list]
[faq-item question="Do I need Redis?" answer="No. File cache is the default."/]
[faq-item question="Can I use custom HTML?" answer="Only in trusted html-safe blocks for privileged roles."/]
[/faq-list]
```

### `link-row` + `link-chip`

```markdown
[link-row]
[link-chip label="Documentation" href="/about"/]
[link-chip label="Status" href="https://status.example.com"/]
[/link-row]
```

### `coming-soon`

```markdown
[coming-soon title="Partner portal" subtitle="Available Q2."/]
```

### `visual-frame` — alignment / width wrapper (often auto-inserted by modal)

```markdown
[visual-frame align="center" max-width="720"]
[feature-card title="Centered card"]Narrow column for readability.[/feature-card]
[/visual-frame]
```

---

## Gallery shortcodes

### `feature-gallery` — admin **Feature gallery** catalog

One shared catalog (`/gallery` admin). The shortcode **displays** published items.

```markdown
[feature-gallery title="Selected work" tag="" layout="grid" columns="3" modal-caption-style="below"/]
```

| Attribute | Meaning |
|-----------|---------|
| `title` | Heading above grid/slider |
| `tag` | Empty = all published; `web` = only items with that module tag |
| `layout` | `grid` (default), `slider`, `hero-strip` |
| `columns` | `2`–`4` for grid |
| `modal-caption-style` | Lightbox captions: `below`, `overlay`, `side` |

**Deep link:** `/features?slide=gallery_abc123` opens the lightbox on that item (id from admin). Tag filter: `?slide=web` focuses the tag and opens the first matching slide.

Supports **images and video** paths in the catalog; click opens unified `PaginiumMediaGallery` (thumbnails, counter, video playback).

Full walkthrough: [GALLERY.md](GALLERY.md).

### `media-gallery` — hand-picked DAM files (not the feature catalog)

```markdown
[media-gallery title="Office tour" ids="media/lobby.jpg|media/team.mp4|media/detail.webp" columns="3" layout="grid" modal-caption-style="overlay"/]
```

| Attribute | Meaning |
|-----------|---------|
| `ids` | Registry paths under `media/…`, separated by `\|` or `,` |
| `columns` | `2`, `3`, or `4` |
| `layout` | `grid` or `masonry` |
| `modal-caption-style` | Same as feature gallery |

Images and **DAM video** (`video/mp4`, `webm`) expand to a grid; React island adds the same lightbox as prose images.

### `gallery-carousel` — carousel island

Use the insert modal for attrs (see admin shortcode definition). Expands to `data-island="gallery-carousel"`.

---

## Content & team blocks

### `latest-articles`

```markdown
[latest-articles label="From the blog" count="5" speed="normal"/]
```

News ticker / list — dynamic fetch on the public site.

### `staff-card` / `staff-team`

```markdown
[staff-card user="user-uuid-or-login"/]
```

```markdown
[staff-team type="contact" id=""/]
```

Team types: `support`, `contact`, `editorial`, `ops`, `external`, `custom` (see admin schema).

### `document-link`

```markdown
[document-link href="/storage/media/brochure.pdf" label="Download brochure (PDF)"/]
```

---

## Widgets (dashboard-style, same body field)

Self-closing only:

```markdown
[widget type="kpi" title="Active users" value="1.2k"/]
```

```markdown
[widget type="bar-chart" title="Weekly signups" labels="Mon|Tue|Wed" values="12|19|15"/]
```

```markdown
[widget type="map-embed" title="Office" src="https://www.google.com/maps/embed?pb=…"/]
```

```markdown
[widget type="form-cta" title="Questions?" href="/contact" subject="Landing inquiry"/]
```

Pipe `|` separates list items in several widget types. Full table: [SHORTCODES_AND_WIDGETS.md §4](SHORTCODES_AND_WIDGETS.md#4-widgets-vs-shortcodes).

---

## Prose media (not shortcodes — same lightbox)

| Insert | Storage | Public lightbox |
|--------|---------|-----------------|
| Image from library | `![alt](/storage/…)` or `<figure>` | Click → gallery (unless `data-lightbox="off"`) |
| Video from library | `:::video` block | Click video → plays in lightbox |
| YouTube/Vimeo | `:::embed` | Allow-listed iframe; grouped with `data-gallery` |

Grouping: set **Gallery group** in the media picker → `data-gallery="portfolio"` on siblings so one lightbox album opens.

Details: [MEDIA_IN_CONTENT.md](MEDIA_IN_CONTENT.md).

---

## After upgrade (beta.95 checklist)

1. Admin → **Shortcodes** — open once (seeds missing bundled definitions).
2. Re-save important landing pages.
3. Rebuild frontend (`npm run build:prod`) on production.
4. Purge content cache if HTML looks stale.

---

## Related

- [SHORTCODES_AND_WIDGETS.md](SHORTCODES_AND_WIDGETS.md)
- [GALLERY.md](GALLERY.md)
- [Architecture: gallery lightbox](../architecture/GALLERY_LIGHTBOX_PLANNED.md)
