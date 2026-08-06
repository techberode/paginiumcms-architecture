# Iterácia 79 — DAM video (bezpečný upload + vloženie do obsahu)

> **Stav:** ⏳ plánované  
> **Priorita:** 🟡  
> **Vlna:** Post-HE DAM & security (It.78–79)  
> **Závisí od:** [It.72](ITERATION_72.md) media storage MVP · [It.78](ITERATION_78.md) unified upload policy · [It.24](ITERATION_24.md) DAM · [It.55](ITERATION_55.md) Tiptap  
> **Nadväzuje na:** obrázky/PDF v Media Library

## Cieľ

Umožniť **self-hosted video** v Media Library a bezpečné vloženie do stránok/článkov. Binárky cez It.72 storage driver; metadata ostávajú flat-file SSOT.

**Striktné pravidlo:** embed len súborov nahraných cez DAM. Externé URL, YouTube/Vimeo `<iframe>` a client-supplied cesty sú **mimo rozsahu**.

---

## Formáty (MVP)

| MIME | Prípona | Magic bytes |
|------|--------|-------------|
| `video/mp4` | `.mp4` | ISO BMFF `ftyp` |
| `video/webm` | `.webm` | EBML / WebM |

---

## Upload profil `media-video`

Cez [It.78](ITERATION_78.md):

- Prienik `media.allowedMimeTypes` a video allow-list.
- `media.maxVideoUploadSizeKb` (default napr. 100 MB).
- Auth `media:write`, CSRF, audit ako pri obrázkoch.
- Voliteľná denná kvóta cez It.78.
- Odmietnutie polyglot / podozrivých kontajnerov.

---

## Backend / Frontend (prehľad)

- `MediaFormats` — video MIME, `isVideoMime()`, sniffing
- Filter `type=video` v knižnici; `<video>` preview
- Tiptap `Video` node + Markdown `:::video` shortcode
- Sanitizéry BE/FE: `<video>`, `<source>`; `src` len same-origin storage/API
- Editor capability `video` v profile
- **Bez autoplay** v MVP; len `controls`

---

## Bezpečnosť (striktne)

1. Upload cez It.78 profil + magic bytes.
2. Serve len registrované cesty; nosniff.
3. Embed bez iframe/object/embed pre video.
4. Žiadne `on*` atribúty; žiadne `javascript:` URL.
5. Size cap + quota proti DoS.
6. Audit upload/delete.

---

## Mimo rozsahu

- YouTube/Vimeo/oEmbed.
- Transcoding, HLS/DASH.
- Titulky/captions (neskôr).

---

## Definition of Done

- [ ] MP4/WebM upload s It.78 profilom.
- [ ] Vloženie z pickeru do Markdown aj WYSIWYG.
- [ ] Verejný render s controls; BE/FE sanitizéry zhodné.
- [ ] PHPUnit + Vitest (upload, render, bypass pokusy).
- [ ] SK/EN docs + CHANGELOG.

## Súvisiace

[It.78](ITERATION_78.md) · [It.72](ITERATION_72.md) · [It.24](ITERATION_24.md)
