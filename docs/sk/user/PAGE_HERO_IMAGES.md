# Hero stránky a úvod blogu — veľkosť a orez obrázka

Hero na stránkach (vrátane **sivého boxu** na `/blog` pre slug **`blog`**) vždy používa **`object-fit: cover`** v rámci s pevným pomerom strán. **Ťahaj a pusť** v editore mení len **`object-position`**, nie tvar rámu. Pri generovaní (AI) alebo exporte z grafiky dodržte pomer a „bezpečnú zónu“, aby orezy boli čo najmenšie.

## Pomer strán rámca (verejný web)

Kód: `PageHeroMedia.tsx` (web), `PageHeroFocusPreview.tsx` (náhľad v admin).

| Zobrazenie | CSS pomer | Šírka : výška |
|------------|-----------|----------------|
| Úzke (mobil) | `2 / 1` | **2:1** |
| `sm` a viac (~640px+) | `21 / 9` | **21:9** (~2,33:1) |

Výškové limity (stále `cover`):

| Umiestnenie | Typická max. výška |
|-------------|---------------------|
| Úvod blogu (`/blog`) | ~`22rem` alebo ~`42vw` |
| Hlavička stránky | ~`52vh` alebo ~`28rem` |

Na **mobile** je rám **2:1**, na **väčších** obrazovkách **21:9** — jeden súbor nemôže byť bez orezu všade. Pre AI generovanie cielte na **21:9** a nechajte rezervu hore/dole pre telefóny.

## Odporúčané rozlíšenie pre AI / export

### Hlavné odporúčanie (jeden master súbor)

| Položka | Hodnota |
|---------|---------|
| **Pomer strán** | **21:9** |
| **Rozlíšenie** | **2560 × 1097** (alebo **2520 × 1080**) |
| **Min. šírka** | **1920 px** |
| **Farba** | sRGB |
| **Formát** | JPEG alebo WebP (upload cez Médiá) |

**21:9** sedí na tablete/deskupe; mobil oreže pás hore/dole — dôležité prvky držte v **strede** (nižšie).

### Bezpečná zóna (kompozícia)

Kritický obsah (tváre, logo, titulok) udržujte približne v:

- **55 %** šírky (v strede),  
- **65 %** výšky (v strede).

V posledných **20 %** od okrajov nič dôležité. Po uploade doladte **Hero obrázok v hlavičke → ťahaj v náhľade**.

### Ak máte len 2:1

| Položka | Hodnota |
|---------|---------|
| Pomer | **2:1** |
| Rozlíšenie | **1920 × 960** (alebo **2400 × 1200**) |

Na mobile OK; na **`sm+`** sa orezávajú **boky**. Nové AI snímky radšej **21:9**.

### SEO / OG (ten istý súbor)

V režime **Auto** hero často = **SEO / OG obrázok**. Klasický náhľad pre sociálne siete je cca **1,91:1** (**min. 1200 × 630**). **21:9** je širší — platformy môžu orezať inak. Pre presný OG náhľad zvážte samostatný **1200 × 630** alebo akceptujte orez.

## Kontrolný zoznam

1. Export **21:9**, šírka **≥ 1920 px** (ideálne **2560 px**).  
2. **Médiá** → priraďte SEO a/alebo hero (single/karusel).  
3. Stránka (**`blog`** pre zoznam článkov) → **Hero obrázok v hlavičke** → ťahaj, kým náhľad sedí s `/blog`.  
4. Z tela stránky vymažte duplicitné `![…](…)` (každý jazyk).  
5. Voliteľne **Médiá → optimalizácia** alebo preset **1920** pri veľkých súboroch.

## Príklad promptu pre AI (SK)

```text
Ultra široká hero fotografia pre web, pomer strán 21:9, rozlíšenie 2560x1097,
jeden jasný objekt v strede, dôležité detaily nie v posledných 20% okrajov,
prirodzené svetlo, bez textu a vodotlače, fotorealistické
```

## Umiestnenie hero (editor)

V **Hero obrázok v hlavičke → Kde zobraziť hero**:

| Hodnota | Správanie na webe |
|---------|-------------------|
| **Auto** | Blog → **sivý box**; layout **landing** → pozadie **showcase / landing** blokov; inak **plná hlavička** |
| **Plná hlavička** | Široký pás pod navigáciou (titulok + obrázok/karusel), telo v karte |
| **Sivý box** | Titulok nad kartou, hero **vnútri** karty (ako `/blog`) |
| **Landing inline** | Bez samostatného pásu; obrázok na **showcase-hero** / **landing-hero** v tele stránky |

Pre **home + landing** so shortcodmi zvoľ **Landing inline**. Pre klasický marketingový pás **Plná hlavička**. Pre blogový štýl **Sivý box**.

## Súvisiace

- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) — slug `blog`, editor stránok  
- [CONTENT_EDITOR.md](CONTENT_EDITOR.md) — SEO a cesty k médiám  
- Anglická verzia: [PAGE_HERO_IMAGES.md](../../en/user/PAGE_HERO_IMAGES.md)  
