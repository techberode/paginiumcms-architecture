# Iteration 102 — Assisted translation polish (It.76 follow-up)

> **Status:** ⏳ planned (foundation shipped: It.76 + It.77 drivers, LibreTranslate self-host runbook)  
> **Priority:** 🔵  
> **Depends on:** [It.76](ITERATION_76.md), [It.77](ITERATION_77.md)  
> **Operator note (2026-10):** Manual locale editing remains valid; self-hosted LibreTranslate may stay disabled until this iteration.

## Goal

Keep assisted translation **optional** and **safe**, but make it usable for real editorial content (Markdown, shortcodes, multi-locale drafts) without mangling structure or requiring a full page reload to see results.

## Problems observed (production homelab)

| Area | Symptom | Root cause (current) |
|------|---------|----------------------|
| Shortcodes / Markdown | Inner text and `[/tag]` closers translated; duplicated broken markup | `TranslationPlaceholderGuard` protects opening `[tag …]`, fenced/inline code, URLs — not closing `[/tag]` or whole paired blocks |
| Editor UX | EN draft appears only after leaving editor, reload, delay | Client/server locale state not refreshed immediately after Apply |
| Workflow | Technical articles need human EN body | By design today — MT on full `body` is a poor fit for code-heavy content |

## Planned scope

1. **Placeholder guard v2**
   - Protect closing shortcodes `[/name]`.
   - Optional mode: protect entire paired blocks `[tag …]…[/tag]` (inner text stays source language).
   - Optional: “translate metadata only” (`title`, `seoTitle`, `seoDescription`) without sending `body`.

2. **Editor UX**
   - After successful Apply: refresh document, switch to first applied target locale (or user choice), no list round-trip.
   - Surface per-locale `skipped` / `failed` reasons in the toast when `NO_RESULT` (today generic 422).

3. **Settings / ops**
   - Document “disable provider = zero outbound” as supported (LibreTranslate container optional).
   - Keep production pattern: HTTPS `baseUrl` via nginx `/internal/translate/` ([AGENT_OPERATIONS.md](runbooks/AGENT_OPERATIONS.md) §2).

4. **Tests**
   - `[alert-box tone="info"]…[/alert-box]` + fenced PHP snippet fixtures.
   - Apply → editor state regression (Vitest or API contract test).

## Out of scope

- Autonomous publish, new cloud providers, or replacing human review.
- Public visitor-facing AI chat (see [It.75](ITERATION_75.md) — admin proposals only).

## Definition of Done

- [ ] Shortcode closers (and optional full-block protect) covered by unit tests.
- [ ] Optional “fields to translate” respected by `TranslationService::propose`.
- [ ] Editor shows applied draft locale without manual list reload.
- [ ] English docs + CHANGELOG entry on ship.
