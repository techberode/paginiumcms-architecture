# Iteration 66 — Security write-time gate & test packs

**Status:** ✅ Complete — **`v2.1.0-beta.22`**  
**Priority:** 🟡 Medium–High (hardening without public-path latency)  
**Depends on:** Existing CodePolicy / WAF / encryption baseline · complements [It.58](ITERATION_58.md)  
**Related:** [It.50 WAF](ITERATION_50.md) · [SECURITY.md](developer/SECURITY.md) · [ISSUES.md](ISSUES.md) ISS-008, ISS-014, ISS-089 · [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md) §11 · [It.67](ITERATION_67.md)

---

## Goal

Strengthen **security without slowing the public site**: expand fail-closed checks at **write / import / CI** time, add dedicated **security test packs**, and close ops verification gaps — while leaving request-path middleware (WAF, CSRF, rate limits) unchanged in cost profile.

---

## Non-goals

- New heavy per-request WAF rulesets or full-body scan on all routes  
- Rewriting auth/session stack  
- Full CSP removal of `style-src 'unsafe-inline'` (React constraint) — see It.67c  
- Plugin/theme Monaco studio (future; reuses same gate)

---

## Delivered

### Phase 66a — Wire existing gates

- [x] Code Editor `writeFile` → `validateUntrusted` when path is untrusted  
- [x] Untrusted trees fail-closed even if `codePolicy.enabled=false` (engine + tests)  
- [x] Contract for It.58d: Monaco shortcode saves must call `ShortcodeDefinitionPolicy` + `validateUntrusted` (documented)

### Phase 66b — Security test packs

- [x] `run-all-tests.zsh` steps **19–21** (`TOTAL_STEPS=21`)  
- [x] `./scripts/security-regression.sh` — CodePolicy + XSS/Zip/headers + static grep + FE security  
- [x] `./scripts/security-static-grep.sh` — outbound HTTP hygiene  
- [x] Documented in [TESTING.md](developer/TESTING.md)

### Phase 66c — Ops & hygiene

- [x] Ops checklist in [SECURITY.md](developer/SECURITY.md) (ISS-008 / 014 / 089)  
- [x] Private `SECURITY_ISSUES.md` sync left to maintainer (gitignored); public ISSUES remain SSOT  

### Phase 66d — Deferred (optional)

- [ ] Media re-encode on upload (default off) — backlog  
- [ ] HMAC seals for critical manifests — backlog → It.67 if needed  

---

## Acceptance criteria

- [x] No new global middleware on public GET  
- [x] Untrusted write policy enforced in Code Editor  
- [x] Security packs + static grep green  
- [x] Docs: TESTING + SECURITY + this file  
- [x] PHPStan / gate green for touched paths  

---

## Smoke

```bash
./scripts/security-regression.sh
./scripts/iteration-gate.sh
```
