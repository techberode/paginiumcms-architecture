# Iteration 82 — Origin Panel (maintainer product cockpit)

> **Status:** ✅ complete (`82a`–`82c`, `82e` shipped in `beta.56`; `82d` deferred)  
> **Priority:** 🔵 (maintainer tooling; no customer-facing impact)  
> **Wave:** Product operations (post-It.71 Performance Guard, post-It.81 editorial ops)  
> **Depends on:** existing dashboard APIs, health/capability probe patterns, Demo module packaging model  
> **Snapshot:** 2026-08-17 · shipped in `v2.1.0-beta.56`

## Goal

Ship an **Origin Panel** — an admin-only cockpit for the **project-owned** PaginiumCMS instances (maintainer dev machine + `paginiumcms.com` production). The panel combines:

1. **Lightweight operational metrics** (entity counts KPI row — full APM charts deferred to external monitoring),
2. **An auto-checked feature checklist** (runtime probes; **no manual checkboxes**),
3. **Project catalog progress** (`docs/manifest/project-catalog.json` SSOT merged with probes → % per iteration),
4. **Release timeline** (curated history from catalog + CHANGELOG anchors),
5. **Strict distribution gating** — module **absent from customer install archives** (Demo tier).

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **82a** | Origin gate + packaging exclusion | 🔵 P1 | ✅ done | `OriginPanelMode`, dev LAN allow, public settings, nav `originOnly`, [ORIGIN_PANEL_PACKAGING.md](ORIGIN_PANEL_PACKAGING.md) |
| **82b** | Feature probe registry + checklist UI | 🔵 P2 | ✅ done | 11 probes, `/api/admin/origin/overview`, `OriginPanelView` |
| **82c** | Dashboard metrics lite | 🔵 P3 | ✅ done | Admin counts KPI row in overview (sparklines / APM embed deferred) |
| **82e** | Project catalog + progress % | 🔵 P2 | ✅ done | `project-catalog.json`, merge service, iteration bars + timeline |
| **82d** | Host metrics ingest hook | 🔵 P4 | ⏸️ deferred | Absorbs remainder of [It.46](ITERATION_46.md) only if needed |

---

## SSOT: project catalog vs release checklist

| Artifact | Role | Origin uses |
|----------|------|-------------|
| `docs/manifest/project-catalog.json` | Product iteration progress, sub-items, weights, timeline | ✅ merged with probes |
| Runtime probes | Wiring verification (routes, services) | ✅ auto status |
| `docs/en/CHECKLIST.md` | Human **release gate** before tag (security, CI) | ❌ link only — not imported |
| `ITERATION_BACKLOG.md` | Human planning doc | ❌ mirror via catalog |

**Percent rule:** `sum(weight × probeScore) / sum(weight)` per iteration; `probeScore`: implemented=1, partial=0.5, missing=0; items without probe use catalog `phase`.

---

## Env contract (root `.env` — maintainer only)

```env
ORIGIN_PANEL=false
ORIGIN_PANEL_ALLOWED_HOSTS=paginiumcms.com,localhost,127.0.0.1
```

In `APP_ENV=development`, private/LAN IPs are allowed when `ORIGIN_PANEL=true` (maintainer dev machine).

**Important:** PHP loads **root** `.env` first; `backend/.env` is fallback only.

---

## API

| Method | Route | Response |
|--------|-------|----------|
| `GET` | `/api/admin/origin/overview` | health, counts, probes, summary, catalog |
| `GET` | `/api/admin/origin/probes` | probes + summary + catalog |
| `GET` | `/api/admin/origin/catalog` | catalog merge only |

Auth: `SUPER_ADMIN` + 2FA. Disabled gate → **404**.

---

## Definition of Done (shipped)

### 82a
- [x] `OriginPanelMode` unit tests (env, allowlist, dev LAN, demo fail-closed)
- [x] boot `warnIfMisconfigured()`
- [x] packaging doc + exclusion path list
- [x] FE nav hidden when `origin.enabled` false

### 82b
- [x] ≥10 probes + PHPUnit registry test
- [x] FE checklist Loading / disabled / Success states
- [x] no manual checkbox in UI

### 82c
- [x] entity counts KPI row (existing `AdminCountsService`)

### 82e
- [x] `project-catalog.json` + merge service + FE progress bars + timeline
- [x] `./scripts/validate-project-catalog.sh` in iteration gate

---

## Non-goals

- Grafana/Prometheus inside CMS (`82d` deferred),
- Manual “mark done” in admin,
- Customer install archive inclusion,
- Replacing `CHECKLIST.md` release gate.

---

## Related documents

- [ORIGIN_PANEL_PACKAGING.md](ORIGIN_PANEL_PACKAGING.md)
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md)
- [CHECKLIST.md](CHECKLIST.md) — complementary release gate
