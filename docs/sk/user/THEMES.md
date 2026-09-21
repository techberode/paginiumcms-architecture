---
title: Vzhľad a farebné schémy
description: Nastavenie verejného light/dark režimu, schémy a preview
icon: material/palette
---

# Vzhľad webu — používateľská príručka

> **Cesta:** **Nastavenia → Vzhľad**  
> Aktuálna Public Beta spravuje farebné schémy a light/dark režim. Nie je to ešte inštalátor externých theme balíkov.

---

## 1. Čo môžeš nastaviť

| Nastavenie | Význam |
|------------|--------|
| Farebná schéma | sada sémantických farieb verejného webu |
| Režim | `light`, `dark` alebo `system` |
| Povoliť prepínač návštevníka | zobrazí ovládanie light/dark vo verejnom UI |
| Preview profil | ukážkový wireframe v administrácii |

Administrátorský light/dark vzhľad je osobné nastavenie pracovného rozhrania a je oddelený od verejného webu.

---

## 2. Výber schémy

Implementované presety:

- `indigo-classic`,
- `ocean-slate`,
- `forest-sage`,
- `sunset-rose`,
- `mono-zinc`.

1. Otvor panel **Vzhľad**.
2. Klikni na kartu schémy.
3. Skontroluj light aj dark preview.
4. Ulož nastavenia.
5. Otvor verejnú stránku v anonymnom okne a over reálny výsledok.

Preview je pomocný náhľad, nie pixelovo presná snímka každej stránky a pluginu.

---

## 3. Light, dark a system

| Režim | Správanie |
|-------|-----------|
| `light` | web predvolene používa svetlý vzhľad |
| `dark` | web predvolene používa tmavý vzhľad |
| `system` | rešpektuje preferenciu operačného systému/prehliadača |

Ak je povolený visitor toggle, návštevník môže mať lokálnu voľbu v prehliadači. Tá neprepisuje globálne nastavenie pre ostatných používateľov.

---

## 4. Branding a obsah

Farebná schéma nemení:

- logo a favicon,
- názov stránky,
- login background,
- Open Graph obrázok,
- obsahové obrázky,
- text a layout AST stránky.

Tieto vrstvy sa nastavujú samostatne. Logo má mať čitateľnú variantu pre zvolený svetlý aj tmavý povrch; transparentné logo bez kontrastu môže v jednej schéme „zmiznúť“.

---

## 5. Kontrola po zmene

Over aspoň:

- navbar a footer,
- tlačidlá a focus stav,
- formuláre a validačné chyby,
- článok s odkazmi a code blokom,
- login/register/maintenance obrazovku,
- mobilnú šírku,
- kontrast loga,
- light aj dark režim.

Pri cache alebo statickom publish profile môže byť potrebná invalidácia/rebuild/publish podľa nasadenia.

---

## 6. Externé témy a Theme Studio

Inštalované balíky: **Build → Témy** (`/themes`) — ZIP import, aktivácia, rollback.

**Theme Studio:** **Upraviť** / **Nová** — Monaco (HTML/CSS), politika ako pri ZIP (žiadny `<script>` v layoute). **Otvoriť playground** (95b) pošle aktuálny CSS/HTML/JS buffer do Sandpacku; export sa vráti do Monaco až po validácii a **sám na disk nezapisuje**. **Normalizovať** z vloženého HTML spraví bezpečný balík, **nie** pixel-perfect kópiu TemplateMo. **Náhľad** je sandbox bez skriptov.

### Ako nahraviť voľnú HTML šablónu (bez programovania)

Paginium má vlastné menu, stránky a články. Demo na TemplateMo je často **jeden obraz**. Live URL (`/live/templatemo_620_compression`) **nie je** téma — sú tam reklamy. Stiahni **ZIP** z produktovej stránky.

**Compression (620)** je vhodná: panely na hover a overlaye `:target` sú **čisté CSS** (žiadny jQuery).

1. Rozbaľ ZIP. Otvor `index.html` a CSS. JS súbory pre túto šablónu nepotrebuješ.
2. **Build → Témy → Nová.** CSS do záložky CSS. Markup piatich panelov do HTML (slot **main**). Ak Normalizovať rozbije panely na header/main/footer, vráť zmenu a vlož len panely do main.
3. **Náhľad** → hover musí žiť z CSS. **Uložiť** → **Aktivovať**.
4. Core menu môže prekryť full-screen layout — v CSS témy ho skry/uprav, ak chceš pôvodný vzhľad.
5. Texty webu daj do Paginium stránok (Úvod, Práca, O nás, Kontakt). Overlaye v ZIP sú makety, nie CMS.

Šablóny s Bootstrap/jQuery sliderom po Normalizácii **prídu o** tie efekty (skripty sa vyhodia). Nerieš to vkladaním `custom.js` do článku.

---

## 7. Riešenie problémov

| Problém | Skontroluj |
|---------|------------|
| schéma sa neuložila | validation error, oprávnenie, settings log |
| verejný web má staré farby | cache, service worker, hard refresh, publish/rebuild |
| iba admin má inú tému | je to očakávané; admin a public theme sú oddelené |
| logo je nečitateľné | transparentnosť a kontrast v light/dark režime |
| časť pluginu má vlastné farby | plugin nepoužíva semantic tokens; nahlás compatibility issue |
| systémový režim sa mení | prehliadač reaguje na OS `prefers-color-scheme` |

---

## Súvisiace dokumenty

- [Architektúra tém](../architecture/THEMES.md)
- [Logo a favicon](BRANDING.md)
- [Nastavenia](../architecture/SETTINGS.md)
- [Admin príručka](ADMIN_GUIDE.md)
