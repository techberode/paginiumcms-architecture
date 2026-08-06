# UX polish — Phases A, B, C (`v2.1.0-beta.28`)

> **Status:** ✅ shipped in `v2.1.0-beta.28` (2026-08-06)  
> **Release note:** [RELEASE_2_1_0_BETA_28.md](RELEASE_2_1_0_BETA_28.md)

Admin and public-site UX improvements delivered in three phases between It.71 and the beta.28 tag.

---

## Phase A — public footer, navigation, SEO, newsletter contrast

| Feature | Location | Notes |
|---------|----------|-------|
| CMS version badge | `Footer.tsx`, `GET /api/settings/public` → `cmsInfo.version` | From `AppVersion::VERSION` |
| Back to top (public) | `PublicSiteLayout` → `BackToTopButton` | Appears after 400px scroll |
| Back to top (admin) | `ResponsiveLayout` → `BackToTopButton` with `scrollContainerRef` | Scrolls main admin pane, not `window` |
| SEO health checklist | `seoHealth.ts`, `SeoHealthChecklist.tsx`, editor + list tooltips | Issue codes with i18n |
| Newsletter light mode | `NewsletterSubscribersPanel`, `NewsletterPreferenceFields` | [ISS-130](../ISSUES.md#iss-130) |

---

## Phase B — analytics charts

| Tab | Components |
|-----|------------|
| Overview | Daily trend (visits + page views), top pages ranked bars, device segment chart |
| Pages | Ranked bars for pages and articles |
| Sources | Ranked bars by referer (color by type) |
| Devices | Segment chart + browser ranked bars |
| Geo | Country aggregation chart + recent visits list |

Shared: `analyticsChartData.ts`, `AnalyticsRankedBarChart.tsx`, `AnalyticsSegmentChart.tsx`, Vitest in `analyticsChartData.test.ts`.

**CI hotfix:** wrong relative import `../../api/analytics` → `../../../api/analytics` ([ISS-132](../ISSUES.md#iss-132)).

---

## Phase C — newsletter bulk actions

| Capability | API |
|------------|-----|
| Bulk unsubscribe | `POST /api/admin/newsletter/subscribers/bulk-unsubscribe` |
| Bulk delete | `POST /api/admin/newsletter/subscribers/bulk-delete` |
| Single unsubscribe | `POST /api/admin/newsletter/subscribers/{id}/unsubscribe` |
| Single delete | `DELETE /api/admin/newsletter/subscribers/{id}` |

UI: checkbox selection, `BulkActionBar`, status filter, page size via `AdminListToolbar`, per-row actions in `NewsletterSubscribersPanel.tsx`.

Repository: `NewsletterRepository::bulkUnsubscribe()`, `bulkDelete()`, `unsubscribeById()`, `deleteById()`.

---

## Verification

```bash
./scripts/iteration-gate.sh
npm test -- --run src/components/frontend/BackToTopButton.test.tsx
npm test -- --run src/components/backend/analytics/analyticsChartData.test.ts
./vendor/bin/phpunit backend/tests/Http/Controllers/Admin/NewsletterAdminControllerTest.php
```

Manual: long admin list (Logs, Newsletter) → back-to-top; Analytics tabs show charts; Newsletter bulk with confirm dialogs.

**CI hotfix (admin back-to-top):** tests rendering `ResponsiveLayout` must use `renderWithProviders` ([ISS-133](../ISSUES.md#iss-133)).
