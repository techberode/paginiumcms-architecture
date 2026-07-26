# Release checklist — PaginiumCMS

> Posledná verzia: **2.1.0-beta.8** · 2026-07-26 · tag **`v2.1.0-beta.8`**  
> Tento súbor obsahuje **copy-paste** bloky pre GitHub Release.

> **Poznámka k verziám:** **`2.0.58`** → `f53e71e` · **Public Beta 1** → `e3e0d82` · **Beta 1 Testing** → `c68e72b` · **Beta 1 patch (RR GHSA)** → *(commit po push)*.

### Kontinuita verzií (nepreskakovať)

| Verzia | Git tag | Release commit | Stav |
|--------|---------|----------------|------|
| 2.0.49 | `v2.0.49` | `1ac58cf` | ✅ tagged |
| **2.0.50** | **`v2.0.50`** | **`67d77bb`** | ✅ tagged |
| **2.0.51** | **`v2.0.51`** | **`d9b7171`** | ✅ tagged |
| **2.0.52** | **`v2.0.52`** | **`9d930a1`** | ✅ tagged |
| **2.0.53** | **`v2.0.53`** | **`aee1494`** | ✅ tagged |
| **2.0.54** | **`v2.0.54`** | **`2338fe9`** | ✅ tagged |
| **2.0.55** | **`v2.0.55`** | **`d8c3437`** | ✅ tagged |
| **2.0.56** | **`v2.0.56`** | **`0664ba3`** | ✅ tagged |
| **2.0.57** | **`v2.0.57`** | **`e84b71f`** | ✅ tagged |
| **2.0.58** | **`v2.0.58`** | **`f53e71e`** | ✅ tagged |
| Public Beta 1 | **`v2.1.0-beta.1`** | **`e3e0d82`** | ✅ tagged |
| **Beta 1 Testing** | **`v2.1.0-beta.2`** | **`c68e72b`** | ✅ tagged |
| **Beta 1 patch (RR GHSA + CMS info)** | **`v2.1.0-beta.3`** | ✅ tagged |
| **It.57 — Auto tags & meta description** | **`v2.1.0-beta.4`** | **`2091076`** | ✅ tagged |
| **It.56 — Rich navigation + auth/session fixes** | **`v2.1.0-beta.5`** | ✅ tagged |
| **Security audit — XSS, Zip-Slip, deploy** | **`v2.1.0-beta.6`** | ✅ tagged |
| **Deps + Vitest + deploy env (ISS-089–092)** | **`v2.1.0-beta.7`** | ✅ tagged |
| **It.58b — Color schemes + themed public site** | **`v2.1.0-beta.8`** | ⏳ tag po gate |

**Pravidlo:** Post-beta patchy — `2.0.59+` alebo `2.1.0-beta.N` podľa rozsahu.

### GitHub Releases (beta publish)

Po pushi tagov a security docs:

```bash
gh auth login   # ak ešte nie
./scripts/create-github-releases.sh
```

Testerom / security reviewerovi poslať **`v2.1.0-beta.8`** + [SECURITY_REVIEW.md](../SECURITY_REVIEW.md).

---

## v2.1.0-beta.8 — It.58b color schemes (ISS-093)

```bash
./scripts/iteration-gate.sh
cd frontend && npm test && npm run type-check
cd frontend && npm audit --audit-level=critical
composer audit
```

### Commit

```bash
git add backend/app/Support/AppVersion.php CHANGELOG.md docs/developer/RELEASE.md
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.8 — It.58b color schemes + themed public site.

Appearance settings, 5 preset schemes, public API slice, theme-* CSS on public UI.
ISS-093 ESLint brace-expansion fix. THEMES.md + CI audit critical gate.
EOF
)"
git tag v2.1.0-beta.8
git push origin main
git push origin v2.1.0-beta.8
```

---

## GitHub Release — copy-paste (v2.1.0-beta.8)

**Title:** `v2.1.0-beta.8 — Color schemes & themed public site (It.58b)`

**Body:**

```markdown
## Summary

- **It.58b** — 5 color schemes × light/dark, Settings → Vzhľad, live wireframe preview
- **Public site** — `theme-*` tokens on Navbar, Footer, blog, login, maintenance, markdown prose
- **API** — `GET /api/settings/public` → `appearance` block
- **ISS-093** — ESLint fix (removed `brace-expansion@5` override)

## Docs

- [THEMES.md](docs/architecture/THEMES.md)
- [ITERATION_58.md](docs/ITERATION_58.md) — 58b ✅, 58a layout builder pending It.15

## Known

- **ISS-089** — npm audit high GHSA-qwww-vcr4-c8h2 (React Router RSC-only); SPA not affected on React 18

## Test plan

- [ ] Nastavenia → Stránka → Vzhľad — zmena schémy viditeľná na `/` a `/blog`
- [ ] Prepínač svetlý/tmavý v Navbar (ak `allowUserToggle`)
- [ ] `cd frontend && npm test` — 244 tests
- [ ] `npm audit --audit-level=critical` — 0 critical
```

---

## v2.1.0-beta.7 — Deps, Vitest, It.58 doc (ISS-089–092)

```bash
./scripts/iteration-gate.sh
cd frontend && npm test && npm run type-check
cd frontend && npm audit --audit-level=critical
composer audit
```

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.7 — Vitest RR fix, eslint pin, deploy env, It.58 doc.

Fix react-router 8 override breaking Vitest on React 18. Pin eslint 9.x.
Deploy env gitignore + script :? syntax. ITERATION_58_ALTERNATIVES + ISS-089–092.
EOF
)"
git tag v2.1.0-beta.7
git push origin main
git push origin v2.1.0-beta.7
```

---

## GitHub Release — copy-paste (v2.1.0-beta.7)

**Title:** `v2.1.0-beta.7 — Vitest fix, deps hygiene, layout builder doc`

**Body:**

```markdown
## Summary

- **ISS-091** — Vitest 75/75: removed erroneous `react-router@8` override on React 18
- **ISS-090** — ESLint pinned to ^9.39 (npm audit fix no longer ERESOLVE)
- **ISS-092** — Deploy local env gitignored; `deploy-frontend-lan.sh` fail-fast syntax
- **Docs:** [ITERATION_58_ALTERNATIVES.md](docs/ITERATION_58_ALTERNATIVES.md) — 3 layout options + security status

## Known

- **ISS-089** — npm audit high GHSA-qwww-vcr4-c8h2 (React Router RSC-only); SPA not affected on React 18

## Test plan

- [ ] `cd frontend && npm test` — 75 files / 238 tests
- [ ] `npm audit --audit-level=critical` — 0 critical
- [ ] Deploy via `scripts/deploy-frontend-lan.env.local` (see PRIVATE_DOMAIN_DEPLOY §16)
```

---

## v2.1.0-beta.6 — Security audit (ISS-086–088)

```bash
./scripts/iteration-gate.sh
cd frontend && npm audit --audit-level=moderate
composer audit
```

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.6 — security audit ISS-086/087/088.

Stored XSS: HtmlDomSanitizer + DOMPurify defense-in-depth. Backup import ZipEntryGuard.
Deploy script env-only host/user. PHPStan fix isSafeUri regex delimiter.
EOF
)"
git tag v2.1.0-beta.6
git push origin main
git push origin v2.1.0-beta.6
```

---

## GitHub Release — copy-paste (v2.1.0-beta.6)

**Title:** `v2.1.0-beta.6 — Security audit (XSS, Zip-Slip, deploy)`

**Body:**

```markdown
## Summary

Externý security audit pred LAN testom — tri opravy + PHPStan fix.

**Fixes:** ISS-086 (stored XSS), ISS-087 (deploy script hardcoded host/user), ISS-088 (backup Zip-Slip).

## Security

- Backend `HtmlDomSanitizer` — attribute allow-list, blocks `on*`, `javascript:` / `data:` URIs
- Frontend DOMPurify before `dangerouslySetInnerHTML` (defense-in-depth)
- `ZipEntryGuard` on backup import
- Deploy: `DEPLOY_HOST=… DEPLOY_USER=… ./scripts/deploy-frontend-lan.sh`

## Test plan

- [ ] Uložiť článok s `<img onerror=…>` → atribút odstránený v API aj na webe
- [ ] Backup import s `../` entry → odmietnuté
- [ ] `./scripts/deploy-frontend-lan.sh` bez env → fail-fast s návodom
- [ ] `composer stan` + `composer test` + `npm test`

## Docs

- [CHANGELOG.md](CHANGELOG.md#210-beta6--2026-07-24)
- [ISSUES.md](docs/ISSUES.md) ISS-086–088
```

---

## v2.1.0-beta.5 — It.56 Rich navigation + session fix

```bash
./scripts/iteration-gate.sh
cd frontend && npm audit --audit-level=moderate
composer audit
```

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.5 — It.56 rich nav, ISS-079/084/085, settings help.

Rich navigation (icons, descriptions, hover preview). Editor save security-only validation.
Sliding session cookie + longer default lifetime. Settings toggle help SK/EN.
EOF
)"
git tag v2.1.0-beta.5
git push origin main
git push origin v2.1.0-beta.5
```

---

## GitHub Release — copy-paste (v2.1.0-beta.5)

**Title:** `v2.1.0-beta.5 — It.56 Rich navigation + session fix`

**Body:**

```markdown
## Summary

**It.56** — navigation descriptions, Lucide/media icons, hover preview + `navigationUi` settings.

**Fixes:** ISS-079 (blog code block save), ISS-084 (spontaneous logout / sliding session cookie), ISS-085 (Lucide icons + desktop descriptions).

**Admin:** Settings toggle help texts (SK/EN) for enable/disable behavior.

## Added

- Rich navigation fields in admin + public navbar
- Settings help texts for bool toggles (i18n SK/EN)

## Fixed

- Editor profile no longer blocks save on existing code blocks (security-only validation)
- Session cookie sliding refresh; default lifetime 8h dev / 2h prod
- Dynamic Lucide icon resolution in public nav

## Docs

- ISSUES.md ISS-079, ISS-084, ISS-085
- ITERATION_56.md complete
```

---

## v2.1.0-beta.4 — It.57 Auto tags & meta description

```bash
./scripts/iteration-gate.sh
cd frontend && npm audit --audit-level=moderate
composer audit
```

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.4 — It.57 auto tags & meta description.

Suggest-meta API + editor panel. Bundled deps: commonmark 2.8.3, tiptap 3.28.0, dev tooling (#6/#8).
EOF
)"
git tag v2.1.0-beta.4
git push origin main
git push origin v2.1.0-beta.4
```

---

## GitHub Release — copy-paste (v2.1.0-beta.4)

**Title:** `v2.1.0-beta.4 — It.57 Auto tags & meta description`

**Body:**

```markdown
## Summary

**It.57** — deterministic auto tags and meta description from content (markdown, HTML, Tiptap JSON). Editor panel **Navrhnúť meta** + settings toggles. Bundled safe dependency updates (Dependabot #6, #8; tiptap 3.28.0 all packages).

## Added

- `POST /api/admin/content/suggest-meta` — tags + description (rate limit 30/min)
- Settings: auto tag / auto description toggles and limits
- Editor: **ContentMetaSuggestPanel** in content editor shell

## Changed

- `league/commonmark` 2.8.2 → 2.8.3
- All `@tiptap/*` 3.27.3 → 3.28.0 (fixes peer deps from split Dependabot PRs)
- Frontend dev deps: vite, happy-dom, postcss, typescript-eslint, etc.

## Verification

- `./scripts/iteration-gate.sh`
- `composer audit` + `npm audit --audit-level=moderate` = 0 CVE
```

---

## v2.1.0-beta.3 — Beta 1 patch (React Router GHSA + CMS info)

```bash
./scripts/iteration-gate.sh
cd frontend && npm audit --audit-level=moderate
composer audit
```

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.3 — React Router GHSA + CMS info panel.

ISS-078: react-router-dom 6.30.4 → 7.18.1 (3× moderate GHSA after beta.2 tag).
Settings: read-only PaginiumCMS – info (version, MIT, locales). SECURITY docs.
EOF
)"
git tag v2.1.0-beta.3
git push origin main
git push origin v2.1.0-beta.3
```

---

## GitHub Release — copy-paste (v2.1.0-beta.3)

**Title:** `v2.1.0-beta.3 — Beta 1 patch (React Router + CMS info)`

**Body:**

```markdown
## Summary

Patch after **v2.1.0-beta.2**. Three **moderate** React Router npm advisories were published on GitHub **after** the beta.2 tag — not visible at `npm audit --audit-level=high`. Fixed by upgrading to `react-router-dom@7.18.1`. Also adds read-only **Settings → PaginiumCMS – info**.

## Fixed — ISS-078 (dependency)

| GHSA | Title |
|------|-------|
| [GHSA-wrjc-x8rr-h8h6](https://github.com/advisories/GHSA-wrjc-x8rr-h8h6) | Open redirect via backslash in `<Link>` / `useNavigate` (CVE-2025-68470 bypass) |
| [GHSA-jjmj-jmhj-qwj2](https://github.com/advisories/GHSA-jjmj-jmhj-qwj2) | Open redirect leading to XSS |
| [GHSA-337j-9hxr-rhxg](https://github.com/advisories/GHSA-337j-9hxr-rhxg) | Arbitrary constructor injection via `deserializeErrors()` (SSR hydration) |

**PaginiumCMS note:** Beta 1 admin is SPA-only (`BrowserRouter`) — SSR hydration issue does not apply. Open redirect risk is low for static admin routes; still patched upstream.

- `react-router-dom`: **6.30.4** → **7.18.1**
- `npm audit --audit-level=moderate` → **0 vulnerabilities**

## Added

- **Settings → Systém → PaginiumCMS – info** — version, MIT license link, locales, stack, doc links
- Root `LICENSE` (MIT)

## Docs

- [ISS-078](docs/ISSUES.md) · [SECURITY_REVIEW.md](docs/SECURITY_REVIEW.md#post-publication-dependency-disclosures-after-v2110-beta2)

## Verification

- `./scripts/iteration-gate.sh`
- `composer audit` + `npm audit --audit-level=moderate` = 0 CVE

## Notes

- Supersedes **v2.1.0-beta.2** for testers and security review.
- beta.2 release notes claimed `npm audit --audit-level=high` = 0 — still true; these were **moderate** and disclosed post-tag.
```

---

## v2.1.0-beta.2 — Beta 1 Testing (pre-push security gate)

```bash
composer gate
# alebo: ./scripts/iteration-gate.sh
```

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.2 — Beta 1 Testing (pre-push security gate).

Audit trail CSV export: LogSanitizer on all cells (C11-AUDITTRAIL-CSV).
Regresný AuditTrailServiceTest; AppVersion bump.
EOF
)"
git tag v2.1.0-beta.2
git push origin main
git push origin v2.1.0-beta.2
```

---

## GitHub Release — copy-paste (v2.1.0-beta.2)

**Title:** `v2.1.0-beta.2 — Beta 1 Testing`

**Body:**

```markdown
## Summary

Pre-push security gate pred verejným testovaním Beta 1. Jediný blocking nález z auditu (Medium): audit trail CSV export bez `LogSanitizer`.

## Fixed

- **C11-AUDITTRAIL-CSV** — `AuditTrailService::exportAuditToCsv()` — všetky bunky cez `LogSanitizer::value()` + jednotné CSV quoting (rovnaký vzor ako `SecurityAuditStore`). Regresný test `AuditTrailServiceTest`.

## Verification

- PHPUnit full suite green
- PHPStan level 8 = 0
- `composer audit` + `npm audit --audit-level=high` = 0 CVE

## Notes

- Nadväzuje na **v2.1.0-beta.1** (Public Beta 1 docs). Toto je **bezpečnostný patch** pred pushom testerom.
- Detail scope Beta 1: [PUBLIC_BETA1.md](docs/PUBLIC_BETA1.md)
```

---

## v2.1.0-beta.1 — Public Beta 1 (Wave 7)

```bash
composer gate
```

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: v2.1.0-beta.1 — Public Beta 1 (Wave 7).

PUBLIC_BETA1 + BETA_TESTER docs, known limitations, AppVersion bump.
EOF
)"
git tag v2.1.0-beta.1
git push origin main
git push origin v2.1.0-beta.1
```

---

## GitHub Release — copy-paste (v2.1.0-beta.1)

**Title:** `v2.1.0-beta.1 — Public Beta 1`

**Body:** see [PUBLIC_BETA1.md](../PUBLIC_BETA1.md) · full template in git history of this file (Wave 7 block).

---

## 2.0.58 — Wave 6 Beta infra gate

```bash
composer gate
# alebo: composer test && composer stan && cd frontend && npm run type-check && npm run lint && npm run lint:api-barrel && npm test
```

**Acceptance:** dokumentovaný cron + beta checklist; `composer gate` green.

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.58 — Wave 6 beta infra gate (CRON, BETA_INFRA, iteration gate).

Production cron docs, maintainer checklist, expanded beta tester path, lint:api-barrel in gate.
EOF
)"
git tag v2.0.58
git push origin main
git push origin v2.0.58
```

---

## GitHub Release — copy-paste (2.0.58)

**Title:**

```
2.0.58 — Wave 6 Beta infra (CRON docs, BETA_INFRA gate, security baseline)
```

**Body:**

```markdown
**Tag:** `v2.0.58` · **Target:** `main` · **Commit:** `f53e71e`

### Summary

Closes **FINAL_BETA1 Fáza C (Wave 6)**: production cron documentation, maintainer quality gate, security baseline audit for Public Beta 1.

### Added

- **docs/deploy/CRON.md** — `scheduler:run` + `worker:process`, job registry, troubleshooting
- **docs/developer/BETA_INFRA.md** — pre-Beta checklist (gate, onboarding, ISS status)

### Changed

- **iteration-gate.sh** — `npm run lint:api-barrel`
- **user/README.md** beta checklist — health, cron, BETA_INFRA link
- **INSTALLATION.md** — cron required for scheduled publish
- **TESTING.md**, **DEV.md**, **CONTINUATION.md**, root README
- `AppVersion` → 2.0.58

### Test plan

- [ ] `composer gate` green
- [ ] Read [CRON.md](docs/deploy/CRON.md) — crontab copy-paste OK for your host
- [ ] [user/README.md](docs/user/README.md) beta checklist walkthrough
- [ ] No open critical ISS for beta (see [BETA_INFRA.md](docs/developer/BETA_INFRA.md))

### Docs

- [BETA_INFRA.md](docs/developer/BETA_INFRA.md)
- [CRON.md](docs/deploy/CRON.md)
- [CHANGELOG.md](CHANGELOG.md) — 2.0.58
```

---

## 2.0.57 — Wave 5f Docker onboarding + user docs

```bash
composer test && composer stan
cd frontend && npm run type-check && npm test -- --run
# Smoke: ./scripts/first-run.sh && docker compose up -d && curl -s http://localhost:8080/api/health
```

**Acceptance:** clone → `./scripts/first-run.sh` → login admin → dashboard bez 500.

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.57 — Wave 5f Docker onboarding and user docs sync.

README first-run quick start, LOCAL_SETUP/INSTALLATION env tables, FIRST_ADMIN_* in .env.example, AppVersion bump.
EOF
)"
git tag v2.0.57
git push origin main
git push origin v2.0.57
```

---

## GitHub Release — copy-paste (2.0.57)

**Title:**

```
2.0.57 — Wave 5f Docker onboarding + user docs (first-run path)
```

**Body:**

```markdown
**Tag:** `v2.0.57` · **Target:** `main` · **Commit:** `e84b71f`

### Summary

Closes **FINAL_BETA1 Fáza A (Wave 5f)**: one documented path from git clone to working admin — `scripts/first-run.sh`, Docker Compose, env vars for first admin.

### Changed

- Root **README.md** — first-run quick start, links to user + developer docs
- **docs/README.md** — Getting Started via first-run
- **LOCAL_SETUP.md** / **INSTALLATION.md** — env var tables (`FIRST_ADMIN_*`, session, 2FA)
- **`.env.example`** — documented bootstrap admin vars
- **CONTINUATION.md**, **CHECKLIST.md** — Wave 5f shipped
- `AppVersion` → 2.0.57

### Test plan

- [ ] Fresh clone → `./scripts/first-run.sh` → admin login → `/dashboard` OK
- [ ] `docker compose up -d` → `GET /api/health` 200
- [ ] `docker compose --profile dev up -d` → Vite :3025 proxies API
- [ ] Beta path: [docs/user/README.md](docs/user/README.md) checklist

### Docs

- [LOCAL_SETUP.md](docs/developer/LOCAL_SETUP.md)
- [INSTALLATION.md](docs/user/INSTALLATION.md)
- [FIRST_STEPS.md](docs/user/FIRST_STEPS.md)
- [CHANGELOG.md](CHANGELOG.md) — 2.0.57
```

---

## 2.0.56 — Password confirmation (register + admin users)

```bash
composer test && composer stan
cd frontend && npm run type-check && npm test -- --run src/utils/validation.test.ts
```

**Po deployi:** registrácia a admin → Používatelia — obe polia hesla musia sedieť; mismatch → toast / 422.

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.56 — password confirmation on register and admin users.

ValidationRules::validatePasswordConfirmation, RegisterModal + UsersManager UI,
AuthController/UserController 422, i18n SK/EN, PHPUnit + Vitest.
EOF
)"
git tag v2.0.56
git push origin main
git push origin v2.0.56
```

---

## GitHub Release — copy-paste (2.0.56)

**Title:**

```
2.0.56 — Password confirmation (register + admin user create/edit)
```

**Body:**

```markdown
**Tag:** `v2.0.56` · **Target:** `main` · **Commit:** `0664ba3`

### Summary

Requires matching **password + passwordConfirm** on public registration and when admins create users or set a new password. Validates on FE (instant feedback) and BE (422).

### Added

- `ValidationRules::validatePasswordConfirmation()` — shared BE rule
- `RegisterModal` — confirm password field + i18n
- `UsersManager` — confirm field on create / password change on edit
- Tests: `AuthControllerTest`, `UserControllerTest`, `ValidationRulesTest`, `CoreHardeningTest`, Vitest

### Fixed

- **ISS-076** — PHPUnit kaskáda (21 failov) po chýbajúcom `passwordConfirm` v `CoreHardeningTest` + neobnovené `allowRegistration`

### Docs

- [ISSUES.md](docs/ISSUES.md) — ISS-076

- [ITERATION_5.md](docs/ITERATION_5.md) · [CORE_HARDENING.md](docs/architecture/CORE_HARDENING.md) §4
- [CHANGELOG.md](CHANGELOG.md) — 2.0.56

### Test plan

- [ ] Register with mismatched passwords → error toast, no account
- [ ] Register with matching strong passwords → success (or OTP flow)
- [ ] Admin → Users → create user — mismatch blocked client-side and via API
- [ ] Admin → edit user → new password requires matching confirm
```

---

## 2.0.55 — Wave 5e API barrel + CONTRIBUTING (It.17 MVP)

```bash
cd frontend
npm run type-check
npm run lint:api-barrel
npm test -- --run
cd ..
composer test && composer stan
```

**Po deployi:** nový endpoint → checklist v `docs/developer/CONTRIBUTING.md`; CI musí prejsť `lint:api-barrel`.

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.55 — Wave 5e CONTRIBUTING, API barrel lint, AppVersion bump.

CONTRIBUTING.md, lint-api-barrel.mjs + CI step, full api/index.ts exports; FINAL_BETA1 plan gitignored locally.
EOF
)"
git tag v2.0.55
git push origin main
git push origin v2.0.55
```

---

## GitHub Release — copy-paste (2.0.55)

**Title:**

```
2.0.55 — API barrel lint + CONTRIBUTING (Wave 5e / It.17 MVP)
```

**Body:**

```markdown
**Tag:** `v2.0.55` · **Target:** `main` · **Commit:** `d8c3437`

### Summary

After **2.0.54**: enforce **API↔FE law** for new work — full `api/index.ts` barrel, `npm run lint:api-barrel` in CI, contributor checklist.

### Added

- `docs/developer/CONTRIBUTING.md`
- `frontend/scripts/lint-api-barrel.mjs` + CI step

### Changed

- `frontend/src/api/index.ts` — 39 modules, 16 `api.*` clients
- `AppVersion` → 2.0.55

### Docs

- [ITERATION_17E.md](docs/ITERATION_17E.md) · [CONTRIBUTING.md](docs/developer/CONTRIBUTING.md)
```

---

## 2.0.54 — Wave 5d hook emitters + extension policy (It.15d)

```bash
npm run type-check          # → 0 errors
npm test -- --run           # → green
composer test               # → 833 passed, 15 skipped
composer stan               # → 0 errors
```

**Po deployi:**

1. Admin → **Extensions** → enable **hello-widget**
2. `GET /api/extensions/hello-widget/ping` → `{ "success": true, "message": "pong" }`
3. Uložiť stránku → hook `content.after_save` (referenčný plugin)

**CI hotfix:** ISS-075 — `PluginManagerTest` nepoužíva namespace `HelloWidget\Hooks` (kolízia s referenčným pluginom).

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.54 — Wave 5d hook emitters, hello-widget reference, extension policy.

HookCatalog, HookEmitter, Core content/extension hooks, ExtensionManifestValidator, EXTENSION_CODE_POLICY.md; ISS-075 PHPUnit namespace fix.
EOF
)"
git tag v2.0.54
git push origin main
git push origin v2.0.54
```

*(Po commite doplň hash do tabuľky a GitHub Release body.)*

---

## GitHub Release — copy-paste (2.0.54)

**Title:**

```
2.0.54 — Hook emitters + extension code policy (Wave 5d / It.15d)
```

**Body:**

```markdown
**Tag:** `v2.0.54` · **Target:** `main` · **Commit:** `2338fe9`

### Summary

After **2.0.53**: Core **emits** canonical hooks; extensions subscribe via `plugin.json`. Reference plugin **hello-widget**, full **Extension Code Policy** doc, manifest validation.

### Added

- `HookCatalog` + `HookEmitter` — content and extension lifecycle hooks
- `ExtensionManifestValidator` + `AppVersion`
- Reference plugin `hello-widget` + route `GET /api/extensions/hello-widget/ping`
- `docs/developer/EXTENSION_CODE_POLICY.md`

### Changed

- `ContentController`, `ContentScheduledPublishService`, `PluginManager` — hook emitters

### Fixed

- **ISS-075** — PHPUnit fatal duplicate `HelloWidget\Hooks` (`PluginManagerTest` → `ping-demo`)

### Docs

- [ITERATION_15D.md](docs/ITERATION_15D.md) · [EXTENSION_CODE_POLICY.md](docs/developer/EXTENSION_CODE_POLICY.md) · [ISSUES.md](docs/ISSUES.md) ISS-075
```

---

## 2.0.53 — It.59 scheduled publish (editor + cron)

```bash
npm run type-check          # → 0 errors
npm test -- --run           # → 228/228
./vendor/bin/phpunit        # → 826 passed, 15 skipped
./vendor/bin/phpstan analyse --level=8 backend/app backend/tests  # → 0 errors
```

**Po deployi (rebuild FE + reštart PHP + cron):**

1. **Editor stránky/článku** — pole „Publikovať o“ → uložiť → stav **Naplánované**
2. **Admin zoznam** — filter „Naplánované“ + stĺpec dátumu
3. **Cron** — `php backend/bin/console scheduler:run` každú minútu (job `content-scheduled-publish`)
4. Po `scheduledAt` sa obsah automaticky prepne na **Publikované** (do ~1 min)
5. Verejný web — scheduled obsah **nie je** viditeľný (404)

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.53 — It.59 scheduled publish (editor + cron job).

Editor datetime picker, status scheduled, ContentScheduledPublishService + content.scheduled_publish handler, admin list filter, PHPUnit/Vitest coverage.
EOF
)"
git tag v2.0.53
git push origin main
git push origin v2.0.53
```

*(Po commite doplň hash do tabuľky a GitHub Release body.)*

---

## GitHub Release — copy-paste (2.0.53)

**Title:**

```
2.0.53 — Scheduled publish (It.59): editor picker + cron job
```

**Body:**

```markdown
**Tag:** `v2.0.53` · **Target:** `main` · **Commit:** `aee1494`

### Summary

After **2.0.52**: **scheduled publish** for pages and articles — pick date/time in the editor, content stays hidden until cron publishes it automatically via It.29 job queue.

### Added

- **Editor** — “Publish at” datetime picker, status **Scheduled**, `scheduledAt` in front matter
- **`content.scheduled_publish`** job — runs every minute; `ContentScheduledPublishService` publishes due items
- **Admin lists** — “Scheduled” status filter + publish date column
- **API** — `status: scheduled` + `scheduledAt` validation; public API returns 404 for scheduled content
- Tests: PHPUnit (service + API) · Vitest (scheduling utils + i18n)

### Changed

- Blueprint page/article — `scheduled` status + `scheduledAt` field
- OTP-aware scheduled job — requires `publishApprovedAt` when publish OTP is enabled

### Tests

- PHPUnit: **826** passed (15 skipped) · Vitest: **228** · PHPStan L8: **0 errors**

### Docs

- [CHANGELOG.md](CHANGELOG.md) — 2.0.53
- [docs/ITERATION_59.md](docs/ITERATION_59.md)
```

---

## 2.0.52 — branding, ACL v nastaveniach, CI (ISS-072–074)

```bash
npm run type-check          # → 0 errors
npm test -- --run           # → 226/226
./vendor/bin/phpunit        # → 820 passed, 15 skipped
./vendor/bin/phpstan analyse --level=8 backend/app backend/tests  # → 0 errors
```

**Po deployi (rebuild FE + reštart PHP):**

1. **Nastavenia → Stránka → Logo a favicon** — logo + favicon → Navbar, admin sidebar, favicon v prehliadači
2. **SUPER_ADMIN → Nastavenia → Bezpečnosť → Oprávnenia rolí** — RBAC + Path ACL; uložiť → `data/security/acl.json`
3. **ADMIN → Security audit** — `/api/admin/security/audit` → **200**
4. **`/security/acl`** — redirect na settings `accessControl`
5. Regresia: login, editácia obsahu, media

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.52 — branding, ACL in settings, CI fixes (ISS-072–074).

Site logo/favicon settings, SUPER_ADMIN accessControl (RBAC + Path ACL), security audit route split, PHPUnit login lockout isolation, PHPStan L8 clean.
EOF
)"
git tag v2.0.52
git push origin main
git push origin v2.0.52
```

*(Po commite doplň hash do tabuľky a GitHub Release body.)*

---

## GitHub Release — copy-paste (2.0.52)

**Title:**

```
2.0.52 — Site branding, ACL in settings, and CI fixes (ISS-072–074)
```

**Body:**

```markdown
**Tag:** `v2.0.52` · **Target:** `main` · **Commit:** `<HASH>`

### Summary

After **2.0.51**: configurable **logo & favicon** in Settings, **role permissions + Path ACL** in Settings (SUPER_ADMIN only), legacy `/security/acl` redirect, security **audit** routes restored for ADMIN, PHPUnit/PHPStan CI fixes.

### Added

- **Settings → Site → Logo & favicon** — `branding.logoUrl`, `branding.faviconUrl`, media picker, public API, `SiteLogo` + favicon head
- **Settings → Security → Role permissions** — `accessControl`: RBAC for ADMIN/EDITOR/USER + Path ACL; sync to `acl.json`
- Docs: [BRANDING.md](docs/user/BRANDING.md), [ACCESS_CONTROL.md](docs/user/ACCESS_CONTROL.md)
- Backlog: It.59 scheduled publish, It.60 editor components, It.61 footer newsletter

### Changed

- Path ACL + role mapping moved from `/security/acl` to **Settings**; sidebar ACL item removed
- `GET/PUT /api/admin/security/acl` — **SUPER_ADMIN** only
- `GET /api/admin/security/audit` (+ export) — **ADMIN** + **SUPER_ADMIN**

### Fixed

- **ISS-072** — security audit 403 for ADMIN
- **ISS-073** — flaky PHPUnit login 429 (HTTP TestCase lockout reset)
- **ISS-074** — PHPStan L8 after accessControl/branding

### Tests

- PHPUnit: **820** passed (15 skipped) · Vitest: **226** · PHPStan L8: **0 errors**

### Docs

- [CHANGELOG.md](CHANGELOG.md) — 2.0.52
- [docs/ISSUES.md](docs/ISSUES.md) — ISS-072–074
- [docs/user/BRANDING.md](docs/user/BRANDING.md)
- [docs/user/ACCESS_CONTROL.md](docs/user/ACCESS_CONTROL.md)
```

---

## 2.0.51 — ops hotfix + maintenance + logs (ISS-063–071)

```bash
npm run type-check          # → 0 errors
npm test -- --run           # → 226/226
./vendor/bin/phpunit        # → 816 passed, 15 skipped
./vendor/bin/phpstan analyse --level=8  # → 0 errors
```

**Po deployi (rebuild FE + reštart PHP):**

1. **`/pages/home`** — bez `RangeError`, história verzií OK
2. **Nastavenia → Všeobecné** — timezone + DST; logy s aktuálnym časom
3. **Nastavenia → Režim údržby** — Coming Soon / Under Maintenance (iba jeden naraz)
4. **Verejný web** — maintenance stránky, prihlásenie OK, registrácia vypnutá
5. **Admin → Logy** — bulk, delete-all, stránkovanie, ručný page size
6. **Header** — „Vymazať cache“

### Commit

```bash
git add -A
git commit -m "$(cat <<'EOF'
release: 2.0.51 — dates, timezone, maintenance modes, logs bulk (ISS-063–071).

Safe dates, site timezone/DST, dual maintenance pages, log admin bulk/pagination, cache header purge, PHPStan L8 clean.
EOF
)"
git tag v2.0.51
git push origin main
git push origin v2.0.51
```

---

## GitHub Release — copy-paste (2.0.51)

**Title:**

```
2.0.51 — Timezone, maintenance modes, and logs admin UX (ISS-063–071)
```

**Body:**

```markdown
**Tag:** `v2.0.51` · **Target:** `main` · **Commit:** `d9b7171`

### Summary

Hotfix after **2.0.50**: safe date formatting, correct log timestamps (timezone + DST), dual public maintenance modes (Coming Soon / Under Maintenance), log admin bulk actions and pagination, cache purge in admin header.

### Added

- **Timezone + DST** — `TimezoneSelect`, `AppTimezone`, bootstrap timezone
- **Dual maintenance** — editable templates, newsletter, maintenance contact form; login link; registration blocked
- **Logs admin** — bulk archive/delete, delete all, pagination with manual page size (1–500)
- **Admin header** — quick cache purge (`useCachePurge`)

### Fixed

- ISS-063–070 — invalid dates crash, CI `DEFAULT_LOCALE`, log timezone offset, PHPUnit cron/locale, code policy log noise

### Tests

- PHPUnit: **816** passed · Vitest: **226** · PHPStan L8: **0 errors**

### Docs

- [CHANGELOG.md](CHANGELOG.md) — 2.0.51
- [docs/ISSUES.md](docs/ISSUES.md) — ISS-063–071
- [docs/user/LOGGING.md](docs/user/LOGGING.md)
```

---

> **Poznámka:** Ďalší tag **`v2.0.53`**. Plánované It.59–61 — [ITERATION_BACKLOG.md](../ITERATION_BACKLOG.md).

---

## 2.0.50 — pred release kontrola

```bash
./scripts/iteration-gate.sh
# Vitest: npm test -- --run  → 217/217
```

**Po deployi:**

1. **Nastavenia → Všeobecné → Jazyk = English**
2. **Verejný web** (`/`, `/blog`, `/login`) — UI po anglicky (navbar, footer, blog list, auth modals)
3. **Jazyk = Slovenčina** — SK copy späť (Domov, Prihlásiť, Magazín & Novinky, …)
4. **Kontakt** — formulár labely sledujú locale (`Predmet` / `Subject`)

---

## GitHub Release — copy-paste (2.0.50)

**Title:**

```
2.0.50 — Public site i18n (ISS-062 / wave 5c)
```

**Tag:** `v2.0.50` · **Target:** `main` · **Commit:** `67d77bb`

**Body:**

```markdown
## Summary

Public site UI (blog, nav, footer, contact, search, auth modals) follows `general.language` — same as admin.

## Highlights

- New `public/{sk,en}` i18n module (~120 keys)
- Migrated 18+ public components to `useI18n().t('public.*')`
- Locale-aware dates (`contentDates`) and reading time labels
- Localized password policy hints/validation on register & reset flows

## Verify

1. Settings → General → Language = English
2. Open `/`, `/blog`, `/login` — English chrome
3. Switch back to SK — Slovak labels return

## Docs

- [CHANGELOG.md](CHANGELOG.md) — 2.0.50
- [ITERATION_18.md](docs/ITERATION_18.md) — wave 5c
- [ISSUES.md](docs/ISSUES.md) — ISS-062
```

---

## 2.0.49 — pred release kontrola

```bash
./scripts/iteration-gate.sh
# Vitest: npm test -- --run  → 210/210
```

**Po deployi:**

1. **Nastavenia → Všeobecné → Jazyk = English**
2. **Dashboard → Prehľad aktivít** — audit správy po anglicky (nie „Maxxim upravil článok…“)
3. **`/audit`** — content/user trail zobrazuje `display_message` z API v EN
4. **Legacy logy** — staré SK `context.summary` sa pri read preformátujú podľa locale

**Opravy:** ISS-061 · [ISSUES.md](../ISSUES.md)

---

## GitHub Release — copy-paste (2.0.49)

**Title:**

```
2.0.49 — Audit messages follow admin locale (ISS-061 / wave 5b)
```

**Tag:** `v2.0.49` · **Target:** `main` · **Commit:** `1ac58cf`

**Body:**

```markdown
## Summary

Audit trail and dashboard activity messages now follow **`general.language`** (SK/EN). Fixes mixed Slovak audit text when admin UI is set to English.

## Added

- `backend/lang/{sk,en}/audit.php` — audit message catalog
- `frontend/src/i18n/modules/audit/{sk,en}.ts` — FE fallback labels

## Changed

- `AuditMessageFormatter` uses `Lang::get()`; `formatFromLog()` re-formats from context (ignores persisted SK summary when structured data exists)
- `AuditTrailService` enriches all read paths with `display_message`; CSV export localized
- `formatAuditEvent.ts` — thin client preferring API `display_message`

## Fixed

- **ISS-061:** EN admin locale showed Slovak audit messages in dashboard activity and audit trail

## Test plan

- [ ] `./scripts/iteration-gate.sh` green (PHPStan L8 + Vitest 210/210)
- [ ] Settings → English → edit content → dashboard activity shows EN message
- [ ] `/audit` content trail — EN labels for legacy events

## Docs

- [CHANGELOG.md](CHANGELOG.md) — 2.0.49
- [ISSUES.md](docs/ISSUES.md) — ISS-061
- [ITERATION_18.md](docs/ITERATION_18.md)
```

---

## 2.0.48 — pred release kontrola

```bash
./scripts/iteration-gate.sh
# Overiť APP_KEY na serveri (EncryptionService) — po nastavení sa nové tajomstvá šifrujú
```

**Po deployi:**

1. **Encryption (ISS-052)** — nový SMTP/2FA secret sa ukladá encrypted; starý plaintext stále čitateľný
2. **CSRF (ISS-012)** — mutujúce API vyžadujú `X-CSRF-TOKEN`; login/register exempt
3. **Path ACL (ISS-055)** — ak `acl.json enabled: true`, deny na content/media/drafts
4. **WAF body (ISS-056)** — POST JSON sken; editor routes exempt
5. **OTP rate limit (ISS-058)** — resend neobnoví verify pokusy

**Opravy:** ISS-012 · ISS-052 … ISS-058 · [ISSUES.md](../ISSUES.md)

---

## GitHub Release — copy-paste (2.0.48)

**Title:**

```
2.0.48 — Security audit hardening (encryption, CSRF, ACL, WAF body, ISS-052–058)
```

**Tag:** `v2.0.48` · **Target:** `main`

**Body:**

```markdown
## Summary

Formal release for **security audit hardening** already on `main`: at-rest encryption, CSRF enforcement, log sanitization, SSRF guard, Path ACL wiring, WAF POST body scan, UserRepository index, OTP rate limits.

## Security

| ID | Feature |
|----|---------|
| ISS-052 | `EncryptionService` — TOTP seed + settings secrets at rest |
| ISS-053 | `LogSanitizer` — control char collapse in logs + CSV |
| ISS-054 | `OutboundUrlGuard` — SSRF protection on outbound URLs |
| ISS-055 | `ContentPathAclGuard` — ACL on content/drafts/media |
| ISS-056 | WAF POST/JSON body scanning (editor exempt) |
| ISS-057 | `UserIndexService` — O(1) auth lookups |
| ISS-058 | OTP dedicated rate limits + resend hardening |
| ISS-012 | CSRF middleware + FE token bootstrap |

## Deploy notes

- Set real **`APP_KEY`** before relying on encryption; never rotate after secrets encrypted.
- Path ACL is opt-in (`acl.json` → `enabled: false` by default).

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] Login → mutating request without CSRF → 403; with token → OK
- [ ] Save settings password field → encrypted on disk
- [ ] OTP resend 4× → blocked; verify attempts not reset

## Docs

- [CHANGELOG.md](CHANGELOG.md) — 2.0.48
- [ISSUES.md](docs/ISSUES.md) — ISS-012, ISS-052 … ISS-058
```

---

## 2.0.47 — pred release kontrola

```bash
./scripts/iteration-gate.sh
# Vitest: npm test -- --run  → 210/210
```

**Commity na `main`:**

| Commit | Obsah |
|--------|--------|
| `f0a885c` | It.18f — ops/platform/editor i18n, Beta gate, ISS-060 (settings EN workflows) |
| `390b392` | ISS-059 — `renderWithProviders`, Vitest CI green |

**Po deployi:**

1. **It.18f i18n** — `/comments`, `/messages`, `/backups`, `/trash`, `/logs`, editor, firewall, scheduler, command palette v SK/EN
2. **Settings EN** — Nastavenia → Workflows / OTP polia po anglicky (ISS-060)
3. **CI / testy** — Vitest 210/210; komponenty s `useI18n()` obalené v `TestI18nProvider`
4. **Dashboard panely** — Health, Locks, Conflicts, Activity — preložené podľa `general.language`
5. **Media metadata modal** — SK/EN labely (`Upraviť metadáta`, `Titulok`, …)

**Opravy:** ISS-059 · ISS-060 · [ISSUES.md](../ISSUES.md)

---

## GitHub Release — copy-paste (2.0.47)

**Title:**

```
2.0.47 — It.18f admin i18n Beta gate + Vitest I18nProvider fix (ISS-059–060)
```

**Tag:** `v2.0.47` · **Target:** `main` · **Commit:** `e1fdead`

**Body:**

```markdown
## Summary

Completes **It.18f** (Beta gate): ops modules, platform UI, content editor, and dashboard panels migrated to `useI18n()`. Fixes **ISS-059** (Vitest without `I18nProvider`) and **ISS-060** (English settings catalog had Slovak OTP copy).

## It.18f — Admin i18n (Beta gate)

- New catalogs: `comments`, `messages`, `backups`, `trash`, `logs`, **platform**, **editor**
- ~40 components → `useI18n()` (firewall, scheduler, extensions, editor shell, SEO, media modals, dashboard panels, …)
- Catalog tests: `ops18f.test.ts`, `platform.test.ts`, `editor.test.ts`

## Fixed

| ID | Fix |
|----|-----|
| ISS-059 | Vitest — `renderWithProviders` + `TestI18nProvider`; 6 suites green |
| ISS-060 | `settings/en.ts` workflows — removed SK copy-paste in EN catalog |
| — | `summarizeBulkResult(t, …)` — bulk toasts via i18n |

## Test plan

- [ ] `./scripts/iteration-gate.sh` green (Vitest **210/210**)
- [ ] Admin SK/EN → comments, messages, backups, trash, logs, editor toolbars
- [ ] Settings → English → Workflows OTP labels in English
- [ ] `/dashboard` — Health, Locks, Activity panels localized
- [ ] Media → edit metadata modal — labels match locale

## Docs

- [ITERATION_18.md](docs/ITERATION_18.md) — It.18f
- [ISSUES.md](docs/ISSUES.md) — ISS-059, ISS-060
- [CHANGELOG.md](CHANGELOG.md) — 2.0.47
```

---

## 2.0.46 — pred release kontrola

```bash
./scripts/iteration-gate.sh
# alebo lokálny alltests skript → log bez exit 1 na PHPUnit/PHPStan/Vitest
```

**Po deployi:**

1. **It.18e i18n** — `/media`, `/navigation`, `/dashboard` v SK/EN podľa `general.language`
2. **Analytika** — `/analytics` KPI, graf, taby Stránky/Zdroje/Zariadenia/Geografia
3. **Dashboard** — rýchle odkazy + disková štruktúra (KB/MB celkového obsahu)
4. **Audit aktivita** — SK správy s titulkom článku/stránky (nie len slug)
5. **Logy** — `/logs` čitateľné `display_message`; dnešný denný súbor OK
6. **Login pozadie** — Nastavenia → Prihlásenie: médiá + upload z disku
7. **SEO indexovanie** — Nastavenia → SEO → vypnúť → `/robots.txt` = `Disallow: /`
8. **Sidebar** — Prehľad + Analytika pod user blokom

**Opravy:** ISS-046 … ISS-050 · [ISSUES.md](../ISSUES.md)

---

## GitHub Release — copy-paste (2.0.46)

**Title:**

```
2.0.46 — It.18e i18n, analytics UI, dashboard disk, audit/logs fixes (ISS-046–050)
```

**Tag:** `v2.0.46` · **Target:** `main` · **Commit:** `637fef4`

**Body:**

```markdown
## Summary

Ships **It.18e** (media, navigation, dashboard i18n), **It.20** (analytics page, dashboard quick links + disk size, robots indexing toggle), and **audit/logs hotfixes** ISS-046–050. Adds login background picker with media library upload.

## It.18e — Admin i18n

- `src/i18n/modules/media/*` → **MediaManager**
- `src/i18n/modules/navigation/*` → **NavigationManager**
- `src/i18n/modules/dashboard/*` → **DashboardView**
- Vitest: `TestI18nProvider` in navigation tests; SK labels in media tests

## It.20 — Analytics & dashboard

- **`/analytics`** — KPI cards (page views, unique visitors, avg time, bounce rate)
- Period filter **7 / 14 / 30 days**; tabs: Overview, Pages, Sources, Devices, Geography
- Visit records enriched with **device, browser, GeoIP country**
- Dashboard **quick links** (Pages, Articles, Users, Settings)
- **Disk structure panel** — counts + total flat-file size (`ContentStorageStatsService`)
- **`seo.allowSearchIndexing`** — toggles `robots.txt` Allow/Disallow + global meta robots

## Audit & logs (ISS-046–050)

| ID | Fix |
|----|-----|
| ISS-046 | Audit category `audit_*` no longer overwritten as `app` |
| ISS-047 | Dashboard activity panel shows legacy + new audit events |
| ISS-048 | SK audit messages with content title (`AuditMessageFormatter`) |
| ISS-049 | Empty daily log file no longer treated as corrupt |
| ISS-050 | Logs UI reads correct path + lowercase severity stats |
| — | Login background picker (media + local upload) |

## Other fixes

- Login background picker i18n key path (`settings.fields.login.backgroundPicker.*`)
- `RobotsTxtGeneratorTest` — mock `seo` group for `allowSearchIndexing`
- PHPStan annotations on log formatters

## Test plan

- [ ] `./scripts/iteration-gate.sh` green (PHPUnit 0 errors, PHPStan 0, Vitest 203+)
- [ ] Switch admin language SK/EN → media, navigation, dashboard labels change
- [ ] `/analytics` — KPI, chart, all tabs load
- [ ] `/dashboard` — quick links + disk panel with total size
- [ ] Edit content → dashboard activity shows SK title
- [ ] `/logs` readable messages; `/audit` formatted events
- [ ] Settings → login → upload background → visible on `/login`
- [ ] Settings → SEO → disable indexing → `/robots.txt` has `Disallow: /`

## Docs

- [ITERATION_18.md](docs/ITERATION_18.md) — It.18e
- [ITERATION_19.md](docs/ITERATION_19.md) — auth + audit
- [ITERATION_20.md](docs/ITERATION_20.md) — analytics + disk + robots
- [ISSUES.md](docs/ISSUES.md) — ISS-046 … ISS-050
- [CHANGELOG.md](CHANGELOG.md) — 2.0.46
```

---

## 2.0.45 — pred release kontrola

```bash
./scripts/iteration-gate.sh
# alebo lokálny alltests skript → log bez exit 1 na PHPUnit/PHPStan
```

**Po deployi:**

1. **Dashboard** — po prihlásení landing `/dashboard`; sidebar: Dashboard samostatne navrchu (mimo kategórií)
2. **Prihlásenie** — veľký dvojpanel (info vľavo, formulár vpravo); vlastný nadpis/popis/pozadie z **Nastavenia → Web → Prihlásenie a registrácia**
3. **Registrácia** — formulár vľavo, info panel vpravo (animácia); live checklist politiky hesla
4. **TOTP krok** — 6 samostatných polí pri 2FA prihlásení
5. **Politika hesla** — **Nastavenia → Bezpečnosť → Bezpečnosť** (min/max dĺžka, A–Z, a–z, 0–9, špeciálny znak) → overiť na `/register` a reset hesla
6. **Preklady / používatelia** — nová jazyková mutácia (`/translations`); avatar používateľa (`/users`)
7. **Upload / obsah** — `uploadSecurity` a `contentSecurity` nastavenia reálne aplikované pri uploade a renderi HTML

**Hotfixy v tomto release (ISS-044, ISS-045):** pozri [ISSUES.md](../ISSUES.md).

---

## GitHub Release — copy-paste (2.0.45)

**Title:**

```
2.0.45 — It.19b–19c security runtime, auth UX, password policy, avatars & locales
```

**Tag:** `v2.0.45` · **Target:** `main` · **Commit:** `f3ed5bc`

**Body:**

```markdown
## Summary

Completes Iteration 19b (security settings wired to runtime), 19c (custom locales + user avatars), and a major auth/login UX refresh. Dashboard is a standalone sidebar entry and default post-login route. Password policy is configurable in admin settings.

Includes hotfixes ISS-044 (services.php parse error) and ISS-045 (LocaleScaffoldService `$projectRoot` / PHPStan + PHPUnit deprecations).

## Highlights

- **Security runtime (It.19b):** `UploadSecurityValidator` → media upload; `ContentSecuritySanitizer` → HTML render; Monaco policy markers; `AdminHintCard`
- **Auth UX:** `AuthShell` — large dual-panel login/register; custom title, description, background image; animated layout swap on register; styled TOTP input
- **Password policy (admin):** min/max length, uppercase/lowercase/digits/special chars — `SettingsBackedPasswordPolicy` + `/api/validation/rules/password`
- **Login settings:** new `login` schema group (page title, description, background URL, info bullets)
- **Dashboard nav:** primary item outside categories; `ADMIN_DEFAULT_ROUTE`
- **Locales (It.19c):** `SupportedLocalesRegistry`, scaffold new locale, Translation editor UI
- **Users:** avatar upload/remove API, `UserAvatarPicker`, SuperAdmin role guards
- **It.18e (partial):** `users` i18n module + `UsersManager` on `useI18n()`

## Hotfixes

| ID | Symptóm | Oprava |
|----|---------|--------|
| ISS-044 | PHP parse error `services.php:301` — API 500 | Odstránený orphan riadok `->constructor(...)` po `ValidationController` closure |
| ISS-045 | PHPUnit exit 1 + PHPStan 7× `$projectRoot` undefined | Deklarovaná `private string $projectRoot` v `LocaleScaffoldService` |

## Test plan

- [ ] `./scripts/iteration-gate.sh` green (PHPUnit 0 failed, PHPStan 0 errors)
- [ ] Login → dashboard; sidebar Dashboard top-level (not under Workspace)
- [ ] Settings → login group → custom background/title visible on `/login`
- [ ] Settings → security password rules → reflected on register form hints
- [ ] 2FA login → TOTP 6-box UI
- [ ] Translation editor → create locale; Users → upload avatar
- [ ] Upload blocked file types respect `uploadSecurity` settings

## Docs

- [ITERATION_19.md](docs/ITERATION_19.md)
- [ISSUES.md](docs/ISSUES.md) — ISS-044, ISS-045
```

---

## 2.0.44 — pred release kontrola

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Admin — prepnúť jazyk v nastaveniach (SK/EN) → sidebar, header, zoznamy stránok sa preložia
2. **Preklady** (`/translations`) — otvoriť `frontend/src/i18n/modules/admin/sk.ts`, uložiť → staging + validácia
3. Zámerná syntaktická chyba v preklade → `.err` kópia v `storage/translations/rejected/`, toast s prvou chybou
4. **Nastavenia** — ľavé menu kategórií: System / Site / Media / Security; URL `?category=security&group=contentSecurity`
5. Sidebar — zbalenie panelu (header toggle), sekcie Workspace / Inbox / Platform / …
6. `./vendor/bin/phpunit` — bez chýb DI (HookManager import)

---

## GitHub Release — copy-paste (2.0.44)

**Title:**

```
2.0.44 — It.18 i18n + translation editor, It.19a admin UX
```

**Tag:** `v2.0.44` · **Target:** `main` · **Commit:** `199877a`

**Body:**

```markdown
## Summary

Iteration 18 migrates admin UI to modular i18n (`useI18n()`) and adds a translation editor for backend lang files and frontend i18n modules. Iteration 19a delivers grouped admin navigation, settings categories, translation save policy (staging + rejected `.err` copies), and security settings schema groups.

Includes HookManager DI hotfix (146 PHPUnit errors) and Vitest `TestI18nProvider` harness fix.

## Highlights

- **i18n modules:** admin, list, content, settings, translations
- **Translation editor:** `/translations` + `/api/admin/translations/*` (Admin + 2FA)
- **Save policy:** staging → validate → promote; sequential policy toasts
- **Settings UX:** System / Site / Media / Security category menu
- **Schema:** `contentSecurity`, `uploadSecurity` groups (UI; runtime wiring in It.19b)
- **Admin nav:** 6 collapsible sidebar sections + header collapse toggle

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] Switch admin language SK ↔ EN — sidebar and lists update
- [ ] Translation editor — valid save promotes; invalid save leaves original + `.err` copy
- [ ] Settings categories and URL `?category=&group=` deep links
- [ ] Sidebar collapse persists after reload

## Docs

- [ITERATION_18.md](docs/ITERATION_18.md)
- [ITERATION_19.md](docs/ITERATION_19.md)
```

---

## 2.0.43 — pred release kontrola

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Admin editor — prepnúť na **WYSIWYG**, uložiť stránku → `contentFormat: tiptap_json` v API odpovedi
2. Verejný náhľad / frontend — obsah renderovaný z `html` cache
3. Vložiť obrázok (paste / drop / 🖼️) → súbor v DAM, URL v dokumente
4. **Minimal** profil — Tiptap `image` node pri uložení → 400
5. Login na **localhost:3025** — jeden pokus, bez dvojitého logu (ISS-042)

---

## GitHub Release — copy-paste (2.0.43)

**Title:**

```
2.0.43 — It.55: Tiptap JSON storage + editor image upload
```

**Tag:** `v2.0.43` · **Target:** `main` · **Commit:** `4367d19` *(git commit message: „Release 2.0.42“)*

**Body:**

```markdown
## Summary

Iteration 55 persists WYSIWYG content as structured Tiptap JSON, renders sanitized HTML on the backend, and wires image paste/drop/upload into the existing media DAM. Includes ISS-042 login session retry fix.

## Highlights

- **`contentFormat: tiptap_json`** — save/load round-trip with cached `html`
- **`TiptapHtmlRenderer` + `ContentBodyRenderer`** — public HTML from JSON
- **Profile-aware validation** — Tiptap node walk (It.54 profiles)
- **Editor upload** — paste, drop, file picker → `/api/media/upload`
- **ISS-042** — `probeSessionWithRetry` after login

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] WYSIWYG save → reload → same content
- [ ] Image upload from editor → visible on public page
- [ ] Markdown-only articles unchanged
- [ ] Single login on localhost:3025

## Docs

- [ITERATION_55.md](docs/ITERATION_55.md)
```

---

## 2.0.42 — pred release kontrola

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Admin editor stránky/článku — prepínač **Profil editora** (Company / Blog / Minimal / Developer)
2. **Minimal** — toolbar bez obrázkov; vloženie `![img]()` pri uložení → 400
3. **Developer** — plný toolbar vrátane tabuliek a code blockov
4. Prepínanie profilu mení toolbar **bez** reload stránky

---

## GitHub Release — copy-paste (2.0.42)

**Title:**

```
2.0.42 — It.54: modular editor profiles (MD + WYSIWYG)
```

**Tag:** `v2.0.42` · **Target:** `main` · **Commit:** `8526c19` *(git commit message: „Release 2.0.41“)*

**Body:**

```markdown
## Summary

Iteration 54 adds editor profiles that control which Markdown/WYSIWYG toolbar actions and Tiptap extensions are available per page or article.

## Highlights

- **Profiles:** company, blog, minimal, developer
- **Settings:** default profile per pages/articles
- **Validation:** backend rejects disallowed blocks on save
- **UX:** profile picker in content editor; paste guard with toast

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] Switch profiles in page editor — toolbar updates instantly
- [ ] Save image markdown on minimal profile → API 400

## Docs

- [ITERATION_54.md](docs/ITERATION_54.md)
```

---

## 2.0.41 — pred release kontrola (legacy commit label)

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Admin editor stránky/článku — prepínač **Profil editora** (Company / Blog / Minimal / Developer)
2. **Minimal** — toolbar bez obrázkov; vloženie `![img]()` pri uložení → 400
3. **Developer** — plný toolbar vrátane tabuliek a code blockov
4. Prepínanie profilu mení toolbar **bez** reload stránky

---

## GitHub Release — copy-paste (2.0.41)

**Title:**

```
2.0.41 — It.54: modular editor profiles (MD + WYSIWYG)
```

**Tag:** `v2.0.41` · **Target:** `main`

**Body:**

```markdown
## Summary

Iteration 54 adds editor profiles that control which Markdown/WYSIWYG toolbar actions and Tiptap extensions are available per page or article.

## Highlights

- **Profiles:** company, blog, minimal, developer
- **Settings:** default profile per pages/articles
- **Validation:** backend rejects disallowed blocks on save
- **UX:** profile picker in content editor; paste guard with toast

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] Switch profiles in page editor — toolbar updates instantly
- [ ] Save image markdown on minimal profile → API 400

## Docs

- [ITERATION_54.md](docs/ITERATION_54.md)
```

---

## 2.0.40 — pred release kontrola

```bash
cd frontend && npm run type-check
./scripts/iteration-gate.sh
```

**Po deployi:**

1. CI **frontend → type-check** zelený (žiadny TS6133 na `PagesManager.tsx`)
2. Admin `/pages` — zoznam, filtre, mutácie stále fungujú (cache invalidácia cez React Query)

---

## GitHub Release — copy-paste (2.0.40)

**Title:**

```
2.0.40 — CI hotfix: unused refetch in PagesManager
```

**Tag:** `v2.0.40` · **Target:** `main`

**Body:**

```markdown
## Summary

Hotfix for CI type-check failure introduced in 2.0.39 (It.53 React Query migration).

## Fixed

- **TS6133** — removed unused `refetch` from `useAdminListQuery` in `PagesManager.tsx`
- List mutations still refresh data via `queryClient.invalidateQueries()`

## Test plan

- [ ] `cd frontend && npm run type-check` — exit 0
- [ ] `./scripts/iteration-gate.sh` green
- [ ] Admin `/pages` — create/edit/delete still updates the list

## Docs

- [ISSUES.md — ISS-041](../ISSUES.md#iss-041--frontend-type-check-nepoužitý-refetch-v-pagesmanager-ci)
```

---

## 2.0.39 — pred release kontrola

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Admin `/dashboard` → `/pages` → `/media` — druhá návšteva bez dlhého spinneru (cache)
2. Scroll pri prepnutí route skočí hore
3. Editor → história verzií → obnoviť — **bez** full page reload
4. Verejný článok → login link — SPA navigácia na `/login`

---

## GitHub Release — copy-paste (2.0.39)

**Title:**

```
2.0.39 — It.53: smooth SPA reload & admin navigation
```

**Tag:** `v2.0.39` · **Target:** `main`

**Body:**

```markdown
## Summary

Iteration 53 removes admin navigation jank: React Query stale-while-revalidate, scroll restoration, skeleton loaders, and the last editor hard reload.

## Highlights

- **React Query:** dashboard, content lists, extensions, sidebar counts
- **UX:** `AdminPageSkeleton`, `AdminListSkeleton`, scroll reset on route change
- **Fix:** version restore in editor refetches content (no `location.reload`)
- **Router:** `v7_startTransition`, public login via `<Link>`

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] `/pages` ↔ `/media` ↔ `/articles` feels instant on revisit
- [ ] Version restore in editor without full reload

## Docs

- [ITERATION_53.md](docs/ITERATION_53.md)
```

---

## 2.0.38 — pred release kontrola

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Admin **Rozšírenia** → `/extensions` — zoznam je prázdny alebo zobrazuje nainštalované doplnky
2. Import ZIP s `plugin.json` → 201, položka v zozname (disabled)
3. Zapnutie doplnku → `PUT …/enable` → 200; po reštarte API načíta hooks + routes
4. Network: `GET /api/admin/extensions` → `{ extensions: [...] }`

---

## 2.0.37 — pred release kontrola

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Verejný **`/blog`** → Network: `GET /api/articles?page=1&per_page=6` (nie legacy full list)
2. **`/blog?page=2&tag=…&sort=oldest`** — meta obsahuje `total`, `tags`, `total_published`
3. Detail článku **`/blog/{slug}`** — `GET /api/articles/{slug}`; prev/next funguje
4. Admin zoznam článkov — paginácia stále OK (`?page=1&per_page=20`)

---

## 2.0.34 — pred release kontrola

```bash
./scripts/iteration-gate.sh
```

**Po deployi:**

1. Admin **Prehľad** → riadok KPI: neprečítané správy, médiá, voľné miesto
2. Klik **Neprečítané správy** → `/messages`
3. Panel **Prehľad aktivít** → „Celý audit trail →“
4. Network: `GET /api/admin/dashboard/overview` obsahuje `counts` a `storage`

---

---

## GitHub Release — copy-paste (2.0.38)

**Title:**

```
2.0.38 — It.15: external plugins & extension runtime
```

**Tag:** `v2.0.38` · **Target:** `main`

**Body:**

```markdown
## Summary

Iteration 15 delivers the **external plugin system** outside Core: ZIP import with code policy, flat-file registry, HookManager integration, enabled extension routes, and admin UI at `/extensions`.

## Highlights

- **Backend:** `PluginManager`, `PluginImporter`, `PluginPolicyScanner`, `data/plugins.json`
- **API:** `GET/POST /api/admin/extensions`, enable/disable/uninstall
- **Security:** every imported file passes `CodePolicyEngine` (422 on violation)
- **Bootstrap:** enabled plugins register hooks + load `Http/Routes/extensions/{id}.php`
- **Frontend:** `ExtensionsManager`, `extensionsApi`, dynamic `extensions/loader.ts`
- **Fix:** `FileHelper::read()` guard (ISS-039)

## Test plan

- [ ] `./scripts/iteration-gate.sh` green
- [ ] Admin `/extensions` — list, import ZIP, enable/disable
- [ ] Invalid ZIP (eval in PHP) → 422 policy errors
- [ ] After enable + PHP restart, extension routes respond

## Docs

- [ITERATION_15.md](docs/ITERATION_15.md)
- [PLUGINS.md](docs/architecture/PLUGINS.md)
- [API.md](docs/architecture/API.md) — Extensions section
```

---

## GitHub Release — copy-paste (2.0.37)

**Title:**

```
2.0.37 — It.44d: content API filters and server-side public blog
```

**Tag:** `v2.0.37` · **Target:** `main`

**Body:**

```markdown
## Summary

Release **2.0.37** completes **Iteration 44 (backend)**: index filters for tag, author, and date range, plus server-side paginated public blog fetch.

Detail: [docs/ITERATION_44.md](docs/ITERATION_44.md)

## Added

- `GET /api/articles?page=&tag=&sort=` with `filter[author]`, `date_from` / `date_to`
- Response meta: `tags[]`, `total_published`
- Public blog list/detail loaded from API (no full article bootstrap)

## Test plan

- [ ] `/blog?page=2&tag=…&sort=oldest` — Network shows paginated `/api/articles`
- [ ] Admin articles list can use `filter[tag]` when wired
- [ ] `./scripts/iteration-gate.sh` green

## Full changelog

[CHANGELOG.md#2037--2026-07-20](CHANGELOG.md#2037--2026-07-20)
```

---

## GitHub Release — copy-paste (2.0.36)

**Title:**

```
2.0.36 — It.52 complete: company info and Google Maps on contact page
```

**Tag:** `v2.0.36` · **Target:** `main`

**Body:**

```markdown
## Summary

Release **2.0.36** completes **Iteration 52**: editable company details in Settings, public contact page company panel, and safe Google Maps embed.

Detail: [docs/ITERATION_52.md](docs/ITERATION_52.md)

## Added

- Settings → **Firemné údaje** (`company.*`)
- Contact page: company info block + optional map iframe
- `isSafeMapEmbedUrl` whitelist for Google Maps embed only

## Test plan

- [ ] Settings → Firemné údaje → fill IČO, address, map URL
- [ ] Public `/contact` shows company panel + map
- [ ] `./scripts/iteration-gate.sh` green

## Full changelog

[CHANGELOG.md#2036--2026-07-20](CHANGELOG.md#2036--2026-07-20)
```

---

## GitHub Release — copy-paste (2.0.35)

**Title:**

```
2.0.35 — It.52b contact form subjects (tests & API contract)
```

**Tag:** `v2.0.35` · **Target:** `main`

**Body:**

```markdown
## Summary

Release **2.0.35** completes **Iteration 52b** verification: configurable contact subjects (`contact.subjects`, `contact.allowCustomSubject`) with FE/BE tests and public settings contract.

Feature shipped in 2.0.32; this release adds regression tests.

Detail: [docs/ITERATION_52.md](docs/ITERATION_52.md)

## Added

- `contactSubjects` + `ContactForm` Vitest coverage
- PHPUnit public settings shape asserts `contact` block

## Test plan

- [ ] `/contact` page → subject dropdown from Settings → Kontakt
- [ ] Disable custom subject → „Vlastný predmet“ hidden
- [ ] `./scripts/iteration-gate.sh` green

## Full changelog

[CHANGELOG.md#2035--2026-07-20](CHANGELOG.md#2035--2026-07-20)
```

---

## GitHub Release — copy-paste (2.0.34)

**Title:**

```
2.0.34 — It.52a Dashboard KPI and overview API
```

**Tag:** `v2.0.34` · **Target:** `main`

**Body (skopíruj celé):**

```markdown
## Summary

Release **2.0.34** ships **Iteration 52a**: admin dashboard KPI row (unread messages, media count, disk free space), enriched `/api/admin/dashboard/overview`, and audit activity deep link.

Detail: [docs/ITERATION_52.md](docs/ITERATION_52.md)

## Added

- Dashboard overview API: `counts` (+ `messages_unread`) and `storage.free_space`
- KPI cards on `/dashboard` with links to Messages and Media
- Activity panel footer link to full audit trail

## Changed

- `AdminCountsService` aggregates unread inbox messages for admins
- CI/process docs: ISS-037, `.cursorrules` test-before-push rule

## Test plan

- [ ] Dashboard loads KPI row without errors
- [ ] Unread messages count matches admin inbox
- [ ] `./scripts/iteration-gate.sh` green

## Full changelog

[CHANGELOG.md#2034--2026-07-20](CHANGELOG.md#2034--2026-07-20)
```

---

## 2.0.32 — pred release kontrola

```bash
# Backend
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit

# Frontend
cd frontend && npm run type-check && npm run lint && npm test && npm run build:prod
```

**Po deployi:**

1. Admin **Články/Stránky** → **Náhľad** → skús 100 % / **75 %** / 50 % / celá obrazovka
2. Editor → **Náhľad** (header + footer v modale)
3. **Nastavenia → Obsah** → `Zobraziť odhadovaný čas čítania` (on/off)
4. `/blog` → karty s „Čítať celý článok“ + minútami čítania
5. Admin **Média/Komentáre/Kôš** → filter URL → refresh

---

## GitHub Release — copy-paste (2.0.32)

**Title:**

```
2.0.32 — It.44c admin URL sync, full-page preview, reading time
```

**Tag:** `v2.0.32` · **Target:** `main`

**Body (skopíruj celé):**

```markdown
## Summary

Release **2.0.32** completes **Iteration 44 (FE)** and ships **It.51** preview UX: admin URL sync for media/comments/trash, full-page preview modal (editor + lists) with proportional scale, blog reading time toggle, and date badges.

Detail: [docs/ITERATION_44.md](docs/ITERATION_44.md) · [docs/ITERATION_51.md](docs/ITERATION_51.md)

## Added

- **It.44c:** `useMediaListQueryParams` — Media/Comments/Trash admin lists sync filters to URL
- **`SitePreviewModal`** — Navbar + content + Footer; scales **100 % / 75 % / 50 % / fullscreen**
- Preview from **editor** and **PagesManager** (pages + articles list)
- **`content.showReadingTime`** — toggle estimated reading time on public blog
- **`contact.subjects`** + **`contact.allowCustomSubject`** — configurable contact form subjects
- Dashboard: activity panel + Flat-File structure overview (It.52a slice)
- Utils/tests: `sitePreview`, `readingTime`, `contentDates`, `useAdminListQueryParams`

## Changed

- Blog cards: created/updated date badges, „Čítať celý článok“ CTA, optional reading time
- List **Náhľad** opens in-app modal instead of navigating away (when `openLinksInNewTab` off)

## Test plan

- [ ] Admin Media → folder + filter → copy URL → reload preserves state
- [ ] Pages/Articles list → **Náhľad** → 75 % visibly narrower than 100 %
- [ ] Editor → **Náhľad** → full chrome visible
- [ ] Settings → disable reading time → blog hides minutes
- [ ] Contact page → subject dropdown from settings
- [ ] `./vendor/bin/phpstan analyse backend --level=8` green
- [ ] `./vendor/bin/phpunit` green
- [ ] `cd frontend && npm test && npm run build:prod` green

## Full changelog

[CHANGELOG.md#2032--2026-07-20](CHANGELOG.md#2032--2026-07-20)
```

---

## Git commit message (optional)

```
Release 2.0.32: It.44c URL sync, full-page preview, and blog reading time.

Adds media/comments/trash URL filters, SitePreviewModal with proportional scale, and content.showReadingTime setting.
```

---

## 2.0.31 — pred release kontrola

```bash
# Backend
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit

# Frontend
cd frontend && npm run type-check && npm run lint && npm test && npm run build:prod
```

**Po deployi:**

1. **Nastavenia → Obsah** → `Článkov na stránku (blog)` = napr. `6` → `/blog` pagination
2. **Nastavenia → Admin UI** → `Otvárať náhľady… v novej karte` (default vypnuté)
3. Admin **Články** → filtruj + skopíruj URL → refresh (query params ostávajú)
4. Detail článku → **Predchádzajúci / Ďalší** bez návratu na zoznam

---

## GitHub Release — copy-paste (2.0.31)

**Title:**

```
2.0.31 — It.44 blog pagination, admin URL filters, link target setting
```

**Tag:** `v2.0.31` · **Target:** `main`

**Body (skopíruj celé):**

```markdown
## Summary

Release **2.0.31** delivers **Iteration 44** (public blog + admin list UX): settings-driven blog pagination, article prev/next navigation, admin list filters synced to URL, and a toggle for opening previews/external links in a new tab (default: same tab).

Detail: [docs/ITERATION_44.md](docs/ITERATION_44.md)

## Added

- **`content.blogItemsPerPage`** (default 6) — public blog list pagination
- **Public blog:** `/blog?page=&tag=&sort=` URL sync, sort (newest/oldest/title), prev/next article nav
- **`ui.openLinksInNewTab`** — optional new-tab for previews, “Go to website”, media, footer demo link
- **`AdminListFilterBar`** + **`useAdminListQueryParams`** — pages/articles admin lists: `?q=&status=&sort=&page=&seo=1`
- Vitest: `blogArticles.test.ts`, `linkTarget.test.ts`

## Changed

- Blog list reads `blogItemsPerPage` from settings (not hardcoded 6)
- Preview links no longer force `_blank` unless setting enabled
- PagesManager: “Clear filters” + shareable filter URLs

## Test plan

- [ ] Set blog items per page to 6 → `/blog` shows pagination with 7+ articles
- [ ] Open article → prev/next navigates without returning to list
- [ ] Admin articles: filter + copy URL → reload preserves filters
- [ ] Toggle `openLinksInNewTab` → preview opens same tab vs new tab
- [ ] `./vendor/bin/phpstan analyse backend --level=8` green
- [ ] `./vendor/bin/phpunit` green
- [ ] `cd frontend && npm run type-check && npm test && npm run build:prod` green

## Full changelog

[CHANGELOG.md#2031--2026-07-20](CHANGELOG.md#2031--2026-07-20)
```

---

## Git commit message (optional)

```
Release 2.0.31: It.44 blog pagination, admin URL filters, and link target setting.

Adds blogItemsPerPage, public blog prev/next, admin list URL sync, and ui.openLinksInNewTab toggle.
```

---

## 2.0.30 — pred release kontrola

```bash
# Backend
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit

# Frontend
cd frontend && npm run type-check && npm run lint && npm test && npm run build:prod
```

**LAN deploy (split nginx + PHP):**

```bash
# Na .26 (nginx SPA)
./scripts/deploy-frontend-lan.sh

# Na .20 (PHP) — pull + reštart kontajnera
curl -s http://192.168.10.26:8081/api/health | jq .
```

**Po deployi:**

1. Vymazať cookies pre host
2. Otestovať login → dashboard (bez druhého hesla)
3. Nový staff user → login → `/account/security` → QR → verify
4. Existujúci „rozbitý“ user: opraviť JSON alebo reset 2FA polí (ISS-031)

**Post-release hotfix (2026-07-20):** ak CI padlo na `type-check` po `f5061e6`, pull **`3fbc595`** (ISS-036). Žiadna zmena `.env` — len frontend typy + `updateUser` v 2FA settings.

---

## GitHub Release — copy-paste (2.0.30)

**Title:**

```
2.0.30 — 2FA setup fixes, login loop, dev TOTP toggle
```

**Tag:** `v2.0.30` · **Target:** `main`

**Body (skopíruj celé):**

```markdown
## Summary

Release **2.0.30** opravuje kritické 2FA UX chyby: QR kód zmizne počas setupu, nový staff user dostane TOTP bez secretu, a frontend hard-reload spôsoboval „dvojitý login“. Pridáva dev prepínač `TWO_FACTOR_REQUIRED=false`. Incidenty: [docs/ISSUES.md](docs/ISSUES.md) (ISS-030–ISS-036).

## Added

- **`TWO_FACTOR_REQUIRED`** env + `TwoFactorPolicy` — vypnutie TOTP len v dev/test (nie na produkcii)
- **`setup_pending`** v `/api/auth/2fa/status` — rozlíšenie prvého QR setupu vs login TOTP
- Admin banner „Dokončite nastavenie 2FA“ + redirect na `/account/security`
- Auth custom events namiesto `window.location` pri 401

## Fixed

| ID | Issue |
|----|-------|
| ISS-030 | QR disappears after 2FA enable → kicked to login TOTP |
| ISS-031 | Staff user created with `twoFactorEnabled` but no secret |
| ISS-032 | `twoFactorVerifiedAt` not saved in user JSON |
| ISS-033 | 401 → hard redirect caused double password login |
| ISS-029 | Login loop follow-up for 2FA / new users |
| ISS-035 | PHPStan ClientIpResolver dead `??` (CI hotfix) |
| ISS-036 | FE type-check: 2FA `setup_pending` unwrap + `updateUser` (post-2.0.30 CI hotfix `3fbc595`) |

## Ops — dev `.env` (optional)

```env
APP_ENV=development
TWO_FACTOR_REQUIRED=false
SESSION_LIFETIME=28800
SESSION_STRICT=false
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

Restart PHP. Clear browser cookies.

**Broken account recovery:** in `storage/app/users/<id>.json` set `twoFactorEnabled: false`, `twoFactorSecret: null`, `twoFactorVerifiedAt: null`, then re-login and complete setup at `/account/security`.

## Test plan

- [ ] Login (no 2FA) → dashboard without second password prompt
- [ ] Create staff user → login → `/account/security` → QR stays visible → verify works
- [ ] Logout → login with 2FA → TOTP step only (not QR setup again)
- [ ] `TWO_FACTOR_REQUIRED=false` in dev skips TOTP middleware
- [ ] `./vendor/bin/phpstan analyse backend --level=8` green
- [ ] `./vendor/bin/phpunit` green
- [ ] `npm run build:prod` green

## Full changelog

[CHANGELOG.md#2030--2026-07-19](CHANGELOG.md#2030--2026-07-19)
```

---

## Git commit message (optional, keď schváliš commit)

```
Release 2.0.30: fix 2FA setup/login loop and add dev TOTP toggle.

Fixes ISS-030–ISS-035 (QR setup vs login TOTP, staff user without secret,
twoFactorVerifiedAt persistence, FE 401 hard redirect). Adds TWO_FACTOR_REQUIRED env.
```

---

## Hotfix commit message (2.0.30 post-release, ISS-036)

```
fix(frontend): resolve 2FA TypeScript CI errors on main.

Map setup_pending in auth API layer, use updateUser in TwoFactorSettings, and align test fixtures.
```

Commit: **`3fbc595`** · pushed to `main` 2026-07-20.

---

## Predchádzajúce release

- **2.0.29** — Session hardening, cache admin — [CHANGELOG.md#2029--2026-07-19](../CHANGELOG.md#2029--2026-07-19)
- **2.0.28** — It.12 Blueprint + It.13 Demo sandbox v2 — [CHANGELOG.md#2028--2026-07-19](../CHANGELOG.md#2028--2026-07-19)
