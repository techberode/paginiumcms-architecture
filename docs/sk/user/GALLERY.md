---
title: Galéria funkcií
description: Ako pridať screenshoty do spoločného katalógu a zobraziť ich na stránke
icon: material/view-gallery
---

# Galéria funkcií

> Jeden katalóg publikovaných obrázkov. Blok na stránke ich **zobrazí**; neukladá druhú sadu fotiek.

Admin → **Galéria funkcií** (`/gallery`) je kuchyňa. Stránka s `[feature-gallery]` je stôl. Súbory ostávajú v Médiách.

## 1. Pridaj fotky (raz)

1. Otvor **Galéria funkcií**.
2. **Pridať screenshot**.
3. Vyplň len to, čo treba:

| Pole | Povinné? | Čo napísať |
|------|----------|------------|
| Názov | áno | Titulok dlaždice (`Kuchyňa`, `Dashboard`) |
| Screenshot | áno | Obrázok z Médií |
| Stav | áno | **Publikované**, inak sa na webe neukáže |
| Popis | nie | Text v modalu po kliknutí |
| Tag modulu | nie | Nálepka na filter (`web`, `mail`) — **nie** slug stránky |
| URL „Dozvedieť sa viac“ | nie | Voliteľné tlačidlo v modalu. Plná `https://…` adresa, alebo prázdne. Cesta `/web` sa odmietne. |

Zopakuj pre každý obrázok. Jeden formulár = jedna fotka. V editore stránky nie je hromadný výber súborov.

## 2. Polož ich na stránku

1. Nastavenia → Layout → **Outline blokov** (`layout.builderMode=outline`). Platí pre **stránky**, nie články.
2. Uprav stránku → paleta → **Galéria**.
3. **Titulok** = nadpis nad mriežkou (`Vybrané práce`).
4. **Značka** = prázdne = **všetky** publikované fotky; `web` = len položky s rovnakým tagom.
5. Ulož.

Telo stránky ostane Markdown:

```markdown
[feature-gallery title="Vybrané práce" tag=""/]
```

V režime Shortcodes / Developer môžeš tag napísať ručne. Článok outline paletu nemá; vlož ten istý shortcode do Markdownu.

## 3. Čo uvidí návštevník

| Miesto | Správanie |
|--------|-----------|
| Živý náhľad v editore | Statická mriežka (sandbox iframe, bez Reactu) |
| Verejná stránka | Ten istý ostrovček, `FeatureGallerySection` (modal, voliteľný slider) |

Nová publikovaná fotka sa na neskontrolovaných blokoch objaví sama. Blok netreba vkladať znova.

## 4. Tagy nie sú samostatné galérie

Úložisko je **jedno** (`data/gallery/`). Tag je nálepka. Dve stránky môžu vyzerať ako dve galérie, lebo ich bloky filtrujú iné nálepky:

```markdown
[feature-gallery title="Web" tag="web"/]
[feature-gallery title="Print" tag="print"/]
```

Fotka bez tagu sa ukáže v blokoch bez značky. `gallery.enabled` a umiestnenie (home / `/features`) riadia len **automatickú** sekciu. Explicitný blok na stránke sa ukáže aj keď je master switch vypnutý.

## 5. Oprávnenia a súbory

- Zápis potrebuje `gallery:manage`.
- Verejné `GET /api/gallery/public` vracia len publikované položky.
- Export/import JSON je metadata (cesty), nie binárne súbory.

Súvisiace: [Landing shortcody (EN)](../../en/user/LANDING_PAGE.md), [Editor obsahu](CONTENT_EDITOR.md), [It.65](../ITERATION_65.md), [It.58f](../ITERATION_58f.md).
