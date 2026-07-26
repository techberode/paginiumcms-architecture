# Iteration 59 – Odložená publikácia (plánovač v editore)

**Status:** ✅ Shipped (**2.0.53**)  
**Priorita:** 🟡 Stredná  
**Nadväzuje na:** [It.29 Cron planner](ITERATION_29.md) ✅ · [It.54 Editor profiles](ITERATION_54.md) ✅ · content API

## Cieľ

Editor stránok a článkov umožní **naplánovať publikáciu** (scheduled publish) — obsah zostane v stave `draft` / `scheduled` a v definovanom čase sa automaticky prepne na `published` cez job queue.

## Rozsah

| Oblasť | Popis |
|--------|--------|
| **Editor** | Pole „Publikovať o“ + date-time picker (rozbalovací kalendár) v `ContentEditorShell` / publish paneli |
| **Front matter** | `scheduledAt` (ISO 8601), voliteľne `status: scheduled` |
| **Admin zoznamy** | Filter / stĺpec „Naplánované“; kalendárny výber pri filtroch dátumu (PagesManager, blog) |
| **Backend job** | Handler `content.scheduled_publish` v It.29 registry — skenuje index / front matter, publikuje due items |
| **Notifikácia** | Voliteľne audit + email adminovi po auto-publish |

## Technicky

- Reuse `Scheduler` / `data/jobs/registry.json` (It.29), nie nový cron mimo existujúceho modelu.
- Idempotentný publish (ak už `published`, skip).
- Respektovať OTP publish approval ([It.41](ITERATION_41.md)) — scheduled job len ak schválené / OTP vypnuté.
- Timezone: `AppTimezone` + DST z nastavení (2.0.51).

## Mimo rozsahu (v1)

- Odložené **unpublish** / archivácia.
- Recurring publish (RSS-only cadence).

## Acceptance criteria

- [x] Editor: výber dátumu/času + uloženie draftu so `scheduledAt`
- [x] CLI/cron: due obsah sa publikuje do 1 minúty od `scheduledAt`
- [x] Admin list: filter scheduled + zobrazenie dátumu
- [x] PHPUnit: job handler + API validácia
- [x] Vitest: date picker UX v editore

## Súvisiace

- [ITERATION_29.md](ITERATION_29.md) — job registry
- [architecture/CONTENT_API.md](architecture/CONTENT_API.md)
- [ROADMAP.md](ROADMAP.md)

## Smoke test

1. **Editor** — `/pages/new` → pole „Publikovať o“ → dátum o 2 min → uložiť → stav **Naplánované**.
2. **Cron / CLI** — pred due: `php backend/bin/console jobs:run content-scheduled-publish` → `outcome: skipped`, `reason: nothing_due`. Po due (do ~1 min): obsah **published**, `scheduledAt` odstránené.
3. **Admin zoznam** — filter stav „Naplánované“, stĺpec dátumu (desktop + mobile karta).
4. **Verejné API** — `GET /api/pages/{slug}` pre scheduled → **404** (skryté do publish).
