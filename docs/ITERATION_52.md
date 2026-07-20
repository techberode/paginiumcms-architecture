# Iteration 52 – Dashboard v2, kontakt & firemné údaje

**Status:** 🚧 rozpracované — **It.52a ✅ (2.0.34)** · It.52b ⏳ · It.52c ⏳  
**Verzia:** 2.0.34 (52a) · 2.0.35+ (52b/c plánované)  
**Nadväzuje na:** [It.51](ITERATION_51.md)

## Cieľ

Admin dashboard ako na mockupe: **prehľad aktivít**, **Flat-File strom**, **kontaktný formulár** s vlastnými predmetmi.

## Rozsah

| Položka | Iterácia | Stav |
|---------|----------|------|
| Audit log panel (formátované udalosti) | It.52a | ✅ |
| Flat-File štruktúra (pages / blog / config / media) | It.52a | ✅ |
| KPI: neprečítané správy / médiá / veľkosť disku | It.52a | ✅ |
| **Kontakt — predvolené predmety** (`contact.subjects`) | It.52b | 🟡 |
| Voľba „Vlastný predmet“ (`contact.allowCustomSubject`) | It.52b | 🟡 |
| **Firemné údaje — editovateľná šablóna** (IČO, adresa, …) | It.52c | ⏳ |
| **Google Map embed** na kontaktnej stránke | It.52c + **It.48** | ⏳ |

## Nastavenia (návrh)

| Kľúč | Skupina | Popis |
|------|---------|--------|
| `contact.subjects` | Kontakt | Text — jeden predmet na riadok |
| `contact.allowCustomSubject` | Kontakt | bool — povoliť voľný text |
| `company.name`, `company.address`, … | Firemné údaje | It.52c — verejný výrez cez `publicSettings` |

## Google Maps

Zapojenie až po **It.48** (PHP šablóny / contact template) alebo samostatný slice **It.52c** — embed URL z nastavení, nie hardcoded iframe.

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
