# Media in pages and articles

How to insert **images**, **self-hosted video**, and **captions** from the content editor. For hero placement and crop on **pages**, see [PAGE_HERO_IMAGES.md](PAGE_HERO_IMAGES.md). For **article** SEO heroes, see [BLOG_ARTICLE_HERO.md](BLOG_ARTICLE_HERO.md).

## Image with caption (recommended for screenshots)

1. In the Markdown editor, use **Insert image** (Media Library).
2. Optionally set **Alt text** (accessibility) and **Open in lightbox on click**.
3. Fill **Caption under the image** when you need a visible title (e.g. *Fig. 1.1/3 Content settings — see the documentation for details*).
4. Save and publish.

When a caption is set, storage uses a safe `<figure class="paginium-figure">` with `<figcaption>`. The same text appears under the image on the public site and in the **lightbox modal** when lightbox is enabled.

Without a caption, behaviour is unchanged: Markdown `![alt](url)` or inline `<img>` (lightbox off).

## Self-hosted video (recommended)

For tutorials and demos, prefer files from **Media →** upload **MP4** or **WebM** (within **Settings → Media** size limits), then **Insert video** in the editor.

Optional caption in the picker adds a `caption:` line to the `:::video` block:

```markdown
:::video
src: /storage/app/content/media/demo.webm
caption: Fig. 2.1 — overview of the admin panel
:::
```

Public output is a native `<video controls>` player (no third-party iframe). Captions render under the player in a figure when set.

## YouTube / Vimeo embed (`:::embed`) — not recommended

The product can **save** allow-listed YouTube/Vimeo URLs as `:::embed` shortcodes (permission **`content:embed-external`**, CSP `frame-src` on the server). **Playback on the public site is unreliable** in common browsers (reported blank players in Chrome and Floorp even when CSP and permissions are correct). This path is **deferred** — no further investment until a reproducible upstream fix is agreed.

**Use instead:**

| Need | Approach |
|------|----------|
| Video in an article | Upload to Media Library → **Insert video** |
| Link to YouTube | Plain Markdown link `[Watch on YouTube](https://…)` |
| Screenshot with explanation | Image + **caption** (above) |
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
