# Iteration 102 — Assisted translation polish (It.76 follow-up)

> **Status:** ✅ shipped — placeholder guard v2, editor locale refresh, field picker, NO_RESULT detail  
> **Priority:** 🔵  
> **Depends on:** [It.76](ITERATION_76.md), [It.77](ITERATION_77.md)  
> **Operator note:** Disable assisted translation (`enabled` off or `provider` = `none`) for zero outbound traffic; LibreTranslate remains optional via nginx `/internal/translate/`.

## Goal

Keep assisted translation **optional** and **safe**, but make it usable for real editorial content (Markdown, shortcodes, multi-locale drafts) without mangling structure or requiring a full page reload to see results.

## Shipped (102)

| Area | Change |
|------|--------|
| **Placeholder guard v2** | Closing `[/tag]` tokens protected; optional **Protect paired shortcode blocks** setting (`translation.protectShortcodeBlocks`) keeps full `[tag …]…[/tag]` off the wire. |
| **Editor UX** | After Apply, content reload switches to the first applied target locale. |
| **Fields** | Editor checkboxes + “Metadata only” preset; `fields` payload respected by `TranslationService::propose`. |
| **Errors** | `NO_RESULT` returns per-locale `errors.locale_*` for clearer toasts. |

## Definition of Done

- [x] Shortcode closers (and optional full-block protect) covered by unit tests.
- [x] Optional “fields to translate” respected by `TranslationService::propose`.
- [x] Editor shows applied draft locale without manual list reload.
- [x] English docs + CHANGELOG entry on ship.

## Out of scope (unchanged)

- Autonomous publish, new cloud providers, or replacing human review.
- Public visitor-facing AI chat ([It.103](ITERATION_103.md)).

## Related

[It.76](ITERATION_76.md) · [It.77](ITERATION_77.md) · [AGENT_OPERATIONS.md](runbooks/AGENT_OPERATIONS.md) §2 (LibreTranslate nginx)
