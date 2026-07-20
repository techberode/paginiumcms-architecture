# Iteration 53 – Smooth SPA reload & admin navigation

**Status:** ✅ Complete  
**Version:** **2.0.39**

## Summary

Eliminate jank and unnecessary full reloads when navigating the admin SPA: React Query cache, scroll restoration, skeleton loading UX, and removal of the last hard reload in the editor.

## Deliverables

| Area | Change | Status |
|------|--------|--------|
| React Query | `QueryClientProvider`, `useAdminListQuery`, `queryKeys` | ✅ |
| Dashboard | Cached fetch + `AdminPageSkeleton` | ✅ |
| Pages / Articles | SWR list query + `AdminListSkeleton` | ✅ |
| Media | Skeleton instead of blank spinner | ✅ |
| Extensions | Cached list query | ✅ |
| Admin counts | Cached sidebar badges | ✅ |
| Settings | Non-blocking reload after first load | ✅ |
| Scroll | Reset admin scroll container on route change | ✅ |
| Hard reload | `MarkdownEditor` version restore → `loadContent()` | ✅ |
| Public SPA | `/login` link via React Router | ✅ |
| Router | `v7_startTransition` on `BrowserRouter` | ✅ |
| Debug | Route transition duration in `DebugRouteTracker` | ✅ |

## Acceptance criteria

- [x] Switching between `/pages`, `/articles`, `/media` uses cached data (no blank blocking spinner on revisit)
- [x] No `window.location.reload()` in admin editor restore path
- [x] 401/session refresh remains event-based (no full reload)
- [x] Vitest: `adminRouteTransitions.test.tsx`, `ResponsiveLayout.test.tsx`
- [x] `./scripts/iteration-gate.sh` green

## Related

- [ISSUES.md](ISSUES.md) — ISS-025, ISS-033
- [ITERATION_15.md](ITERATION_15.md) — plugin routes follow same SPA contract

## Next

→ [Iteration 54](ITERATION_54.md) — modular editor profiles
