# Query index — voliteľná SQLite projekcia (It.92)

> **Stav:** ✅ **It.92 hotové** v strome (runtime watch, SK/EN admin) · september 2026  
> **EN:** [../../en/architecture/QUERY_INDEX.md](../../en/architecture/QUERY_INDEX.md)  
> **Špecifikácia:** [../ITERATION_92.md](../ITERATION_92.md)

## Prečo je to zlomová iterácia

PaginiumCMS **nemení** pravidlo: **súbory = SSOT** (Markdown, JSON, zálohy, Git). It.92 pridáva **voliteľnú odvodenú vrstvu** — rovnakú triedu ako `content.json` alebo cache, nie „prechod na SQL CMS“.

| Bez SQLite (dnes) | S SQLite (po zapnutí) |
|-------------------|------------------------|
| Katalóg = načítanie a filter **`content.json` v PHP** | Rovnaké zápisy SSOT; listing/search cez **indexované** SQLite + FTS5 |
| Veľký katalóg = viac CPU/RAM | Rýchlejšie admin listy a verejný blog index |
| Guard len navrhuje cache purge | Guard môže **navrhnúť** query index podľa p95 |

**Metafora:** kuchyňa stále píše objednávky na papier. SQLite je **duplicitný lístok nad passom** — keď spadne, číta sa znova z `content.json` (rebuild).

## K čomu zapnutie vedie

1. **Veľké weby** — tisíce článkov/stránok bez full JSON decode pri každom liste.  
2. **Vyhľadávanie** — FTS5 nad poliami indexu (title, slug, excerpt, tagy), bez user SQL; v SQL musí byť `entries_fts MATCH :match` (nie alias tabuľky).  
3. **Aktivácia** — najprv **Rebuild**, potom **Activate sqlite**; aktivácia nespúšťa rebuild, len existujúci súbor + paritu s JSON. Bez súboru → **422**, driver zostane `json`.  
4. **Prevádzka** — chýbajúci `.sqlite` → **fallback JSON**, health varovanie, CLI rebuild. Záloha môže `.sqlite` vynechať.  
5. **Bezpečnosť zápisu** — článok sa uloží aj keď sync SQLite zlyhá (incident, nie rollback).  
6. **Positioning** — No-SQL SSOT pre audit/GitOps; SQLite = **performance switch**, nie migrácia dát.

## Čo SQLite **nie je**

- Telo stránky, useri, settings, komentáre v SQL.  
- GET podľa slugu (ostáva súbor).  
- Auto-zapnutie z Performance Guard `automatic`.

## Vrstvy

SSOT → `content.json` (vždy) → voliteľne `content.sqlite` → `QueryIndexInterface` → cache (It.69).

## Slicey

`92a` JSON adaptér → `92b` SQLite → `92c` prepínač → `92d` Guard → `92e` UI/CLI → `92f` testy + docs.
