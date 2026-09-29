# Blog article hero (OG image under the title)

Article heroes use the **SEO / OG image** (or featured image) shown under the title on `/blog/{slug}` and as the card thumbnail on `/blog`.

## Front matter (editor → Article hero focus panel)

| Key | Values | Meaning |
|-----|--------|---------|
| `heroFocusX` | 0–100 | Horizontal crop anchor when using **Fill frame** |
| `heroFocusY` | 0–100 | Vertical crop anchor when using **Fill frame** |
| `heroFit` | `contain` (default) · `cover` | **Show full image** (letterbox) vs **fill frame** (crop) |

Defaults: **`heroFit: contain`** so wide **21:9** masters (see [PAGE_HERO_IMAGES.md](PAGE_HERO_IMAGES.md)) are not center-cropped on the detail page.

## Public rendering

| Surface | Component | Behaviour |
|---------|-----------|-----------|
| Article detail | `ArticleHeroImage` | `contain` → letterbox in ~21:9 band (max height 480px); `cover` → `object-cover` + focus |
| Article list cards | `ArticleHeroCardImage` | Same `heroFit` + focus as detail inside the list card height band |

List cards use a fixed height (`compact` / `standard` / `comfortable` in Settings → Content → blog layout). With **contain**, the full banner stays visible with side or top/bottom bars; with **cover**, the card crops like a photo grid.

## Recommended image size

Use the same **21:9** master as page heroes (**2560 × 1097** or similar). Social previews may still use **1200 × 630** OG variants from the media pipeline; the article hero displays the assigned OG/featured asset.

## API / storage

Saved via content PUT with `applyArticleHeroFrontMatter` on the backend (`heroFocusX`, `heroFocusY`, `heroFit` in front matter).
