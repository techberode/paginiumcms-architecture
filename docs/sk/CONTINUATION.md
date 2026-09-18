# PaginiumCMS — kontext pre pokračovanie vývoja

> **Účel:** stručný handoff pre ďalšiu reláciu  
> **Checkpoint:** 18. september 2026 · **`v2.1.0-beta.82`**  
> **Aktívna fáza:** **plný vývoj naplánovaných iterácií** — stabilizačný freeze zrušený

Anglický master: [en/CONTINUATION.md](../en/CONTINUATION.md)

---

## Rozhodnutie (9. 9. 2026)

Stabilizačná fáza je **ukončená**. Pokračujeme v existujúcich špeckách; `./scripts/iteration-gate.sh` ostáva povinný.

## Stav (september 2026)

| Oblasť | Stav |
|--------|------|
| Najnovší tag | ✅ `v2.1.0-beta.82` — It.89b–e plugin broker · CLI |
| Rozpracované | **58f-h** · **It.94** · **It.95** (It.92 hotové v strome, tag `beta.83`) |

## Fronta

1. **It.70** · **It.76/77** · **It.75** · **It.48** (58g compile s It.48)

Hotové pred touto frontou: **It.58f** vizuálne bloky (**58f-a–g**) — [ITERATION_58f.md](ITERATION_58f.md). **It.93** ✅ `beta.77`–`78` (93l Kanban + 93m domain IMAP).

**It.92** ✅ SQLite odvodený query index, runtime watch, Engine UI — CHANGELOG `2.1.0-beta.83`. Deep-link: `/settings?group=engine` (alias `/settings/engine`). **It.89** hotové (`beta.80`–`82`).

## Dokumentácia

| Dokument | Obsah |
|----------|--------|
| [ITERATION_93.md](ITERATION_93.md) | Admin chrome + denné aplikácie |
| [architecture/ADMIN_DEEP_LINKS.md](architecture/ADMIN_DEEP_LINKS.md) | Admin SPA cesty (`/{module}`; aliasy `/platform/*`) |
| [RELEASE_2_1_0_BETA_82.md](RELEASE_2_1_0_BETA_82.md) | Posledný release |
| [ITERATION_89.md](../ITERATION_89.md) | Plugin capabilities (hotové) |
| [ITERATION_58f.md](ITERATION_58f.md) | Vizuálne bloky (58f-a–g; **58f-h** plánované) |
| [ITERATION_94.md](ITERATION_94.md) | Admin UX pre neskúsených (94a–94d) |
| [ITERATION_95.md](ITERATION_95.md) | Sandpack playground + import komponentov z Git |
| [ISSUES.md](../ISSUES.md#iss-170) | Audit 2026-09-17 — ISS-170–172 (APP_KEY, mail pixely, `/api/test`) |
| [ITERATION_92.md](ITERATION_92.md) | SQLite query index |
| [architecture/QUERY_INDEX.md](architecture/QUERY_INDEX.md) | Prečo SQLite / kam to vedie (It.92) |
| [ITERATION_90.md](ITERATION_90.md) | Editor Workbench (hotové `beta.72`–`73`) |
