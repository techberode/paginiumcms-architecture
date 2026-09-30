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

## YouTube / Vimeo embed (`:::embed`)

Allow-listed YouTube (nocookie) and Vimeo URLs can be stored as `:::embed` shortcodes when the role has **`content:embed-external`**. Production must allow **`frame-src`** for those hosts (see deploy CSP snippets).

The public HTML pipeline expands embeds to `<iframe class="paginium-external-embed">` and **preserves** that iframe through content security sanitization (even when `iframe` is not listed in **Settings → Allowed HTML tags**). If an embed still does not show, re-save the page after deploy and verify CSP with `curl -sI … | grep -i content-security-policy`.

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
- [ISS-193](../ISSUES.md#iss-193) — external video embed display deferred  
