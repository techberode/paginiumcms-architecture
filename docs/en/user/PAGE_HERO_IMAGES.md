# Page hero & blog intro — image size and crop

PaginiumCMS page heroes (including the **`blog`** intro greybox on `/blog`) always use **`object-fit: cover`** inside a fixed aspect frame. The editor **drag-to-reposition** control only changes **`object-position`**; it does not change the frame shape. To lose as little content as possible, generate (or export) images that match the frame and keep important subjects away from the edges.

## Frame aspect ratios (public site)

Implementation: `PageHeroMedia.tsx` (public) and `PageHeroFocusPreview.tsx` (admin preview).

| Viewport | CSS aspect ratio | Width : height |
|----------|------------------|----------------|
| Default (narrow) | `2 / 1` | **2:1** |
| `sm` and up (~640px+) | `21 / 9` | **21:9** (~2.33:1) |

Additional limits (height caps, still `cover`):

| Placement | Max height (typical) |
|-----------|----------------------|
| Blog intro (`/blog`, embed) | ~`22rem` or ~`42vw` |
| Standard page header | ~`52vh` or ~`28rem` |

Because the aspect ratio **changes at the `sm` breakpoint**, a single JPEG cannot be pixel-perfect on every device. Plan for **two crop behaviours**: slightly more **top/bottom** loss on phones (2:1), minimal loss on tablet/desktop (21:9) if you follow the specs below.

## Recommended specs for AI / design export

### Primary recommendation (one master file)

| Field | Value |
|-------|--------|
| **Aspect ratio** | **21:9** |
| **Pixel size** | **2560 × 1097** (or **2520 × 1080**) |
| **Minimum width** | **1920 px** |
| **Colour** | sRGB |
| **Format** | JPEG or WebP (upload via Media Manager) |

Use **21:9** so desktop and tablet frames match the file; phones will crop a strip from the top and bottom—keep faces, logos, and headlines in the **centre safe zone** (see below).

### Safe zone (composition)

Keep all critical content inside roughly:

- **55%** of the width (centred), and  
- **65%** of the height (centred).

Avoid text, faces, or logos in the outer **20%** on any side. After upload, use **Header hero image → drag in the preview** on the page editor to fine-tune.

### If you only have 2:1 assets

| Field | Value |
|-------|--------|
| Aspect ratio | **2:1** |
| Pixel size | **1920 × 960** (or **2400 × 1200**) |

Works well on mobile; on **`sm+`** the frame is wider (21:9), so **`object-fit: cover` crops the left and right**. Prefer 21:9 for new AI generations.

### SEO / Open Graph (same file)

The hero often reuses the page **SEO / OG image** (`ogImage` / `seoImage`, **Auto** hero mode). Classic social preview is about **1.91:1** (**1200 × 630** minimum). A **21:9** master is wider than OG; platforms may crop differently. For social cards where the full image must show, consider a dedicated **1200 × 630** OG asset and a separate **21:9** hero—or accept OG cropping.

## Delivery checklist

1. Export **21:9**, **≥ 1920 px** wide (2560 px preferred).  
2. Upload to **Media →** assign as **SEO image** and/or **Header hero** (single/carousel).  
3. Open the page (slug **`blog`** for the list intro) → **Header hero image** → drag until the preview matches what you want on `/blog`.  
4. Remove duplicate `![…](…)` images from the page body (each locale tab).  
5. Optional: **Media → Preview optimize** or resize preset **1920** if the file is huge; heroes are served with responsive `?w=` (preset **960** for hero thumbs, full width in `srcset` up to **960** + card widths—upload quality still matters for sharpness on large screens).

## Example AI prompt (English)

```text
Ultra-wide website hero photograph, aspect ratio 21:9, 2560x1097 pixels,
single clear subject, composition centered with safe margins,
no important details in the outer 20% edges, soft natural lighting,
no text overlays, no watermarks, photorealistic
```

Adjust style to match your brand; keep the **21:9** and **safe zone** constraints.

## Hero placement (editor)

In **Header hero image → Hero placement**:

| Value | Public behaviour |
|-------|------------------|
| **Auto** | Blog slug embed → intro **greybox**; **landing** layout → SEO image on **showcase / landing** shortcodes; other pages → **full header** band |
| **Full header band** | Wide band under the site nav (title + image/carousel), body in the card below |
| **Intro greybox** | Title above a **rounded card**; image/carousel inside the card (same as `/blog` intro) |
| **Landing inline** | No separate header band; image applies to **showcase-hero** / **landing-hero** blocks in the page body (and drag crop still applies) |

Use **landing inline** for template **home** + layout **landing** with shortcodes. Use **full header** for a classic marketing hero. Use **intro greybox** when you want a contained photo like the blog index.

## Related docs

- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) — blog slug `blog`, page editor  
- [CONTENT_EDITOR.md](CONTENT_EDITOR.md) — SEO fields and media paths  
- [BRANDING.md](BRANDING.md) — default OG image in settings  

## Technical reference (developers)

| Item | Location |
|------|----------|
| Public hero frame | `frontend/src/components/frontend/PageHeroMedia.tsx` |
| Admin drag preview | `frontend/src/components/backend/PageHeroFocusPreview.tsx` |
| Hero settings API | `heroMode`, `heroImages`, `heroFocusX`, `heroFocusY` in page front matter |
| Thumbnail preset | `MEDIA_THUMB_WIDTH.hero` = **960** (`frontend/src/api/media.ts`) |
