# ROADMAP vs runtime truth

`docs/ROADMAP.md` is a **directional** document. It is updated at release milestones, not on every merge. Treating stale ⏳ rows as “not implemented” causes false gaps.

## Authoritative sources (in order)

1. **Origin Panel** — `/platform/origin` (SUPER_ADMIN, `ORIGIN_PANEL` env). Feature probes report `implemented` / `partial` / `missing` from real classes and routes.
2. **[CHANGELOG.md](../../../CHANGELOG.md)** — shipped facts and release tags.
3. **[ISSUES.md](../../ISSUES.md)** — incidents and follow-ups.

## Maintenance options

| Approach | Effort | Benefit |
|----------|--------|---------|
| Manual ROADMAP refresh each release | Low | Good enough if checkpoint date is updated |
| CI guard `scripts/check-roadmap-stale.sh` | Done | Runs in GitHub Actions `backend` job; fails when known-shipped iterations are still marked ⏳ in ROADMAP |
| Generated ROADMAP section from probe JSON | Medium | Single source of truth; needs export endpoint or CLI |

## CI guard

From repo root:

```bash
./scripts/check-roadmap-stale.sh
```

The script encodes a small allow-list of iterations that **must not** appear as `⏳` in `docs/ROADMAP.md` while probe classes exist in `backend/app/Modules/Origin/Probes/`.

When adding a new Origin probe for a shipped iteration, extend the script and update ROADMAP in the same PR.
