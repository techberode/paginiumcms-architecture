# PaginiumCMS — kontext pre pokračovanie vývoja

> **Účel:** stručný handoff pre ďalšiu reláciu  
> **Checkpoint:** 6. september 2026 · **`v2.1.0-beta.66`**  
> **Aktívna fáza:** Stabilizácia — It.25 M1+ preflight dodané; cieľ stabilný tag **september 2026**

Anglický master s detailmi: [en/CONTINUATION.md](../en/CONTINUATION.md)

---

## Aktuálny stav (stručne)

| Oblasť | Stav |
|--------|------|
| Posledné vydanie | ✅ `v2.1.0-beta.65` — setup wizard M1+ preflight |
| It.25 wizard | ✅ `beta.62`–`beta.65` (Server → Admin → Web → Infra) |
| Update UX | ✅ dashboard banner + deploy blockers (`beta.64`) |
| Stabilizačný smoke M1–M5 | 🟡 M1/M5 OK; M2–M4 ešte |
| It.83+ / nové moduly | 🔒 zmrazené do stabilného releasu |

---

## Setup wizard — kroky

1. **Server** — `GET /api/setup/preflight` (read-only, bez auto-inštalácie)
2. **Administrátor** — prvý SUPER_ADMIN
3. **Web** — názov + jazyk
4. **Infra** — `backendPort`, `storageDriver`
5. **Hotovo** — dashboard

Dokumentácia: [ITERATION_25.md](ITERATION_25.md), [INSTALLATION.md](user/INSTALLATION.md) §7, [RELEASE_2_1_0_BETA_65.md](../en/RELEASE_2_1_0_BETA_65.md).

---

## Príkazy

```bash
./scripts/iteration-gate.sh
./scripts/smoke-it25.sh
curl -s http://127.0.0.1:8080/api/setup/preflight | jq .
```

---

Historické podrobnosti: [`CHANGELOG.md`](../../CHANGELOG.md), [`ISSUES.md`](../ISSUES.md) (ISS-162).
