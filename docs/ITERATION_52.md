# Iteration 52 – Dashboard v2, kontakt & firemné údaje

**Status:** ✅ Complete (It.52a–c)  
**Verzia:** **2.0.36**  
**Nadväzuje na:** [It.51](ITERATION_51.md)

## Cieľ

Admin dashboard ako na mockupe: **prehľad aktivít**, **Flat-File strom**, **kontaktný formulár** s vlastnými predmetmi.

## Rozsah

| Položka | Iterácia | Stav |
|---------|----------|------|
| Audit log panel (formátované udalosti) | It.52a | ✅ |
| Flat-File štruktúra (pages / blog / config / media) | It.52a | ✅ |
| KPI: neprečítané správy / médiá / veľkosť disku | It.52a | ✅ |
| **Kontakt — predvolené predmety** (`contact.subjects`) | It.52b | ✅ (2.0.35) |
| Voľba „Vlastný predmet“ (`contact.allowCustomSubject`) | It.52b | ✅ (2.0.35) |
| **Firemné údaje — editovateľná šablóna** (IČO, adresa, …) | It.52c | ✅ (2.0.36) |
| **Google Map embed** na kontaktnej stránke | It.52c | ✅ (2.0.36) |

## Nastavenia (návrh)

| Kľúč | Skupina | Popis |
|------|---------|--------|
| `contact.subjects` | Kontakt | Text — jeden predmet na riadok |
| `contact.allowCustomSubject` | Kontakt | bool — povoliť voľný text |
| `company.mapEmbedUrl` | Firemné údaje | Google Maps embed `src` (len `https://www.google.com/maps/embed…`) |

## Verejný výrez (`GET /api/settings/public`)

Skupina **`company`** — rovnaké kľúče ako v admin schéme; frontend zobrazí panel na `/contact`, ak `showOnContactPage` a aspoň jedno pole je vyplnené.

## API (It.52a)

`GET /api/admin/dashboard/overview` rozšírené o:

| Pole | Popis |
|------|--------|
| `counts` | Rovnaké počty ako `/api/admin/counts` (+ `messages_unread` pre admin) |
| `storage.free_space` | Ľudsky čitateľná veľkosť z health checku `storage` |
| `storage.free_space_bytes` | Raw bajty voľného miesta |

## Súvisiace

- [ITERATION_35.md](ITERATION_BACKLOG.md) — Flat-File Inspector (read-only explorer)
- [ITERATION_48.md](ITERATION_48.md) — šablóny stránok + static/dynamic web
