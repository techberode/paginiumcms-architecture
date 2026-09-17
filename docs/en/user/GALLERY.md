---
title: Feature gallery
description: Add screenshots to the shared gallery catalog and place them on a page
icon: material/view-gallery
---

# Feature gallery

> One catalog of published images. Page blocks **display** that catalog; they do not store a second set of photos.

Admin → **Feature gallery** (`/gallery`) is the kitchen. A page with `[feature-gallery]` is the table. Media files stay in the DAM (`/media`).

## 1. Add photos (once)

1. Open **Feature gallery**.
2. Click **Add screenshot**.
3. Fill only what you need:

| Field | Required? | What to type |
|-------|-----------|----------------|
| Title | yes | Caption on the tile (`Kitchen`, `Dashboard`) |
| Screenshot | yes | Pick an image from Media |
| Status | yes | **Published** or the public site ignores it |
| Description | no | Text inside the lightbox |
| Module tag | no | Filter label (`web`, `mail`) — **not** a page slug |
| “Learn more” URL | no | Optional lightbox button. Use a full `https://…` URL, or leave empty. A path like `/web` is rejected. |

Repeat for every photo. One form = one image. There is no multi-select on the page editor.

## 2. Show them on a page

1. Settings → Layout → builder **Outline** (`layout.builderMode=outline`). This applies to **pages**, not articles.
2. Edit the page → palette → **Gallery**.
3. **Title** = heading above the grid (`Selected work`).
4. **Feature tag** = leave empty for **all** published photos, or type the same label as on the items (`web`) to show a subset.
5. Save.

The page body stays Markdown:

```markdown
[feature-gallery title="Selected work" tag=""/]
```

In Shortcodes / Developer mode you can type that tag yourself. Articles have no outline palette; paste the same shortcode into Markdown.

## 3. What visitors see

| Surface | Behaviour |
|---------|-----------|
| Editor live preview | Static DAM grid (sandbox iframe, no React) |
| Public page | Same island, hydrated as `FeatureGallerySection` (modal, optional slider) |

A new published photo appears on every unfiltered gallery block. You do not insert the block again.

## 4. Tags vs “several galleries”

There is **one** store (`data/gallery/`). A tag is a sticker on a photo. Two pages can look like two galleries because their blocks filter different stickers:

```markdown
[feature-gallery title="Web" tag="web"/]
[feature-gallery title="Print" tag="print"/]
```

A photo with no tag still appears in blocks that have no tag. `gallery.enabled` and placement (`home` / `/features`) only control the **automatic** home/route section. An explicit page block shows even when that master switch is off.

## 5. Permissions and files

- Mutations need `gallery:manage`.
- Public `GET /api/gallery/public` returns published items only.
- JSON export/import is metadata (paths), not binary files.

Related: [Landing page shortcodes](LANDING_PAGE.md), [Content editor](CONTENT_EDITOR.md), [It.65](../ITERATION_65.md), [It.58f](../ITERATION_58f.md).
