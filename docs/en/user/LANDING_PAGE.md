---
title: Build a landing page
description: Compose a marketing home with layout templates and bundled shortcodes (It.84c)
icon: material/rocket-launch
---

# Build a landing page

> **Route:** Pages → create or edit → set **Layout template** to `landing`  
> **Shortcodes:** Settings → Appearance → Layout (builder mode Shortcodes) or page editor insert panel

PaginiumCMS landing pages are **markdown + shortcodes** — no drag-and-drop builder required. Shortcodes expand to safe HTML with allow-listed `pg-*` CSS classes at **render time** (source stays readable in the editor).

---

## 1. Quick setup (5 steps)

1. Create a page (e.g. slug `paginium-cms`).
2. Set **Template** to `landing` (optional chrome hint).
3. Set **Layout template** to `landing`.
4. Publish the page.
5. Open **Shortcodes** insert panel in the editor and compose sections (see §3).

Demo installs include a sample page at `/paginium-cms` when demo mode seeds content.

---

## 2. Bundled shortcodes (marketing set)

| Shortcode | Type | Purpose |
|-----------|------|---------|
| `landing-hero` | self-closing | Headline, subtitle, primary CTA |
| `feature-grid` + `feature-card` | paired | 2–3 column feature grid |
| `stats-row` + `stat-item` | paired + self-closing | KPI / trust metrics row |
| `testimonial` | self-closing | Quote + author + role |
| `pricing-table` + `pricing-plan` + `pricing-feature` | paired | Pricing columns |
| `cta-banner` | self-closing | Bottom call-to-action band |
| `alert-box` | paired | Info / warning / success note |

Styles live in `frontend/src/theme/pgLayout.css` and follow your active **color scheme** tokens.

---

## 3. Example: PaginiumCMS presentation page

```markdown
[landing-hero title="PaginiumCMS" subtitle="Flat-file hybrid CMS for teams who want control." cta="Read the blog" href="/blog"/]

[stats-row]
[stat-item value="100%" label="File SSOT"/]
[stat-item value="CSRF" label="Protected writes"/]
[stat-item value="SK/EN" label="Locales"/]
[stat-item value="OSS" label="Open source"/]
[/stats-row]

[feature-grid columns="3"]
[feature-card title="Admin SPA"]Edit pages, media, and settings in one place.[/feature-card]
[feature-card title="Shortcodes"]Compose landing sections without custom PHP.[/feature-card]
[feature-card title="Classic profile"]Runs without Redis or external DB.[/feature-card]
[/feature-grid]

[testimonial quote="We ship content without migration theatre." author="Platform lead" role="Self-hosted team"/]

[pricing-table columns="3"]
[pricing-plan name="Classic" price="Free" period="" cta="Self-host" href="/contact" variant="default"]
[pricing-feature text="Flat-file storage"/]
[pricing-feature text="Backup export"/]
[/pricing-plan]
[pricing-plan name="Team" price="OSS" period="" cta="Documentation" href="/about" variant="featured"]
[pricing-feature text="Roles & permissions"/]
[pricing-feature text="Editorial workflow"/]
[/pricing-plan]
[/pricing-table]

[cta-banner title="Ready to try it?" subtitle="Clone the repo and run first-run — or use demo mode." cta="Contact" href="/contact" tone="primary"/]
```

---

## 4. Attribute reference (marketing shortcodes)

### `cta-banner`

| Attribute | Example |
|-----------|---------|
| `title` | `Ready to start?` |
| `subtitle` | Supporting sentence |
| `cta` | Button label |
| `href` | `/contact` |
| `tone` | `primary` or `muted` |

### `stats-row` / `stat-item`

Nest self-closing `stat-item` tags inside `stats-row`:

```markdown
[stats-row]
[stat-item value="99.9%" label="Uptime"/]
[/stats-row]
```

### `testimonial`

| Attribute | Example |
|-----------|---------|
| `quote` | Quote text (no extra quotes needed in attribute) |
| `author` | Person name |
| `role` | Title or company |

### `pricing-table` / `pricing-plan` / `pricing-feature`

```markdown
[pricing-table columns="3"]
[pricing-plan name="Pro" price="€29" period="/mo" cta="Choose" href="/signup" variant="featured"]
[pricing-feature text="Feature one"/]
[pricing-feature text="Feature two"/]
[/pricing-plan]
[/pricing-table]
```

`variant`: `default` or `featured` (highlight border).

---

## 5. Security notes

- Shortcode expand templates are validated by `ShortcodeDefinitionPolicy` (no scripts, no arbitrary CSS classes).
- Only `pg-*` classes are allow-listed in bundled definitions.
- Custom shortcodes edited in admin pass the same policy before save.

---

## 6. Troubleshooting

| Symptom | Check |
|---------|--------|
| Raw `[shortcode]` visible on public site | Shortcode disabled in admin or typo in name |
| Unstyled block | Production build must include `pgLayout.css` (imported in `main.tsx`) |
| Nested shortcodes not expanding | Max 8 expand passes — avoid extremely deep nesting |
| Layout looks narrow | Set **Layout template** to `landing` or `hero-content` |

---

## Related

- [Appearance and color schemes](THEMES.md)
- [Iteration 84 — presentation expansion](../en/ITERATION_84.md)
- [Iteration 58 — layout & shortcodes](../en/ITERATION_58.md)
