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

## 6. Externé témy

Priečinok pre theme templates a architektonický návrh existujú, ale univerzálny ZIP theme lifecycle nie je v tejto dokumentácii označený ako hotový. Nenahrávaj neznámu PHP šablónu ručne do produkcie a neočakávaj, že sa objaví ako bezpečne inštalovateľná téma.

Keď bude theme package systém implementovaný, bude oddelený od výberu farebnej schémy a bude mať manifest, preview, kompatibilitu, aktiváciu a rollback.

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
