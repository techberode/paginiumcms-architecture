# Editor obsahu — podstránky a články

> **Cesty v admin paneli:** `/pages/:slug`, `/articles/:slug`  
> **Posledná aktualizácia:** júl 2026

---

## Prehľad

Podstránky a články zdieľajú rovnaký editor (`MarkdownEditor` + `ContentEditorShell`):

| Režim | Popis |
|-------|--------|
| **Markdown** | Priamy zápis Markdown + náhľad |
| **WYSIWYG** | Vizuálny editor (TipTap) |
| **SEO panel** | Meta titulok, OG obrázok, kanonická URL, tagy (články) |

Backend je jediný zdroj pravdy — po **Uložiť** sa obsah zapíše do flat-file (`content/pages/` alebo `content/blog/`).

---

## SEO nastavenia a náhľadový obrázok

V editore rozbaľ sekciu **SEO nastavenia**.

| Pole | Účel |
|------|------|
| SEO titulok | Alternatíva k názvu vo vyhľadávačoch (~60 znakov) |
| Meta popis | V hlavnom formulári (pole **Popis**) — zobrazuje sa aj v SEO health |
| **OG / náhľadový obrázok** | Obrázok pre sociálne siete a náhľad článku |
| Kanonická URL | Voliteľné; prázdne = automatická URL |
| Tagy | Len články — oddelené čiarkou |
| noindex | Skryť stránku pred vyhľadávačmi |

### Výber obrázku z médií (file manager)

1. V poli **OG / náhľadový obrázok** klikni **Vybrať z médií**
2. V modálnom okne vyber obrázok z knižnice (`GET /api/media?type=image`)
3. Cesta sa doplní ako `/storage/app/content/media/…`
4. Pod poľom sa zobrazí **miniatúra** náhľadu
5. Tlačidlo **×** vymaže výber (ešte treba **Uložiť**)

Obrázky najprv nahraj v **Médiá** (`/media`), ak v knižnici ešte nie sú.

### Kam sa obrázok uloží (backend)

Pri uložení článku s `ogImage`:

| Uložisko | Kľúč |
|----------|------|
| Front matter | `seoImage` |
| Článok (Article) | `featuredImage` (rovnaká URL) |
| API odpoveď | `ogImage`, `featuredImage` |

Pre **články** teda OG obrázok = **náhľad v blogovom zozname** a v detaili článku na verejnom webe (`/blog`).

Pre **podstránky** slúži hlavne SEO / Open Graph (nie je featured v blog liste).

---

## Verejný web — blogové karty

Zoznam článkov (`BlogRenderer`) zobrazí náhľad, ak existuje aspoň jedna z ciest:

- `featuredImage`
- `ogImage`
- `frontMatter.seoImage`

Utility: `frontend/src/utils/contentPreviewImage.ts` — normalizuje `/storage/…` cesty pre `<img src>`.

**Dôležité:** nginx (alebo reverse proxy) musí proxyovať **`/storage`** na PHP backend. Inak obrázok v admin náhľade môže fungovať, ale verejná stránka nie. Viď [deploy/NGINX_API.md](../deploy/NGINX_API.md).

---

## Obrázky v tele článku (WYSIWYG / Markdown)

| Akcia | Kde |
|-------|-----|
| Vložiť obrázok do textu | Toolbar editora → **Media** → `MediaPickerModal` |
| Správa súborov | Admin → **Médiá** → `MediaManager` |

Do tela sa vkladá absolútna URL (`origin` + `/storage/…`). Do SEO poľa sa ukladá relatívna cesta `/storage/…` — obe sú správne.

---

## Uloženie a cache

1. Po zmene SEO (vrátane obrázka) vždy klikni **Uložiť**
2. Po deployi môže byť potrebné obnoviť cache obsahu:

```bash
php backend/bin/console content:cache-purge --reindex
```

3. Na verejnom blogu hard refresh (Ctrl+F5)

Ak obrázok v SEO paneli vidíš, ale na `/blog` nie — skontroluj, či bol článok uložený **po** výbere obrázka a či proxy servuje `/storage`.

---

## Súvisiace dokumenty

- [architecture/CONTENT_API.md](../architecture/CONTENT_API.md) — API polia pri ukladaní
- [ITERATION_27.md](../ITERATION_27.md) — SEO panel a health badge
- [ITERATION_8.md](../ITERATION_8.md) — Media Manager a picker
- [ITERATION_30.md](../ITERATION_30.md) — ContentEditorShell layout
- [deploy/NGINX_API.md](../deploy/NGINX_API.md) — `/api` a `/storage` proxy
