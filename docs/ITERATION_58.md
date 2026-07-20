# Iteration 58 – Page layout builder (lightweight web builder)

**Status:** ⏳ Planned (implementation **after It.15**)  
**Wave:** Post-15 Editor & UX  
**Priority:** 🟡 Medium — largest slice in wave

## Summary

Editable **page layout** based on **≥5 fixed templates** (not a full Elementor clone): configurable **Header**, **Footer**, and **Body** as named blocks. Each block holds ordered content slots (text, hero, gallery, CTA, …). Stays compatible with PaginiumCMS flat-file principles — **no SQL**, **no runtime PHP templates required for MVP**.

## Design principles (Paginium-safe)

| Principle | Rule |
|-----------|------|
| Flat-file SSOT | Layout JSON in content front matter or sidecar `{slug}.layout.json` |
| No heavy framework | Prefer composition of existing React components + CSS grid |
| Static-friendly | Layout resolves to HTML cache (align with It.48) |
| Escape hatch | Markdown-only pages still work — layout optional |
| Performance | Lazy-load block editors; public site reads pre-built structure |

## Template catalog (minimum 5)

| ID | Name | Structure |
|----|------|-----------|
| `single` | Single column | header → body → footer |
| `hero-content` | Hero + content | header → hero block → body → footer |
| `two-column` | Sidebar right | header → main + aside → footer |
| `landing` | Marketing | header → hero → features grid → CTA → footer |
| `blog-article` | Article chrome | minimal header → body (reuse article renderer) → footer |

## Block types (v1)

- `richtext` (MD or Tiptap slot)
- `hero` (title, subtitle, image, CTA link)
- `imageGallery` (media IDs)
- `htmlEmbed` (restricted — profile-gated)
- `spacer` / `divider`

## Admin UX

- **Layout editor** route: visual outline (not drag-anywhere pixel editor) — reorder blocks, pick template
- Edit Header/Footer globally or per-page override (Settings vs page FM flag)
- Preview via existing `SitePreviewModal` (It.51)

## Storage sketch

```json
{
  "template": "hero-content",
  "header": { "variant": "default", "blocks": [] },
  "body": [
    { "type": "hero", "props": { "title": "…", "image": "media/…" } },
    { "type": "richtext", "props": { "bodyRef": "main" } }
  ],
  "footer": { "variant": "compact", "blocks": [] }
}
```

## Backend

- `LayoutValidator` + schema version
- Extend `Page` model / API serialize
- Public API returns resolved layout + cached HTML sections

## Dependencies

- ⛔ **[It.15](ITERATION_15.md)** — custom block types as plugins
- ⛔ [It.54/55](ITERATION_54.md) — richtext blocks
- 🟡 [It.48](ITERATION_48.md) — static site generation of layouts
- 🟡 [It.53](ITERATION_53.md) — smooth admin navigation for builder UI

## Out of scope (v1)

- Pixel-perfect free-form canvas
- Third-party React page builders (GrapesJS full import) unless proven lightweight
- Multi-language layout variants (It.18)

## Acceptance criteria

- [ ] 5 templates selectable; at least 2 block types editable
- [ ] Saved layout renders on public `/slug` without admin bundle
- [ ] PHPUnit layout validation rejects unknown block types
- [ ] Vitest block renderer snapshots
- [ ] Documented migration: legacy pages without layout → `single` template default

## Related

- [ITERATION_48.md](ITERATION_48.md) — PHP/static templates
- [ITERATION_31.md](ITERATION_BACKLOG.md) — live preview (complements builder)
