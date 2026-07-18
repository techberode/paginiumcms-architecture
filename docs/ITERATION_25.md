# Iteration 25 – Setup wizard (profil webu) ⏳

**Status:** Planned (not in scope — settings `media.stockImageTopic` stačí)  
**Version:** TBD (post-2.0.13)  
**Priority:** 🟡 stredná

## Summary

Prvotný sprievodca inštaláciou: admin účet, názov webu, **téma zamerania** (tagy), voliteľný seed demo obsahu/médií.

**Rozhodnutie (2026-07):** Neduplikovať inštalačný krok — téma stock obrázkov sa nastavuje v **Admin → Settings → Media / DAM** (`stockImageTopic`). Install wizard zostáva v backlogu pre komplexnejší onboarding.

## Scope (when implemented)

| # | Deliverable |
|---|-------------|
| 1 | Route `/setup` ak `settings.json` nemá `installed: true` |
| 2 | Kroky: admin, profil webu, voliteľný import N stock obrázkov |
| 3 | Zápis do `general.*` + `media.stockImageTopic` |
| 4 | `POST /api/setup/complete` (SUPER_ADMIN only after first register) |

## Depends on

- Iteration 24 — stock katalóg + `StockImageImporter` ✅

## See also

- [ITERATION_24.md](ITERATION_24.md) — stock knižnica (téma bez install wizardu)
