# Iteration 101 — Public form builder (widgets & shortcodes)

> **Status:** planned (separate from contact form, 404 broken-content report, and bundled shortcodes shipped in It.58+)  
> **Priority:** medium — operator-facing authoring, not a SQL forms engine  
> **SSOT:** flat-file field definitions + server-side expand/render; submissions reuse Message Desk / audited stores

---

## 1. Problem

Operators need **reusable public forms** (contact variants, lead capture, broken-link reports, event signup) embeddable in pages/articles via **shortcodes or widgets**, without duplicating one-off React forms or hard-coding each use case in PHP.

Iteration **101** is **not** a generic SaaS form SaaS clone in v1. It is:

- Declarative **form schemas** (fields, validation, spam/rate limits),
- **Render** on the public site (React island or safe server HTML),
- **Submit** through existing guarded endpoints (CSRF/rate limit where applicable),
- **Admin** list/export aligned with Message Desk patterns.

---

## 2. Out of scope (101 v1)

- Multi-step wizards, payments, file uploads beyond existing upload policy,
- Anonymous form builder without auth (definitions are admin-only),
- Replacing the core **contact** module — 101 **extends** it.

---

## 3. Deliverables (target)

| ID | Deliverable |
|----|-------------|
| **101a** | Form definition flat-file schema + admin CRUD (AuthZ `forms:manage`) |
| **101b** | Bundled presets: contact-lite, broken-content (context fields), newsletter opt-in |
| **101c** | Shortcode `[form id="…"]` + widget type `form-embed` with React hydration |
| **101d** | Submission pipeline (validation, LogSanitizer, rate limit, desk routing) |
| **101e** | Docs + Origin probe `it.101.form_builder` |

---

## 4. Related (already shipped / partial)

- Custom **404** page slug + **broken-content report** on 404 (context URL/referer → Message Desk).
- **Contact** template + `/api/contact` + `[widget type="form-cta"]`.
- Shortcode registry & Code Policy (It.58d).

See [SHORTCODES_AND_WIDGETS.md](user/SHORTCODES_AND_WIDGETS.md), [LANDING_PAGE.md](user/LANDING_PAGE.md).
