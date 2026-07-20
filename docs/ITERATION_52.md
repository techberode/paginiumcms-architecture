# Iteration 52 – Dashboard v2, kontakt & firemné údaje

**Status:** ⏳ plánované  
**Verzia:** 2.0.34+ (plánované)  
**Nadväzuje na:** [It.51](ITERATION_51.md)

## Cieľ

Admin dashboard ako na mockupe: **prehľad aktivít**, **Flat-File strom**, **kontaktný formulár** s vlastnými predmetmi.

## Rozsah

| Položka | Iterácia | Stav |
|---------|----------|------|
| Audit log panel (formátované udalosti) | It.52a | 🟡 |
| Flat-File štruktúra (pages / blog / config / media) | It.52a | 🟡 |
| KPI: neprečítané správy / médiá / veľkosť disku | It.52a | ⏳ |
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

## Súvisiace

- [ITERATION_35.md](ITERATION_BACKLOG.md) — Flat-File Inspector (read-only explorer)
- [ITERATION_48.md](ITERATION_48.md) — šablóny stránok + static/dynamic web
