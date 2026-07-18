# Iteration 30 – Content admin polish (editory, cache, zoznamy)

**Release target:** 2.0.20  
**Status:** ✅ Implemented

## Priorita (prečo táto iterácia)

| # | Problém | Dopad |
|---|---------|--------|
| 1 | Content cache ukladala PHP objekty → prázdna tabuľka po refreshi | 🔴 kritické |
| 2 | Editor bez kontextu (URL, menu, SEO na jednej obrazovke) | 🟠 vysoké |
| 3 | Zoznamy bez responzivity / `itemsPerPage` z nastavení | 🟡 stredné |
| 4 | Anglické labely v admin zoznamoch | 🟢 UX |

## Backend

- **Content cache:** serializované API polia namiesto `Content` objektov; `null` sa neukladá do cache
- **ChainedDriver:** `increment()` číta z file vrstvy (bez regresie generácie)
- **ContentIndexService:** rebuild indexu ak je prázdny, ale súbory na disku existujú
- **CLI:** `php backend/bin/console content:cache-purge [--reindex]`
- **Login alert spam:** email len pri lockoute (nie každý failed login); skip `@example.com` / `test_*` / `APP_ENV=testing`; cooldown 900 s

## Frontend

- **Markdown + WYSIWYG** editory s prepínaním režimov a `contentFormat` vo front matter
- **ContentEditorShell** – layout podľa prototypu (metadata, menu kontext, SEO, footer)
- **SeoMetadataPanel** – od **2.0.23**: výber OG/náhľadu cez `MediaPickerModal`; blog karty cez `contentPreviewImage`
- **AdminListToolbar** – zdieľaný toolbar pre zoznamy (Pages, Media)
- **PagesManager** – SK labely, mobilné karty, `itemsPerPage` z nastavení, náhľad článkov `/blog/{slug}`
- **Responzivita** – `hide-mobile` / `hide-tablet`, mobilné karty pod 768 px

## Po deployi (jednorazovo)

```bash
php backend/bin/console content:cache-purge --reindex
# alebo: rm -f backend/storage/cache/*.cache
```

## Test plan

- [ ] Vytvoriť podstránku → refresh zoznamu → položka ostáva
- [ ] Upraviť obsah → verzie + telo sa zobrazí
- [ ] Prepínač Markdown ↔ WYSIWYG → uložiť → reload
- [ ] Mobile: zoznam podstránok ako karty
- [ ] `content:cache-purge --reindex` na serveri
