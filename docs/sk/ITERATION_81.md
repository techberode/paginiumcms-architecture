# Iterácia 81 — Redakčný workflow a content ops

> **Stav:** 🚧 prebieha (`81a`–`81e` hotové; `81f` plánované)  
> **Priorita:** 🟡  
> **Kanónická špecifikácia (EN):** [../en/ITERATION_81.md](../en/ITERATION_81.md)

## Cieľ

Balík editor-facing funkcií: duplikácia obsahu, hromadné tagy, uložené pohľady, editoriálny kalendár, flag zastarávajúceho obsahu a knižnica snippetov — bez SQL, na flat-file modeli.

## Overené medzery v kóde (august 2026)

| # | Funkcia | Stav v kóde |
|---|---------|-------------|
| 1 | Duplikácia contentu | žiadny `duplicate`/`clone` endpoint |
| 2 | Editoriálny kalendár | `SchedulerView` = cron joby, nie publikačný kalendár |
| 3 | Bulk tagy | len bulk delete + status |
| 4 | Uložené filtre | nič (`SavedFilter` / `SavedView`) |
| 5 | Stale content | nič v `PerformanceAuditor` |
| 6 | Snippet knižnica | Blueprinty ≠ opakovane použiteľné inštancie |

## Odporúčané poradie (dopad × náročnosť)

| Sub | Funkcia | Cieľový release |
|-----|---------|-----------------|
| **81a** | Duplikácia ako draft | `beta.47` ✅ |
| **81b** | Bulk priradenie tagov | `beta.47` ✅ |
| **81c** | Uložené filtre/pohľady | `beta.47` ✅ |
| **81d** | Editoriálny kalendár | `beta.47`–`48` ✅ |
| **81e** | Flag zastarávajúceho obsahu | `beta.48` ✅ |
| **81f** | Knižnica snippetov (shortcode) | `beta.48`+ |

Detail API, bezpečnosť a Definition of Done → anglická špecifikácia.
