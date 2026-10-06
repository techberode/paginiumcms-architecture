# Gallery & lightbox — It.58f-i (complete)

> **Captured:** October 4, 2026 · **Closed:** October 6, 2026 — release **`v2.1.0-beta.95`**  
> **Status:** ✅ **58f-i-g…k** complete — unified `PaginiumMediaGallery`, prose + feature gallery + `[media-gallery]`, video/embed slides, `?slide=` deep links  
> **Release notes:** [RELEASE_2_1_0_BETA_95.md](../RELEASE_2_1_0_BETA_95.md) · **Operators:** [GALLERY.md](../user/GALLERY.md), [MEDIA_IN_CONTENT.md](../user/MEDIA_IN_CONTENT.md)

`PaginiumMediaGallery` (YARL + Captions/Counter/Thumbnails/Zoom/Slideshow) lives in the public bundle; prose hosts and feature gallery share the facade. Legacy `ProseImageLightboxModal` removed in **beta.95**.

---

## 1. Principles (security + architecture)

- **No** gallery JS/HTML pasted into Markdown; **no** third-party CDN scripts.
- **CSP:** gallery runs only from the **Vite `'self'` bundle** (same as islands).
- **Images:** allow-listed public media URLs.
- **Video slides:** DAM `<video>` and/or existing allow-listed embeds (`paginium-external-embed`, YouTube nocookie, Vimeo) — slides typed in React, not author iframes.
- **Autoplay slideshow:** optional; `prefers-reduced-motion`; pause when tab hidden.
- **Theme:** gallery chrome uses **`--color-primary`**, `--color-surface`, `--color-text` (appearance scheme + visitor light/dark) — not hard-coded emerald/indigo only.
- **Admin** stays Tailwind; gallery UI is public (and preview) only.

---

## 2. Implementation strategy — React image & video gallery

**Recommended stack (to evaluate in 58f-i-g spike):**

| Option | Pros | Cons |
|--------|------|------|
| **[Yet Another React Lightbox](https://yet-another-react-lightbox.com/)** + **Video plugin** | Thumbnails, counter, keyboard, a11y; video slide type | Bundle size; style override to Paginium tokens |
| Extend in-house `ProseImageLightboxModal` | No new dep | Duplicated work vs feature modal; video harder |

**Decision for planning:** target **YARL (or equivalent)** behind a single facade:

```text
PaginiumMediaGallery (facade)
  ├── slides: { type: 'image' | 'video' | 'embed', src, title, description }[]
  ├── open(index)
  └── plugins: thumbnails, counter, autoplay (optional v1)
```

**Replace over time:** `ProseImageLightboxHost` + `FeatureGalleryModal` call the same facade.

**v1 toolbar (in scope):** close, prev/next, counter, thumbnail rail, optional slideshow play/pause, zoom (images).  
**v1 out of scope:** rotate, flip, share, download (demo screenshots).

---

## 3. Planned slices

| Slice | ID | Deliverable |
|-------|-----|-------------|
| **React gallery foundation** | **58f-i-g** | ✅ YARL dep; `PaginiumMediaGallery` + slide model; prose + feature-gallery modal |
| **Per-asset insert opts** | **58f-i-h** | ✅ Media picker: lightbox on/off, **gallery group** id, exclude from slideshow; `data-gallery` / `data-slideshow` → grouped slides |
| **Inline media gallery** | **58f-i-i** | ✅ Shortcode `[media-gallery]` — DAM `ids` (`\|` / `,` paths), columns, grid/masonry, `MediaGalleryIsland` + lightbox |
| **Video gallery** | **58f-i-j** | ✅ Mixed slides: image + DAM video + allow-list embed (YouTube nocookie / Vimeo); prose + `[media-gallery]` + feature modal |
| **Feature gallery parity** | **58f-i-k** | ✅ Grid/slider thumbs (incl. video), `PaginiumMediaGallery`, live `?slide=` ↔ item id |

---

## 4. Suggested implementation order

1. **58f-i-g** — dependency + facade + prose migration.  
2. **58f-i-h** — insert options + grouping.  
3. **58f-i-j** — video/embed slide types.  
4. **58f-i-i** — inline multi-asset block.  
5. **58f-i-k** — feature-gallery cleanup.

Commit **58f-i-f** (stats count-up) before **58f-i-g** code.

---

## 5. Expected files

| Path | Role |
|------|------|
| `frontend/src/components/frontend/mediaGallery/PaginiumMediaGallery.tsx` | Facade + YARL config |
| `frontend/src/components/frontend/mediaGallery/slides.ts` | Build slides from DOM, gallery items, attrs |
| `frontend/src/theme/paginiumMediaGallery.css` | Token overrides for lightbox chrome |
| Removed | `ProseImageLightboxModal.tsx` (use `PaginiumMediaGallery` / `FeatureGalleryModal`) |

---

## 6. Related code today

| Area | Path |
|------|------|
| Prose | `ProseImageLightboxHost.tsx`, `utils/proseImageLightbox.ts` |
| Feature gallery | `FeatureGalleryModal.tsx`, `FeatureGalleryGrid.tsx` |
| Insert | `proseImageInsert.ts`, `MediaPickerModal.tsx` |
| Video | `:::video`, embed sanitizer, [MEDIA_IN_CONTENT.md](../user/MEDIA_IN_CONTENT.md) |

---

## 7. Acceptance

- Public CSP unchanged (`script-src 'self'`).  
- `data-lightbox="off"` still respected.  
- Vitest for slide collection / grouping; gate green; SK/EN editor copy.
