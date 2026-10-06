---
title: "Media gallery a dokončený lightbox (beta.95)"
slug: media-gallery-jednotny-lightbox-beta95
perex: "Shortcode [media-gallery], video a embed snímky v jednom lightboxe a zdieľateľné odkazy ?slide= vo feature galérii — bez CDN skriptov."
kategoria: Novinky
tagy: paginiumcms, galeria, lightbox, shortcode, beta95
serie: "PaginiumCMS po beta.89"
serie_diel: 34
release_anchor: "v2.1.0-beta.95"
---

# Media gallery a dokončený lightbox (beta.95)

*Toto je **34. diel** série. Naväzuje na jednotný lightbox z It.58f-i-g; release **beta.95** uzatvára vlny i–k.*

PaginiumCMS teraz pokrýva tri scenáre jedným viewerom **PaginiumMediaGallery**:

1. Obrázky a video v **tele článku** (prose).
2. **Feature gallery** na landing stránke (mriežka / slider).
3. Kurátorovaný blok **`[media-gallery]`** priamo z Media Library (`ids="media/…"`).

---

## Čo je nové oproti „jednému modalu“

- **Video a embed** (YouTube nocookie, Vimeo, self-hosted mp4) v tom istom lightboxe ako JPEG/PNG/WebP.
- **Feature gallery:** URL `?slide=<id-položky>` otvorí správnu snímku; pri listovaní sa adresa synchronizuje (zdieľateľné linky).
- Shortcode **`[media-gallery]`** — mriežka alebo masonry bez ručného HTML.

---

## Kde nájsť presný návod (GitHub)

| Téma | Dokumentácia |
|------|----------------|
| Všetky bundled shortcody | [SHORTCODE_COOKBOOK.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/SHORTCODE_COOKBOOK.md) |
| Feature vs media gallery | [GALLERY.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/GALLERY.md) |
| Prose + skupiny galérií | [MEDIA_IN_CONTENT.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/user/MEDIA_IN_CONTENT.md) |
| Release poznámky | [RELEASE_2_1_0_BETA_95.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/RELEASE_2_1_0_BETA_95.md) |
| Architektúra vlny | [GALLERY_LIGHTBOX_PLANNED.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/architecture/GALLERY_LIGHTBOX_PLANNED.md) |

SK prehľad shortcodov: [SHORTCODES_A_WIDGETY.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/sk/user/SHORTCODES_A_WIDGETY.md).

---

## Tip pre redakciu

Po deployi otvorte admin **Shortcodes** raz (aktualizácia katalógu `media-gallery`) a znovu uložte stránky s galériou, ak sa zobrazuje starý HTML markup.

---

## Ďalší diel

[Scroll efekty: visual-frame a section-band (beta.96)](ARTICLE_35_motion-visual-frame-section-band.md)
