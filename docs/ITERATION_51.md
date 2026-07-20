# Iteration 51 – Live preview, tagy & blog štítky

**Status:** ✅ (náhľad + reading time) · **Verzia:** 2.0.32  
**Nadväzuje na:** [It.44](ITERATION_44.md) ✅

## Cieľ

Editor vidí **celú stránku** (header + telo + footer) ešte pred publikovaním. Verejný blog dostane **viditeľné tagy** a **štítky dátumov** (vytvorenie / úprava).

## Rozsah

| Položka | Popis |
|---------|--------|
| **SitePreviewModal** | Fullscreen popup s Navbar + obsah + Footer; mierka 100 % / 75 % / 50 % / celá obrazovka |
| **Editor tlačidlo** | „Náhľad stránky“ v `ContentEditorShell` (stránka + článok, aj koncept) |
| **ArticleTagsEditor** | Chip UI pre tagy (mimo skrytého SEO panelu) |
| **Blog štítky dátumov** | `formatContentDateLabels()` — vytvorené / upravené na kartách a detaile |
| **Admin filter tag** | ⏳ voliteľne v `PagesManager` (articles) — It.44 backend |

## Technicky

- Náhľad renderuje draft cez `PageRenderer` / inline article layout — **bez** navigácie mimo modalu (`previewMode` na Navbar).
- Tagy ostávajú v API poli `tags[]`; editor synchronizuje s `SeoFormValues.tags`.
- Live preview **nenahrádza** It.31 draft token URL — dopĺňa editor UX.

## Testy

```bash
cd frontend && npm test -- --run src/components/backend/SitePreviewModal.test.tsx
cd frontend && npm test -- --run src/utils/contentDates.test.ts
```

## Súvisiace

- [ITERATION_31.md](ITERATION_BACKLOG.md) — pôvodný live preview backlog
- [ITERATION_52.md](ITERATION_52.md) — dashboard + kontakt
