# Iteration 66 — Security write-time gate & test packs

**Status:** ⏳ Planned  
**Priority:** 🟡 Medium–High (hardening without public-path latency)  
**Depends on:** Existing CodePolicy / WAF / encryption baseline · complements [It.58](ITERATION_58.md)  
**Related:** [It.50 WAF](ITERATION_50.md) · [SECURITY.md](../SECURITY.md) · [ISSUES.md](ISSUES.md) ISS-008, ISS-014, ISS-089 · [ITERATION_58_ALTERNATIVES.md](ITERATION_58_ALTERNATIVES.md) §11

---

## Goal

Strengthen **security without slowing the public site**: expand fail-closed checks at **write / import / CI** time, add dedicated **security test packs**, and close ops verification gaps — while leaving request-path middleware (WAF, CSRF, rate limits) unchanged in cost profile.

---

## Non-goals

- New heavy per-request WAF rulesets or full-body scan on all routes  
- Rewriting auth/session stack  
- Full CSP removal of `style-src 'unsafe-inline'` (tracked separately; React constraint)  
- Plugin/theme Monaco studio (future; reuses same gate)

---

## Design principles

| Principle | Rule |
|-----------|------|
| **Write-time > request-time** | Prefer validate-on-save/import over extra middleware hops |
| **Fail-closed untrusted** | Untrusted trees already cannot disable CodePolicy — wire every remaining write path |
| **Integrity** | No breaking API contracts; new checks return 422 with structured errors |
| **Performance** | Zero added cost on anonymous `GET` of published pages |
| **Test packs** | Explicit PHPUnit/Vitest subsets in `run-all-tests.zsh` — not only “hidden” in full suite |

---

## Phases

### Phase 66a — Wire existing gates (no new product UI)

- [ ] Audit all untrusted write/import entry points call `CodePolicyEngine::validate` / `validateUntrusted`:
  - Code Editor save  
  - Plugin ZIP import (`PluginPolicyScanner`) — already; add regression asserts  
  - Any theme / layout file write stubs if present  
- [ ] Document required call sites for It.58d Monaco shortcode save (contract test / interface note)  
- [ ] Ensure `codePolicy.enabled=false` never skips untrusted (regression already exists — keep green)

### Phase 66b — Security test packs (CI / alltests)

Add dedicated steps (or extend step 12+) in `scripts/run-all-tests.zsh` + document in `docs/developer/TESTING.md`:

| Pack | Coverage |
|------|----------|
| **CodePolicy pack** | `backend/tests/Core/CodePolicy/` + ShortcodeDefinitionPolicy cases |
| **XSS / HTML pack** | HtmlDomSanitizer + FE `sanitizeHtml` / `safeUrl` (align with `npm run test:security`) |
| **Zip / upload pack** | `ZipEntryGuard`, upload magic-bytes tests |
| **Headers pack** | `SecurityMiddleware` / CSP smoke (static assertions, not live crawl) |

- [ ] Packs run in CI and local alltests; fail the gate on regression  
- [ ] Optional: `scripts/security-static-grep.sh` — fail if new `curl`/`file_get_contents` without `OutboundUrlGuard` in `backend/app` (allow-list known files)

### Phase 66c — Ops & hygiene (no runtime tax)

- [ ] Checklist verify **ISS-008** (HTTPS for password forms in prod)  
- [ ] Checklist verify **ISS-014** (`APP_ENV=production`, CORS) on prod/demo  
- [ ] Sync private `SECURITY_ISSUES.md` with public ISS-104–111 (stale A3–A9 rows)  
- [ ] Document ISS-089 policy (critical-only npm audit) in SECURITY.md  
- [ ] Optional release-only: `npm audit --audit-level=moderate` report (non-blocking unless critical)

### Phase 66d — Soft hardening (optional in same iteration)

- [ ] Media upload: optional GD/Imagick **re-encode** for raster images (off by default; setting toggle) — CPU only on upload  
- [ ] Content HMAC / integrity seal for critical flat-file manifests (settings, plugins index) — verify on read in admin only, not public hot path  

---

## Acceptance criteria

- [ ] Public page TTFB / middleware chain unchanged (no new global middleware)  
- [ ] Untrusted write without policy → 422 / import abort  
- [ ] New alltests packs green; documented in TESTING.md  
- [ ] ISS-008 / 014 verification recorded in ISSUES or deploy checklist  
- [ ] PHPStan L8 + iteration gate green  

---

## Risk & rollback

| Risk | Mitigation |
|------|------------|
| Stricter policy breaks legit plugin ZIP | Fixture + allow routes.php exception already; expand fixtures |
| Static grep false positives | Allow-list file paths in script |
| Re-encode breaks animated GIF/WebP | Default off; format allow-list |

---

## Relation to other iterations

- **It.58d** consumes 66a contract (Monaco save must call gates).  
- **It.67** (below) deepens untrusted surfaces + CSP/deps if needed.  
- **It.50** WAF stays as-is (complete); 66 does not enlarge request body scan scope.
