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

## YouTube / Vimeo embed (`:::embed`) — not recommended

The product can **save** allow-listed YouTube/Vimeo URLs as `:::embed` shortcodes (permission **`content:embed-external`**, CSP `frame-src` on the server). **Playback on the public site is unreliable** in common browsers (reported blank players in Chrome and Floorp even when CSP and permissions are correct). This path is **deferred** — no further investment until a reproducible upstream fix is agreed.

**Use instead:**

| Need | Approach |
|------|----------|
| Video in an article | Upload to Media Library → **Insert video** |
| Link to YouTube | Plain Markdown link `[Watch on YouTube](https://…)` |
| Screenshot with explanation | Image + **caption** (above or below) |
| Map | Settings → Company map URL or `[widget type="map-embed"]` (Google embed only) |

If embed blocks remain in old content, they may show an empty frame; replace them with self-hosted video or a link when editing.

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
- [ISS-193](../ISSUES.md#iss-193) — external video embed display deferred  
