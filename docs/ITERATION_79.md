# Iteration 79 — DAM video (secure upload + content embed)

> **Status:** ⏳ planned  
> **Priority:** 🟡  
> **Wave:** Post-HE DAM & security (It.78–79)  
> **Depends on:** [It.72](ITERATION_72.md) media storage MVP · [It.78](ITERATION_78.md) unified upload policy · [It.24](ITERATION_24.md) DAM · [It.55](ITERATION_55.md) Tiptap editor  
> **Follows:** images/PDF in Media Library

## Goal

Allow **self-hosted video** in the Media Library and safe embedding in pages/articles. Videos are stored as binary objects through the It.72 storage driver; metadata stays flat-file SSOT (`registry.json`, sidecars).

**Strict rule:** only files uploaded through the DAM pipeline may be embedded. Arbitrary external video URLs, `<iframe>` embeds (YouTube/Vimeo), and client-supplied storage paths are **out of scope** for this iteration.

---

## Supported formats (MVP)

| MIME | Extension | Magic-byte check |
|------|-----------|------------------|
| `video/mp4` | `.mp4` | ISO BMFF `ftyp` box |
| `video/webm` | `.webm` | EBML / WebM signature |

Optional later: `video/ogg` (Theora) — not in MVP.

---

## Upload policy profile: `media-video`

Uses [It.78](ITERATION_78.md) `UploadPolicyEngine` profile `media-video`:

- Intersection with `media.allowedMimeTypes` **and** explicit video MIME allow-list.
- Separate size cap: `media.maxVideoUploadSizeKb` (default e.g. 102400 = 100 MB; configurable, max ceiling in schema).
- Same filename guards, auth (`media:write`), CSRF, and audit as image upload.
- Optional per-site daily video quota (bytes) via It.78 quota guard.
- Reject polyglot files (e.g. MP4 with embedded HTML/script markers in metadata — strip or reject per probe rules).

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `MediaFormats` | register video MIME types, `isVideoMime()`, container sniffing |
| `MediaRepository` | filter `type=video`; upload/delete via storage driver |
| `MediaController::serveFile` | `video/*` served `inline` with `nosniff`; optional `Accept-Ranges` follow-up |
| `TiptapHtmlRenderer` | render `video` node → `<video controls playsinline preload="metadata">` |
| `ContentSecuritySanitizer` | allow `video`, `source`; `src` only same-origin `/storage/` or `/api/media/file/` |
| Markdown | `:::video` shortcode or guarded HTML block parsed to canonical video node |

Settings additions under `media`:

```yaml
media:
  allowedMimeTypes: ... # admin adds video/mp4,video/webm
  maxVideoUploadSizeKb: 102400
  videoPosterRequired: false   # optional: require poster image from library
```

---

## Frontend

| Area | Change |
|------|--------|
| `MediaManager` | type filter `video`; `<video>` thumbnail/preview; size warning |
| `MediaPickerModal` | mode `video` for editor; no image-only filter when picking video |
| `WysiwygEditor` | Tiptap `Video` extension; `insertVideo(url, poster?)` |
| `MarkdownEditor` | insert `:::video` or markdown HTML snippet from picker |
| `sanitizeHtml.ts` | allow `video`, `source`; attrs `src`, `type`, `controls`, `poster`, `preload` |
| Public render | `<video>` in content body with responsive wrapper |

Editor profile: new capability `video` (default off in `minimal`; on in `standard`/`full`).

---

## URL and embed model

| Rule | Implementation |
|------|----------------|
| Stable reference | content stores media path or id resolved to current public URL |
| No arbitrary URL | sanitizer drops `src` not matching allow-list |
| Poster | optional second media pick (image only) |
| Autoplay | **disabled** in MVP (`controls` only; no `autoplay` attr) |
| Download | browser default; no forced attachment for video |

Private/signed URL policy aligns with It.72 remainder when S3 driver ships.

---

## Security (strict)

1. **Upload:** It.78 `media-video` profile; magic bytes mandatory.
2. **Serve:** only registered media paths; path traversal blocked; nosniff.
3. **Embed:** backend + frontend sanitizers; no `<iframe>`, `<object>`, `<embed>` for video in this iteration.
4. **XSS:** no user-controlled `on*` attributes; no `javascript:` URLs.
5. **DoS:** size cap + optional quota; reject uploads over cap before write.
6. **Audit:** upload/delete logged via It.78 audit logger.
7. **Permissions:** `media:write` upload; public read follows existing public media policy.

---

## Out of scope

- YouTube/Vimeo/oEmbed iframe embeds.
- Video transcoding, HLS/DASH, adaptive bitrate.
- Subtitles/captions track upload (future).
- AI-generated video.
- Replacing images in gallery with video backgrounds.

---

## Tests

- Upload valid MP4/WebM → registry + binary on disk/driver.
- Reject: wrong magic bytes, double extension, oversize, MIME mismatch, unauthenticated.
- `type=video` list filter; editor pick flow integration test.
- Tiptap round-trip: insert video → save → render HTML contains `<video`.
- Sanitizer strips external URL, `<iframe>`, event handlers.
- `MediaControllerTest` serves video with correct `Content-Type`.
- Editor profile without `video` blocks insert.
- Regression: existing image/PDF uploads unchanged.

---

## Definition of Done

- [ ] MP4 and WebM upload through Media Library with It.78 policy profile.
- [ ] Video embeddable in Markdown and WYSIWYG from library picker only.
- [ ] Public render shows controls; no autoplay; sanitizers agree BE/FE.
- [ ] Separate video size setting documented and enforced.
- [ ] PHPUnit + Vitest coverage for upload, render, and sanitizer bypass attempts.
- [ ] SK/EN docs and CHANGELOG updated.

## Related

[It.78 Upload policy](ITERATION_78.md) · [It.72 Storage drivers](ITERATION_72.md) · [It.24 DAM](ITERATION_24.md) · [It.55 Tiptap](ITERATION_55.md)
