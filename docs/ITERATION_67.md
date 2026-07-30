# Iteration 67 — Untrusted surfaces & defense-in-depth

**Status:** ⏳ Planned (after / parallel-soft with It.58d + It.66)  
**Priority:** 🟡 Medium  
**Depends on:** [It.66](ITERATION_66.md) write-time packs · [It.58d](ITERATION_58.md) shortcode Monaco · It.15 plugins  
**Related:** [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md) §11 · CSP S5 · ISS-089

---

## Goal

Complete **defense-in-depth on untrusted authoring surfaces** (layout shortcodes, plugins, future themes) and optional **browser/dependency hygiene** — still without taxing anonymous page views.

---

## Phases

### Phase 67a — It.58d security completion (product wiring)

- [ ] Every shortcode definition save (Monaco / API):  
  `ShortcodeDefinitionPolicy` → `CodePolicyEngine::validateUntrusted` → write → registry update  
- [ ] Expand-on-save produces AST/HTML only — **never** evaluates user PHP  
- [ ] Plugin-registered shortcodes use the same gate  
- [ ] Preview path uses identical validators (no preview bypass)  
- [ ] PHPUnit: hostile expand / illegal class / oversize → 422  
- [ ] Vitest: Monaco save error surfacing

### Phase 67b — Theme / module import parity (prep for future studio)

- [ ] Theme ZIP / module package import reuses `PluginPolicyScanner` patterns + Zip-Slip  
- [ ] Untrusted path markers cover real theme roots used on disk  
- [ ] Document “future Monaco studio must call validateUntrusted” as non-negotiable API

### Phase 67c — CSP & dependency hygiene (careful)

- [ ] Inventory inline styles; prefer CSS variables / classes where cheap (no React rewrite)  
- [ ] Tighten non-style CSP directives if gaps remain (`frame-ancestors`, `base-uri`, `form-action`)  
- [ ] Document residual `style-src 'unsafe-inline'` as accepted until CSS-in-JS strategy changes  
- [ ] ISS-089: keep critical-only CI; periodic human review of moderate advisories

### Phase 67d — Security regression kit (expand packs from It.66)

- [ ] Golden “hostile fixture” corpus: shortcode JSON, plugin PHP, HTML embed snippets  
- [ ] One command: `./scripts/security-regression.sh` wrapping packs + static grep  
- [ ] Optional nightly (not request path): composer/npm audit report artifact

---

## Non-goals

- Elementor-style sandboxed iframe builder  
- Runtime PHP templates from user content  
- Blocking public traffic with ML WAF  

---

## Acceptance criteria

- [ ] Hostile shortcode / plugin fixtures never activate  
- [ ] It.58d save path has zero policy bypasses  
- [ ] Public render path unchanged (compile/cache only)  
- [ ] Gate + security-regression script green  
- [ ] Docs: SECURITY.md + ISSUES updated  

---

## Performance budget

| Change | When it runs | Public GET impact |
|--------|----------------|-------------------|
| Policy on Monaco save | Admin mutate | None |
| Theme/plugin import scan | Import only | None |
| CSP header tweaks | Every response | Negligible (header size) |
| Hostile corpus in CI | CI / alltests | None |

---

## Suggested order

1. **It.66** (packs + wire audits + ops) — can start immediately  
2. **It.58c** (templates + LayoutPreview) — product  
3. **It.58d + It.67a** together — shortcodes with security completion  
4. **It.67b–d** as capacity allows
