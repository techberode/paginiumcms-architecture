# Iteration 101 — Public form builder (widgets & shortcodes)

> **Status:** ✅ shipped (MVP 101a–101e)  
> **Priority:** medium — operator-facing authoring, not a SQL forms engine  
> **SSOT:** flat-file field definitions + server-side expand/render; submissions reuse Message Desk / audited stores

---

## Shipped (101)

| ID | Deliverable |
|----|-------------|
| **101a** | `data/forms/definitions/{id}.json` + admin CRUD `forms:manage` → `/forms` |
| **101b** | Presets: `contact-lite`, `broken-content`, `newsletter-opt-in` |
| **101c** | `[form id="…" /]` + `[widget type="form-embed" id="…" /]` → `form-embed` React island |
| **101d** | `GET /api/public/forms/{id}`, `POST …/submit` (honeypot, contact rate limit, desk routing) |
| **101e** | Origin probe `it.101.form_builder` |

Admin edits definitions as JSON (fields, routing, enabled). Copy shortcode/widget markdown from the list.

---

## Out of scope (unchanged)

Multi-step wizards, payments, uploads beyond upload policy, anonymous builder UI.

---

## Related

[SHORTCODES_AND_WIDGETS.md](user/SHORTCODES_AND_WIDGETS.md) · Contact `/api/contact` · 404 broken-content report
