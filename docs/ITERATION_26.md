# Iteration 26 – Media preview lightbox

**Status:** Complete  
**Version:** 2.0.13  
**Release track:** post-2.0.12 (DAM UX — náhľady obrázkov)

## Summary

Klikateľný náhľad obrázkov v Media Library s dvoma režimami zobrazenia: **Fit** (celý obrázok v okne, zachovaný pomer strán) a **1:1** (natívne rozlíšenie v pixeloch, scroll ak je väčší ako obrazovka). Navigácia medzi súbormi v aktuálnom filtri, klávesnica Esc / šípky.

## Logical sequence

```
It.24 (DAM grid) → It.26 (lightbox preview)
```

## Scope

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | `MediaPreviewLightbox.tsx` — modal, Fit / 1:1, metadata | ✅ |
| 2 | Integrácia v `MediaManager` — klik na thumbnail, tlačidlá Expand / 1:1 | ✅ |
| 3 | Zobrazenie `naturalWidth × naturalHeight` po načítaní | ✅ |
| 4 | Prev/Next v rámci filtrovaného zoznamu | ✅ |
| 5 | PDF/ne-obrázky → otvorenie v novom okne | ✅ |
| 6 | Vitest (`MediaPreviewLightbox.test.tsx`) | ✅ |

---

## Part 1 – Komponent `MediaPreviewLightbox` ✅

**Súbor:** `frontend/src/components/backend/MediaPreviewLightbox.tsx`

| Režim | Správanie |
|-------|-----------|
| **Fit** | `object-contain`, max výška `calc(100vh - 8rem)` — celý obrázok viditeľný |
| **1:1 (native)** | CSS `width/height` = `naturalWidth/Height` px — skutočné rozlíšenie, kontajner `overflow-auto` |

**Ovládanie:**

- Klik mimo obsah / tlačidlo X → zatvorenie
- `Esc` → zatvorenie
- `←` / `→` → predchádzajúci / ďalší súbor (ak existuje)
- Prepínač Fit / 1:1 v hlavičke

**Metadata v hlavičke:** title, fileName, rozmery px, veľkosť, MIME.

---

## Part 2 – MediaManager integrácia ✅

**Súbor:** `frontend/src/components/backend/MediaManager.tsx`

- Klik na thumbnail (hover ikona Expand) → lightbox Fit
- Tlačidlo **Expand** na karte → Fit
- Tlačidlo **1:1** na karte → native režim
- `MediaPreviewLightbox` renderovaný na konci stromu (portál nie je potrebný — `fixed inset-0 z-50`)

---

## Part 3 – Čo nie je v scope (budúce iterácie)

| Feature | Iterácia |
|---------|----------|
| Generovanie thumbnailov (backend resize) | DAM rozšírenie / It.8+ |
| Zoom/pan (pinch) | Voliteľné UX v It.26+ |
| Lightbox v `MediaPickerModal` (editor) | It.30 Live Preview / editor |

---

## Test plan

1. Admin → Media → klik na obrázok → Fit režim, celý obrázok viditeľný
2. Prepni na **1:1** → rozmery v hlavičke zodpovedajú súboru
3. Veľký obrázok (napr. 4000×3000) → v 1:1 režime scroll
4. Esc zatvorí náhľad
5. Šípky prepínajú medzi položkami v gride

---

## Deploy

Len frontend (`paginium-deploy`). Backend bez zmien.
