# Media in pages and articles

How to insert **images**, **self-hosted video**, and **captions** from the content editor. For hero placement and crop on **pages**, see [PAGE_HERO_IMAGES.md](PAGE_HERO_IMAGES.md). For **article** SEO heroes, see [BLOG_ARTICLE_HERO.md](BLOG_ARTICLE_HERO.md).

## Media Library insert flow (Markdown and WYSIWYG)

1. Place the **text cursor** in the body where the asset should appear.
2. Toolbar → **Insert image** or **Insert video** (opens Media Library).
3. In **Before insert (optional)** at the top of the modal:
   - Enable **Add image/video caption** when you need a visible title (e.g. *Fig. 1.1/3 …*).
   - Choose **Caption position**: **Below** (default) or **Above** the image or video.
   - For **images** only: optional **Alt text** and **Open in lightbox on click**.
4. Click the file in the grid — content is inserted at the **cursor**, not at the end of the document.
5. Save and publish.

Shortcodes and widgets from the editor sidebar also insert at the **last cursor position** in the Markdown surface.

## Image with caption (recommended for screenshots)

When a caption is enabled, storage uses `<figure class="paginium-figure">` with `<figcaption>`. Class **`paginium-figure--caption-top`** is added when the caption is above the image.

The caption text appears on the public page in the chosen position. In the **lightbox modal**, the same text is shown **under** the enlarged image (independent of above/below in the article).

Without a caption, behaviour is unchanged: Markdown `![alt](url)` or inline `<img>` (with optional `data-lightbox="off"`).

## Unified lightbox (prose and galleries)

Since **beta.95** (`PaginiumMediaGallery`), these share one full-screen viewer (thumbnails, counter, prev/next, optional slideshow):

| Source | How it opens |
|--------|----------------|
| Prose image from library | Click image (unless lightbox off in picker) |
| Prose `:::video` / `<video src="/storage/…">` | Click the player |
| Allow-listed YouTube/Vimeo iframe | Click embed (same album if `data-gallery` matches) |
| `[feature-gallery]` / `[media-gallery]` | Click tile in grid or slider |

**Album grouping (prose):** in the media picker, set **Gallery group** → adds `data-gallery="your-id"` so only siblings with the same id appear in one carousel.

**Slideshow skip:** **Exclude from slideshow** → `data-slideshow="off"` on that asset.

**Shortcode galleries:** see [GALLERY.md](GALLERY.md) and [SHORTCODE_COOKBOOK.md](SHORTCODE_COOKBOOK.md).

## Self-hosted video (recommended)

For tutorials and demos, prefer **Media →** upload **MP4** or **WebM**, then **Insert video**.

Optional caption in the picker adds lines to the `:::video` block:

```markdown
:::video
src: /storage/app/content/media/demo.webm
caption: Fig. 2.1 — overview of the admin panel
captionPosition: above
:::
```

Omit `captionPosition` (or use `below`) when the title sits under the player. Public output is a native `<video controls>` player inside a figure when a caption is set.

## YouTube / Vimeo embed (`:::embed`)

Allow-listed YouTube (nocookie) and Vimeo URLs can be stored as `:::embed` shortcodes when the role has **`content:embed-external`**. Production must allow **`frame-src`** for those hosts (see deploy CSP snippets).

The public HTML pipeline expands embeds to `<iframe class="paginium-external-embed">` and **preserves** that iframe through content security sanitization (even when `iframe` is not listed in **Settings → Allowed HTML tags**). If an embed still does not show, re-save the page after deploy and verify CSP with `curl -sI … | grep -i content-security-policy`.

### Layout: alignment and width

Use the editor toolbar **Insert YouTube/Vimeo** modal (Markdown and WYSIWYG). Besides provider and video ID, you can set:

| Control | Stored in Markdown | Public effect |
|---------|-------------------|---------------|
| **Horizontal alignment** | `align: left` \| `center` \| `right` | CSS class `paginium-external-embed--align-*` on the iframe |
| **Player width** | `maxWidth: 280`–`1280` (pixels) | Inline `max-width` on the iframe (height follows **16:9** via theme CSS) |

The modal **layout preview** shows sample paragraphs above and below the player so you can see placement inside the prose column before insert.

Example block (you can also edit these lines by hand in Markdown):

```markdown
:::embed
provider: youtube
id: dQw4w9WgXcQ
align: center
maxWidth: 960
:::
```

Inline form: `:::embed provider="youtube" id="dQw4w9WgXcQ" align="center" maxWidth="960" :::`

**Defaults:** `align: center`, `maxWidth: 560`. Blocks created before layout options existed omit `align` / `maxWidth`; the theme still caps width at 560px and centers embeds in `.paginium-prose`.

**Site-wide overrides:** Theme Studio → `assets/theme.css` can target `.paginium-prose iframe.paginium-external-embed` (e.g. larger default `max-width`, border radius). Per-embed `maxWidth` in the shortcode wins over the theme cap when set.

**YouTube “playback disabled on other websites”:** That message comes from the video owner’s YouTube settings, not from Paginium alignment. Allow embedding in YouTube Studio, use a link, or host the file with **`:::video`**.

**Alternatives:**

| Need | Approach |
|------|----------|
| File you host | Media Library → **Insert video** (`:::video`) |
| Link only | `[Watch on YouTube](https://…)` |
| Screenshot | Image + **caption** |

See [ISS-193](../ISSUES.md#iss-193) for the sanitizer regression that caused blank embeds.

## Permissions

| Capability | Permission |
|------------|------------|
| Insert images / DAM video | `content:edit` (typical editor) |
| Save `:::embed` (YouTube/Vimeo) | `content:embed-external` |
| Trusted raw HTML blocks | `content:trusted-html` |

See [ACCESS_CONTROL.md](ACCESS_CONTROL.md).

## Related

- [CONTENT_EDITOR.md](CONTENT_EDITOR.md) — save flow, SEO, diagnostics  
- [SHORTCODES_AND_WIDGETS.md](SHORTCODES_AND_WIDGETS.md) — landing widgets, maps  
- [ISS-193](../ISSUES.md#iss-193) — sanitizer stripped allow-listed embed iframes (fixed)  
