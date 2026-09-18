# Iteration 94 — Admin self-service UX (novice safety & context)

> **Status:** ✅ **complete** (September 18, 2026)  
> **Priority:** 🟡 **P1 product** — reduce irreversible mistakes and “did it work?” anxiety for non-technical admins  
> **Depends on:** [It.25](ITERATION_25.md) (setup, onboarding tour), [It.93](ITERATION_93.md) (command palette), [SETTINGS_I18N.md](SETTINGS_I18N.md)  
> **Not It.94:** visual block canvas → [It.58f-h](ITERATION_58f.md)

---

## Audit baseline (September 18, 2026)

Re-verified in code (not impressions):

| # | Finding | Code reality | It.94 response |
|---|---------|--------------|----------------|
| 🔴1 | Destructive actions need confirmation | Was `window.confirm` in many modules | **94a** ✅ `ConfirmProvider` + full admin migration |
| 🔴2 | Toast / feedback | ✅ `NotificationProvider` + `useToast` | **94b** ✅ [ADMIN_TOAST_COVERAGE_94.md](ADMIN_TOAST_COVERAGE_94.md) |
| 🔴3 | Inline field validation | Settings RHF + API errors | **94c** ✅ `FieldError` on settings, API keys, change password |
| 🟡4 | Onboarding | `OnboardingTour` = orientation only | **94d** ✅ dashboard checklist + deep links |
| 🟡5 | Context help on technical fields | One-line tooltips only | **94e** ✅ `ContextHelpPanel` + doc links |
| 🟡6 | Setup wizard first content | It.25 ends at infra | **94d** checklist links to content/settings |
| 🟢7 | Skeleton / empty states | Partial | **94f** unchanged (backlog) |
| 🟢8 | Responsive image srcset | Media previews | backlog / It.79 |

---

## Implementation summary

| Slice | Delivered |
|-------|-----------|
| **94a** | `ConfirmContext`, `useConfirm`, `useAdminConfirm`, `confirmDialog` bridge; no raw browser confirm in `frontend/src` admin paths |
| **94e** | Settings: `systemUpdate`, `engine` performance guard, `media` S3; Redirects admin header help |
| **94c** | `FieldError` component — `SettingsView`, `ApiKeysManager`, `ChangePasswordModal` |
| **94b** | Toast audit document (see link above) |
| **94d** | `GettingStartedChecklist` on dashboard, localStorage dismiss |
| **94g** | `KeyboardShortcutsModal`, palette footer link, `ResponsiveLayout` key handlers |
| **94f** | Not expanded this iteration |

---

## Key files

| Path | Role |
|------|------|
| `frontend/src/context/ConfirmContext.tsx` | Accessible alert dialog provider |
| `frontend/src/hooks/useAdminConfirm.ts` | Standard destructive confirm |
| `frontend/src/utils/confirmDialog.ts` | Non-React bridge (e.g. cache purge hook) |
| `frontend/src/components/admin/ContextHelpPanel.tsx` | Tooltip + detail + doc link |
| `frontend/src/components/dashboard/GettingStartedChecklist.tsx` | Task checklist |
| `frontend/src/components/admin/KeyboardShortcutsModal.tsx` | Shortcut cheat sheet |

---

## Definition of Done (It.94)

- [x] **94a:** Destructive admin actions use confirm modal (not `window.confirm` in app source).
- [x] **94e:** Priority groups (performance, media/S3, system update, redirects UI) have tooltip + docLink where docs exist.
- [x] **94c:** `FieldError` on settings + ≥ 2 other major forms.
- [x] **94b:** Toast coverage documented; core paths toast on mutate.
- [x] **94d:** Checklist on dashboard with auto-completing probes.
- [x] **94g:** Shortcuts modal SK/EN.
- [x] `./scripts/iteration-gate.sh` green (verify before release).

---

## Related

- [CONTINUATION.md](CONTINUATION.md) · [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md)  
- [It.95](ITERATION_95.md) — Sandpack playground (developer-facing, not novice)
