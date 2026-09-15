# Iterácia 92 — Hybrid Engine query index (odvodené SQLite) + poradca Performance Guard

> **Stav:** ⏳ plánované  
> **Kanónická špecifikácia (EN):** [../en/ITERATION_92.md](../en/ITERATION_92.md)

## Cieľ

Voliteľný **odvodený** query engine (SQLite WAL + FTS5) pre listingy, filtre a search pri veľkom katalógu. **Súbory ostávajú SSOT.** Predvolené je JSON (`content.json`). Performance Guard **odporučí** zapnutie podľa záťaže; **nikdy ho nezapne sám** (rovnako ako Redis).

Nie je to prechod na SQL CMS.

## Zhrnutie sliceov

| ID | Čo | Stav |
|----|-----|------|
| **92a** | `QueryIndexInterface` + JSON adaptér | ⏳ |
| **92b** | SQLite driver + rebuild | ⏳ |
| **92c** | Settings prepínač, dual-write, fallback na JSON | ⏳ |
| **92d** | Guard advisor `query_index_sqlite` (len suggest) | ⏳ |
| **92e** | Admin Engine UI, health, CLI | ⏳ |
| **92f** | Testy + mandát v dokumentácii | ⏳ |

Poradie: `92a → 92b → 92c → 92d → 92e → 92f`.

## Aktivácia

Settings → Engine: `queryIndexDriver` = `json` \| `sqlite`. Pred `sqlite` musí prejsť probe (`pdo_sqlite`, zápis, rebuild, rovnaký počet ako JSON). Chýbajúci `.sqlite` nesmie zhodiť CMS.

## Mimochodom

Komentáre, useri, settings **nepatria** do SQLite v tejto iterácii. Verejný GET podľa slugu ostáva čítanie súboru.
