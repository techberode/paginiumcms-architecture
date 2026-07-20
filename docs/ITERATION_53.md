# Iteration 53 – Smooth SPA reload & admin navigation

**Status:** ⏳ Planned (implementation **after It.15**)  
**Wave:** Post-15 Editor & UX ([ITERATION_WAVE_POST_15.md](ITERATION_WAVE_POST_15.md))  
**Priority:** 🟡 Medium — foundation before heavy editor modules

## Summary

Eliminate jank and unnecessary full reloads when navigating the admin SPA and public site: stable scroll, predictable data refetch, no layout thrashing, and no duplicate auth/settings fetches that block the UI.

## Problem today

- Hard navigation or `window.location` patterns (mostly fixed in 2.0.30) can still cause flash/reload feel.
- Some admin views refetch large payloads on every mount without cache or `startTransition`.
- Dashboard and list views may block paint while multiple parallel API calls complete.

## Goals

| Deliverable | Description |
|-------------|-------------|
| Navigation audit | Inventory all routes that trigger full remount vs in-place update |
| Data-fetch policy | Shared hooks: stale-while-revalidate, dedupe in-flight requests |
| React 18 transitions | Wrap heavy route switches in `startTransition` where appropriate |
| Scroll restoration | Preserve or reset scroll intentionally per route (admin lists, editor) |
| Loading UX | Skeleton placeholders instead of blank spinners on slow paths |
| Metrics | Optional dev overlay: route transition ms, API waterfall |

## Out of scope

- New editor features (It.54+)
- Backend pagination changes (It.44 BE)

## Dependencies

- ✅ React Router v6 + `MemoryRouter` test helpers
- ⛔ **Blocked until [It.15](ITERATION_15.md) is complete** — plugin routes must follow the same smooth-navigation contract

## Flat-file impact

None — FE/HTTP layer only.

## Acceptance criteria

- [ ] Switching between `/pages`, `/articles`, `/media` feels instant (<200 ms perceived) on LAN deploy
- [ ] Browser Back/Forward does not lose unsaved editor state without warning (existing lock/draft rules)
- [ ] No full page reload on 401/session refresh (AuthContext events only)
- [ ] Vitest: route transition tests for top 5 admin modules
- [ ] `./scripts/iteration-gate.sh` green

## Related

- [ISSUES.md](ISSUES.md) — ISS-025, ISS-033 (session / redirect)
- [ITERATION_32.md](ITERATION_BACKLOG.md) — React chunking (optional parallel)

## Next

→ [Iteration 54](ITERATION_54.md) — modular editor profiles
