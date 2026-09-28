---
title: Public site worklog (24–28 Sep 2026)
description: Incidents, fixes, deploy notes, shortcodes, widgets, and follow-ups from production demo tuning
icon: material/history
---

# Public site worklog — 24–28 September 2026

This document records **symptoms, root causes, fixes, and verification** for the public SPA, landing shortcodes, blog CLS, theme persistence, CMS version footer, deploy script issues, and related documentation. Canonical incident IDs: [ISS-178](../../ISSUES.md#iss-178) through [ISS-186](../../ISSUES.md#iss-186).

---

## 1. Footer CMS version stuck on beta.90

| | |
|---|---|
| **Symptom** | Public footer showed `beta.90` while git tag / health was newer. |
| **Cause** | `GET /api/settings/public` `cmsInfo.version` used `AppVersion::VERSION` constant instead of runtime `AppVersion::current()`. |
| **Fix** | Use `AppVersion::current()` for public `cmsInfo.version`; bump `VERSION` only with tagged releases. |
| **Verify** | Hard refresh → footer matches health tag after deploy. |

---

## 2. Blog article navigation — transient 404 and CLS

| | |
|---|---|
| **Symptom** | Clicking another article briefly showed 404; Lighthouse CLS spike. |
| **Cause** | Slug changed before fetch completed; stale article cleared too early; prose links used `/slug` instead of `/blog/slug`. |
| **Fix** | Skeleton + `useLayoutEffect` loading flag; reserved hero aspect ratio; in-prose blog links use client routing; shell min-height preserved across slug changes. |
| **Verify** | Navigate A→B on production blog; no 404 flash; CLS stable (user confirmed LCP/CLS/INP good). |

---

## 3. Deploy script on demo (`APP_ROOT`, `: command not found`)

| | |
|---|---|
| **Symptom** | `deploy-instance-update.sh` failed with `: command not found`, `APP_ROOT` unset. |
| **Cause** | CRLF line endings or broken `\` continuations in copied script. |
| **Fix** | Run with `bash ./scripts/deploy-instance-update.sh`; ensure `export APP_ROOT=…`; use LF-only scripts from git. |
| **Verify** | Full path including `npm run build:prod` (see [ISS-177](../../ISSUES.md#iss-177)). |

---

## 4. Public light/dark theme reset after reload

| | |
|---|---|
| **Symptom** | User chose light mode; after reload site returned to dark. |
| **Cause** | Theme `localStorage` gated on functional cookies; consent decline cleared `paginium-public-theme`. |
| **Fix** | Theme preference is essential UX — not blocked by functional cookie gate; early boot script in `index.html` applies stored theme before React. |
| **Verify** | Decline optional cookies → toggle theme → reload → theme persists. |

---

## 5. Landing `showcase-hero` missing on public site (admin preview OK)

| | |
|---|---|
| **Symptom** | First shortcode block empty / offset; hero invisible on FE. |
| **Cause** | Admin preview forced `.pg-reveal { opacity: 1 }`; public had `opacity: 0` until scroll; hero used `pg-reveal` + min-height gap. |
| **Fix** | `useLandingReveal` reveals in-viewport blocks immediately; bundled `showcase-hero` **v2** removes `pg-reveal`; CSS fallback for legacy installs; integration test. |
| **Ops** | Admin → **Shortcodes** (runs `seedMissingBundled`) → re-save landing; body uses self-closing syntax and `href2="/blog"`. Purge content cache. |

---

## 6. Primary CTA unreadable on showcase-hero

| | |
|---|---|
| **Symptom** | Solid primary button with no readable label. |
| **Cause** | `.paginium-prose a` link color overrode `.pg-btn-primary` text. |
| **Fix** | `pgLayout.css` / `index.css` — prose links exclude `.pg-btn*`. |

---

## 7. Shortcodes documentation and marketing mini-series

| Deliverable | Location |
|-------------|----------|
| User guide (EN) | [SHORTCODES_AND_WIDGETS.md](../user/SHORTCODES_AND_WIDGETS.md) |
| Landing updates | [LANDING_PAGE.md](../user/LANDING_PAGE.md) |
| SK blog drafts (local/gitignored) | `docs/marketing/ARTICLE_SC01…SC04`, `ARTICLE_SERIES_SK.md` |

Syntax highlights: **self-closing** `[showcase-hero … /]`; second link attribute is **`href2`**, not a second `href`.

---

## 8. Contact page and maps (already shipped)

The **contact layout** (`PageRenderer` template `contact`) includes:

- `ContactForm` — subjects from Settings → Contact
- `CompanyInfoPanel` — company block from Settings → Company
- `CompanyMapEmbed` — **Google Maps iframe** when `company.mapEmbedUrl` passes allow-list (`https://www.google.com/maps/embed…`)

No extra work required for a standard contact page map — only paste embed URL in settings.

Public **widgets** now also support `[widget type="map-embed" …]` in page/article bodies (same allow-list on the server).

---

## 9. New in this release (28 Sep)

| Feature | Notes |
|---------|--------|
| **Widgets** | `map-embed`, `data-table`, `bar-chart`, `form-cta` (Lexa-style building blocks for pages/articles) |
| **Custom 404 / 500** | Settings → Layout → slugs for published error pages; built-in Paginium panels as fallback |
| **Analytics → Geography** | Visitor **dot map** (country centroids, no Google API in admin) + existing bar chart and recent visits |

---

## 10. Production checklist after pull

1. `./scripts/iteration-gate.sh` (or CI) green locally.
2. Deploy: `bash ./scripts/deploy-instance-update.sh` with correct `APP_ROOT`.
3. Purge **content cache** if landing HTML stale.
4. Open **Shortcodes** once; re-save home/landing page.
5. Optional: create pages `404` / `500` with marketing layout; set slugs under **Layout** settings.

---

## Related

- [CHANGELOG.md](../../CHANGELOG.md) — Unreleased entries
- [ISSUES.md](../../ISSUES.md) — ISS-178–ISS-186
- [DEPLOY.md](../../deploy/DEPLOY.md) — post-deploy verification
