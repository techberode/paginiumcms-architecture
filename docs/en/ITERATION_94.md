# Iteration 94 — Admin self-service UX (novice efficiency)

> **Status:** ⏳ planned (audit September 17, 2026)  
> **Priority:** 🟡 **P1 product** — perceived quality and completion rate for non-technical admins  
> **Depends on:** [It.93](ITERATION_93.md) (dashboard, command palette), [It.25](ITERATION_25.md) (setup + onboarding tour), settings i18n ([SETTINGS_I18N.md](SETTINGS_I18N.md))  
> **Related (not It.94):** true **visual block canvas** → [It.58f-h](ITERATION_58f.md#slice-58f-h--visual-block-canvas) (extends page blocks; do not duplicate under 94)

## Why this iteration

Several admin surfaces work for power users but leave **inexperienced editors** guessing: whether an action succeeded, what a setting means, or how to assemble a page without developer help. This iteration bundles **feedback**, **onboarding persistence**, **contextual help**, and **discoverability** — without a second SSOT or new backend stores where avoidable.

| # | Finding | Why it matters for inexperienced users |
|---|---------|----------------------------------------|
| 1 | `LayoutBuilderCard` (~69 lines) is **template pick only**, not a drag-and-drop block canvas | Editors choose a preset layout but cannot stack hero / gallery / CTA visually — the gap between “CMS for developers” and “CMS for anyone”. **Scheduled as [58f-h](ITERATION_58f.md#slice-58f-h--visual-block-canvas), not 94.** |
| 2 | **No unified toast / notification system** (re-verified) | Save/delete/bulk feedback relies on inline banners; easy to miss, especially on long lists |
| 3 | **No persistent getting-started checklist** on the dashboard | `OnboardingTour` is a one-time tour; checklists with progress outperform one-shot tours for task completion |
| 4 | **Low `Tooltip` / field-help coverage** (~4 usages) | Complex settings (Performance Guard, S3, redirects) lack in-form context; admins leave the form to search docs |
| 5 | **No keyboard shortcut cheat sheet** | `AdminCommandPalette` (Cmd+K) exists; no `?` / Cmd+/ modal listing shortcuts — novices never discover them |

---

## Recommended delivery order

| Order | Slice | Scope | Rationale |
|------:|-------|--------|-----------|
| 1 | **[58f-h](ITERATION_58f.md#slice-58f-h--visual-block-canvas)** | Visual block canvas (dnd-kit) on page outline | Largest product gap; reuses `PageOutlineEditor`, `LandingHeroRenderer`, `FeatureGalleryRenderer` |
| 2 | **94a** | Unified toast system | Fast win; improves whole admin perceived quality |
| 3 | **94b** | Getting-started checklist widget | Extends It.25 onboarding; flat-file or settings-backed progress |
| 4 | **94c** | Settings tooltip / help expansion | Incremental; high independence |
| 5 | **94d** | Keyboard shortcuts modal | Low effort; pairs with It.93 command palette |

Parallel with **It.89** / **It.92** where FE-only slices do not touch plugin SDK contracts. Developer **Sandpack playground** and private component packs → [It.95](ITERATION_95.md).

---

## Slice 94a — Unified toast system

**Goal:** One notification channel for success, error, and in-progress states across admin modules.

| Item | Detail |
|------|--------|
| Library | Prefer **sonner** or **react-hot-toast** (evaluate bundle size + a11y); single provider at admin root |
| Migration | Replace ad-hoc inline success/error banners **progressively** (content save, bulk actions, settings, mail, system update) |
| API | Thin wrapper `useAdminToast()` → `success` / `error` / `promise` for async saves |
| i18n | Messages via existing module keys; no hardcoded SK/EN strings |
| a11y | `role="status"` / live region; respect reduced motion |

**Do not:** duplicate backend error text without sanitization; log secrets in toast body.

---

## Slice 94b — Getting-started checklist (dashboard)

**Goal:** Persistent widget on SUPER_ADMIN / ADMIN dashboard: 5–6 tasks with progress bar, survives sessions.

Example items (configurable later):

- First article or page published  
- SEO basics (site title / description)  
- Custom domain or `APP_URL` verified  
- Outbound mail or notification channel smoke-tested  
- 2FA enabled for admin account  

| Item | Detail |
|------|--------|
| vs `OnboardingTour` | Tour = orientation once; checklist = **task completion** until dismissed or 100% |
| Storage | Settings key or flat-file user preference (`onboarding.checklist`); backend optional probe endpoints reusing health/setup patterns |
| UX | Link each row to deep link ([ADMIN_DEEP_LINKS.md](architecture/ADMIN_DEEP_LINKS.md)); checkbox auto-check when probe passes |
| i18n | SK/EN module `dashboard` or `onboarding` |

---

## Slice 94c — Contextual help on complex settings

**Goal:** Extend `SettingHelpTooltip` / `translateSettingFieldTooltip` coverage to high-friction groups.

Priority groups (first pass):

- Performance Guard (`performance.*`)  
- Media / S3 driver (`media.s3*`, migration)  
- Redirects / firewall / WAF where labels alone are insufficient  

| Item | Detail |
|------|--------|
| Pattern | Reuse `settings` i18n `help` + tooltip component; no new tooltip framework |
| Audit | Grep settings schema groups vs FE label coverage; track % in iteration DoD |

---

## Slice 94d — Keyboard shortcuts cheat sheet

**Goal:** Modal listing admin shortcuts; discoverable without memorizing Cmd+K.

| Item | Detail |
|------|--------|
| Triggers | `?` (when focus not in input), **Cmd+/** / **Ctrl+/** |
| Content | Command palette, navigation, editor (if focused), list bulk shortcuts where stable |
| Implementation | Small modal component; register shortcuts next to `AdminCommandPalette` |
| i18n | SK/EN; link from command palette footer (“View all shortcuts”) |

---

## Definition of Done (It.94 slices 94a–94d)

- Toasts used in at least **core paths**: content save/delete, settings save, one bulk list action.  
- Checklist visible on dashboard for eligible roles; at least **one** auto-completing probe wired.  
- **≥ 80%** of fields in the three priority settings groups have help tooltips (or documented exception).  
- Shortcuts modal opens via `?` and Cmd+/; lists palette + ≥ 5 other shortcuts.  
- `./scripts/iteration-gate.sh` green; SK/EN strings for new UI.

**58f-h** DoD lives in [ITERATION_58f.md](ITERATION_58f.md).

---

## Queue note

Ship **58f-h** before or in parallel with **94a** if product priority is page building; ship **94a** first if the goal is fastest admin-wide polish. Full queue: [CONTINUATION.md](CONTINUATION.md), [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md).
