---
title: Známe incidenty a opravy
description: Kanonický register incidentov PaginiumCMS s príčinami, riešeniami, overením a stabilnými odkazmi
icon: material/alert-circle-check
---

# PaginiumCMS – Známe incidenty a opravy

> **Posledná aktualizácia:** 2. august 2026 · dokumentačný snapshot **`v2.1.0-beta.23`** · register **ISS-001–ISS-120**

Tento dokument je kanonický verejný register produkčných, integračných, bezpečnostných, prevádzkových a CI problémov zistených počas vývoja PaginiumCMS. Každé číslo incidentu v prehľade je klikateľné a smeruje na stabilný explicitný anchor s popisom, príčinou, riešením a dostupným overením.

> **Stabilita odkazov:** Anchory majú tvar `#iss-001` až `#iss-120` a nemenia sa ani po úprave názvu incidentu. Odkazy možno bezpečne používať v changelogu, release notes, commitoch a GitHub Issues.

> **Markdown preview:** Wildcard zapisuj celý v backticks, napríklad `navigation.json.backup.*`. Neuzavretá kurzíva alebo code fence môže rozbiť preview veľkého súboru.

> **Audit report:** Koreňový `AUDIT_REPORT.md` môže byť lokálny/gitignored pracovný dokument. Verejný stav opráv sleduj v tomto registri a v [CHANGELOG.md](../../CHANGELOG.md).

<a id="prehlad"></a>

## Prehľad

| ID | Symptóm | Závažnosť | Stav |
|---|---|---|---|
| [ISS-001](#iss-001) | `POST /api/debug/client-event` → 404 | Nízka (šum v konzole) | ✅ Opravené |
| [ISS-002](#iss-002) | `GET /api/pages` → 500 na dashboarde | Vysoká | ✅ Intermittent — diagnose + hardening |
| [ISS-003](#iss-003) | „Noví používatelia“ každú chvíľu | Stredná | ✅ Hardening BE |
| [ISS-004](#iss-004) | Hromadenie `navigation.json.backup.*` | Nízka | ✅ Retencia záloh |
| [ISS-005](#iss-005) | Vitest worker crash / visnutie | Stredná (CI) | ✅ Opravené |
| [ISS-006](#iss-006) | PHPStan 15 chýb | Stredná (CI) | ✅ Opravené |
| [ISS-007](#iss-007) | Dashboard nesprávny počet používateľov | Nízka | ✅ Opravené |
| [ISS-008](#iss-008) | HTTP heslo polia (login/users) | Info | ⏳ HTTPS v produkcii |
| [ISS-009](#iss-009) | `/settings` crash `n.max is not a function` | Vysoká | ✅ Opravené |
| [ISS-010](#iss-010) | Vitest stderr: `act(...)` + Router future flags | Nízka (CI šum) | ✅ Opravené (2.0.24) |
| [ISS-011](#iss-011) | ESLint warnings (`any`, react-refresh) | Nízka (tech. dlh) | ⏳ 57/65 baseline, postupné čistenie |
| [ISS-012](#iss-012) | CSRF middleware nezapojený (audit S3) | Stredná | ✅ Opravené — `CsrfMiddleware` (synchronizer-token) |
| [ISS-013](#iss-013) | ntfy bez auth — privátne topicy zlyhajú | Stredná | ✅ It.47 (Bearer/Basic + test-connector) |
| [ISS-014](#iss-014) | CORS dev wildcardy pri zlej `APP_ENV` (audit S6) | Nízka | ⏳ Overiť deploy |
| [ISS-015](#iss-015) | PHPUnit → 429 / 503 / OTP persistencia | Stredná (CI) | ✅ Opravené (2.0.25) |
| [ISS-016](#iss-016) | PHPStan `phpVersion` vs `composer.json` | Stredná (CI) | ✅ Opravené (2.0.25) |
| [ISS-017](#iss-017) | PHPStan `match.alwaysTrue` v bulk controlleroch | Stredná (CI) | ✅ Opravené (2.0.25) |
| [ISS-018](#iss-018) | PHPStan `fopen` resource v `TrashController` | Stredná (CI) | ✅ Opravené (2.0.25) |
| [ISS-019](#iss-019) | `tsc --noEmit` strict TypeScript chyby | Stredná (CI) | ✅ Opravené (2.0.25) |
| [ISS-020](#iss-020) | ESLint 68 warnings → prekročenie `--max-warnings 65` | Stredná (CI) | ✅ Opravené (2.0.26) |
| [ISS-021](#iss-021) | PHPStan `function.alreadyNarrowedType` v log readeri | Stredná (CI) | ✅ Opravené (2.0.26) |
| [ISS-022](#iss-022) | Vitest `MediaManager.test.tsx` — krehké textové asercie | Stredná (CI) | ✅ Opravené (2.0.26) |
| [ISS-023](#iss-023) | PHPUnit `SearchControllerTest` — flaky admin draft search | Stredná (CI) | ✅ Opravené (2.0.29) |
| [ISS-024](#iss-024) | `AuthMiddleware` → 500 na auth trasách | Kritická | ✅ Opravené (2.0.29) |
| [ISS-025](#iss-025) | Odhlásenie počas editácie / pri uložení | Vysoká | ✅ Opravené (2.0.29) |
| [ISS-026](#iss-026) | Zámena `SESSION_USE_STRICT_MODE` ↔ `SESSION_STRICT` | Stredná (ops) | ✅ Dokumentované (2.0.29) |
| [ISS-027](#iss-027) | Debug log: falošné login 401 z PHPUnit | Nízka (diagnostika) | ✅ Opravené (2.0.29) |
| [ISS-028](#iss-028) | `npm run build:prod` — JSX chyba v `SettingsView` | Vysoká (deploy) | ✅ Opravené (2.0.29) |
| [ISS-029](#iss-029) | Login loop — krátke prihlásenie, potom späť na `/login` | Vysoká | ✅ 2.0.29 session; **2.0.30** 2FA loop |
| [ISS-030](#iss-030) | 2FA setup — QR zmizne, presmeruje na TOTP login | Vysoká | ✅ Opravené (2.0.30) |
| [ISS-031](#iss-031) | Nový staff user: `twoFactorEnabled` bez secretu | Kritická | ✅ Opravené (2.0.30) |
| [ISS-032](#iss-032) | `twoFactorVerifiedAt` sa neukladalo do user JSON | Vysoká | ✅ Opravené (2.0.30) |
| [ISS-033](#iss-033) | FE 401 → `window.location /login` (dvojitý login) | Vysoká | ✅ Opravené (2.0.30) |
| [ISS-034](#iss-034) | Dev: žiadny prepínač TOTP v `.env` | Stredná (DX) | ✅ Opravené (2.0.30) |
| [ISS-035](#iss-035) | PHPStan: `ClientIpResolver` mŕtvy `??` fallback | Nízka (CI) | ✅ Opravené (2.0.29 hotfix) |
| [ISS-036](#iss-036) | FE type-check: 2FA `setup_pending` / `setUser` (CI) | Stredná (CI) | ✅ Opravené (2.0.30 hotfix `3fbc595`) |
| [ISS-037](#iss-037) | FE type-check: nepoužitý `React` import v teste (CI) | Nízka (CI) | ✅ Opravené (hotfix `64cc894`) |
| [ISS-038](#iss-038) | PHPUnit It.44d: index filtre tag/date (CI) | Stredná (CI) | ✅ Opravené (`54b013c`) |
| [ISS-039](#iss-039) | PHPUnit `LogWriterTest`: vfs + corrupt JSON (CI) | Stredná (CI) | ✅ Opravené (`54b013c`) |
| [ISS-040](#iss-040) | Corrupt access log → `JsonException` → API 500 | Kritická (prod) | ✅ Opravené (`743e922`) |
| [ISS-041](#iss-041) | FE type-check: nepoužitý `refetch` v `PagesManager` (CI) | Nízka (CI) | ✅ Opravené (hotfix 2.0.40) |
| [ISS-042](#iss-042) | Dvojitý login — 1. pokus padne, 2. prejde (probe `/me`) | Vysoká (auth UX) | ✅ Opravené (**2.0.43**) |
| [ISS-043](#iss-043) | Vitest `editorToolbar` — globálny `screen` vs. profil | Nízka (CI) | ✅ Opravené (2.0.42 It.54) |
| [ISS-044](#iss-044) | `services.php:301` parse error → API 500 | Kritická | ✅ Opravené (**2.0.45**) |
| [ISS-045](#iss-045) | `LocaleScaffoldService::$projectRoot` — PHPStan + PHPUnit | Stredná (CI) | ✅ Opravené (**2.0.45**) |
| [ISS-046](#iss-046) | Audit udalosti sa zapisovali ako kategória `app` | Vysoká (audit) | ✅ Opravené (**2.0.46**) |
| [ISS-047](#iss-047) | Dashboard „Prehľad aktivít“ prázdny | Vysoká (admin UX) | ✅ Opravené (**2.0.46**) |
| [ISS-048](#iss-048) | Audit správy nečitateľné (zlý formát + EN text) | Stredná (audit UX) | ✅ Opravené (**2.0.46**) |
| [ISS-049](#iss-049) | Korumpovaný denný log `2026-07-21.json` | Stredná (ops) | ✅ Opravené (**2.0.46**) |
| [ISS-050](#iss-050) | Sekcia Logy prázdna — nesprávna cesta log readera | Vysoká (admin UX) | ✅ Opravené (**2.0.46**) |
| — | Login pozadie — len URL pole (bez uploadu/médií) | Stredná (admin UX) | ✅ Opravené (**2.0.46**) |
| [ISS-051](#iss-051) | Boot crash — `DevTokenGenerator` výnimka pri `APP_ENV=production` | Kritická (boot/CLI) | ✅ Opravené (security_fix hotfix) |
| [ISS-052](#iss-052) | Tajomstvá (TOTP seed, SMTP/SSO/ntfy) v plaintexte na disku (audit A1) | Stredná (bezpečnosť) | ✅ Opravené — `EncryptionService` + `APP_KEY` |
| [ISS-053](#iss-053) | Log/CSV injection cez `\r\n` v query/User-Agent (audit C11) | Nízka-Stred (bezpečnosť) | ✅ Opravené — `LogSanitizer` |
| [ISS-054](#iss-054) | SSRF cez admin-konfigurovateľné URL (OAuth/ntfy/webhook, audit C14) | Nízka-Stred (bezpečnosť) | ✅ Opravené — `OutboundUrlGuard` |
| [ISS-055](#iss-055) | Path ACL implementované, ale nezapojené do content/media (audit S9) | Stredná (bezpečnosť) | ✅ Opravené — `ContentPathAclGuard` |
| [ISS-056](#iss-056) | WAF skenuje len URI/query/UA, nie POST/JSON telo (audit S-WAFBODY) | Stredná (bezpečnosť) | ✅ Opravené — body scan + editor exempt |
| [ISS-057](#iss-057) | `UserRepository::findByEmail/findById` O(n) scan všetkých JSON (audit PERF-USERREPO) | Nízka (výkon) | ✅ Opravené — `UserIndexService` + `data/index/users.json` |
| [ISS-058](#iss-058) | OTP bez dedikovaného rate-limitu + resend resetuje pokusy (audit S10) | Stredná (bezpečnosť) | ✅ Opravené — `Otp*RateLimitMiddleware` + `resend_count` |
| [ISS-059](#iss-059) | Vitest — `useI18n()` bez `I18nProvider` v unit testoch (CI @ `f0a885c`) | Nízka (CI) | ✅ Opravené — `renderWithProviders` (**2.0.47**) |
| [ISS-060](#iss-060) | `settings/en.ts` workflows — SK copy-paste v EN katalógu (OTP labely) | Stredná (i18n UX) | ✅ Opravené (**2.0.47** / `f0a885c`) |
| [ISS-061](#iss-061) | Audit správy v EN admin locale zostávali po slovensky | Stredná (i18n UX) | ✅ Opravené (**2.0.49**) |
| [ISS-062](#iss-062) | Verejný web mal hardcoded SK aj pri EN admin locale | Stredná (i18n UX) | ✅ Opravené (**2.0.50**) |
| [ISS-063](#iss-063) | Admin + verejný web: `RangeError: Invalid time value` (dátumy) | Vysoká (prod crash) | ✅ **2.0.51** |
| [ISS-064](#iss-064) | CI `tsc`: `DEFAULT_LOCALE` neexportovaný z `i18n/index.ts` | Nízka (CI) | ✅ **2.0.51** |
| [ISS-065](#iss-065) | Admin logy o **2 h dozadu** (PHP timezone UTC) | Stredná (ops) | ✅ **2.0.51** |
| [ISS-066](#iss-066) | PHPUnit: `CronExpressionEvaluator` same-minute + DST | Nízka (CI) | ✅ **2.0.51** |
| [ISS-067](#iss-067) | PHPUnit: `LocaleMiddlewareTest` mock po timezone middleware | Nízka (CI) | ✅ **2.0.51** |
| [ISS-068](#iss-068) | Code policy zamietnutie logované ako **ERROR** + stack trace | Stredná (logy) | ✅ **2.0.51** |
| [ISS-069](#iss-069) | Nastavenia: časové pásmo len voľný text | Stredná (admin UX) | ✅ **2.0.51** |
| [ISS-070](#iss-070) | Nastavenia: chýba prepínač **letného času (DST)** | Stredná (ops) | ✅ **2.0.51** |
| [ISS-071](#iss-071) | Logy: chýbajú bulk akcie, delete-all a stránkovanie | Stredná (admin UX) | ✅ Opravené · **2.0.51** |
| [ISS-072](#iss-072) | Security audit `/api/admin/security/audit` → 403 pre ADMIN | Stredná (regresia) | ✅ Opravené · **2.0.52** |
| [ISS-073](#iss-073) | PHPUnit: login testy → 429 namiesto 401 (lockout persistencia) | Stredná (CI) | ✅ Opravené · **2.0.52** |
| [ISS-074](#iss-074) | PHPStan L8 po `accessControl` / branding (10 chýb) | Stredná (CI) | ✅ Opravené · **2.0.52** |
| [ISS-075](#iss-075) | PHPUnit fatal: `Cannot redeclare class HelloWidget\Hooks` (Wave 5d) | Stredná (CI) | ✅ Opravené · **2.0.54** |
| [ISS-076](#iss-076) | PHPUnit kaskáda po `passwordConfirm` — 21 failov (401/422/null) | Stredná (CI) | ✅ Opravené · **2.0.56** |
| [ISS-077](#iss-077) | Audit trail CSV export bez `LogSanitizer` (C11 medzera) | Stredná (security) | ✅ Opravené · **2.1.0-beta.2** |
| [ISS-078](#iss-078) | `react-router-dom@6.30.4` — 3× npm moderate (GHSA, po publikácii beta.2) | Stredná (dependency) | ✅ Opravené · **2.1.0-beta.3** |
| [ISS-079](#iss-079) | Profil **blog** — save zlyhá na existujúcom fenced code block (`` ``` ``) | Vysoká (admin UX) | ✅ Opravené · **2.1.0-beta.5** |
| [ISS-080](#iss-080) | PHPStan: `ContentMetaController` volá neexistujúce `getGroup()` | Stredná (CI) | ✅ Opravené · **2.1.0-beta.4** |
| [ISS-081](#iss-081) | Dependabot: čiastočný bump `@tiptap/*` → peer conflict + CI fail | Stredná (CI / deps) | ✅ Opravené · **2.1.0-beta.4** |
| [ISS-082](#iss-082) | Dependabot #7: `symfony/yaml` 8.x vs constraint `^7.0` | Nízka (tech. dlh) | ⏳ Odložené — major migrácia |
| [ISS-083](#iss-083) | Dependabot #10: `eslint` 10.x — breaking flat config | Nízka (tech. dlh) | ⏳ Odložené — samostatný upgrade |
| [ISS-084](#iss-084) | Samovolné odhlásenie v Chrome (~24 min) — kaskáda 401 na `/me`, `/admin/counts`, … | Vysoká (auth UX) | ✅ Opravené · **2.1.0-beta.5** |
| [ISS-085](#iss-085) | Rich navigácia — Lucide ikona len rámček, popis neviditeľný na desktope | Stredná (admin/public UX) | ✅ Opravené · **2.1.0-beta.5** |
| [ISS-086](#iss-086) | Stored XSS — `strip_tags` nečistí atribúty (`onerror`, `javascript:`) | **Kritická (security)** | ✅ Opravené · **2.1.0-beta.6** |
| [ISS-087](#iss-087) | `deploy-frontend-lan.sh` — hardcoded host/user/port | Stredná (ops / hygiene) | ✅ Opravené · **2.1.0-beta.6** |
| [ISS-088](#iss-088) | Backup import — Zip-Slip pri `extractTo()` | Stredná (security) | ✅ Opravené · **2.1.0-beta.6** |
| [ISS-089](#iss-089) | `npm audit` high — GHSA-qwww-vcr4-c8h2 (RR RSC-only, React 18) | Nízka (false positive SPA) | ⏳ Akceptované · CI `--audit-level=critical` |
| [ISS-090](#iss-090) | `eslint: latest` → npm 10, `npm audit fix` ERESOLVE | Nízka (CI/deps) | ✅ Opravené · **2.1.0-beta.7** |
| [ISS-091](#iss-091) | Vitest 14× fail — `react-router@8` override + `useOptimistic` | Stredná (CI) | ✅ Opravené · **2.1.0-beta.7** |
| [ISS-092](#iss-092) | Deploy — lokálne env + syntax `:?` v deploy skripte | Nízka (ops) | ✅ Opravené · **2.1.0-beta.7** |
| [ISS-093](#iss-093) | ESLint `expand is not a function` — override `brace-expansion@5` vs `minimatch@3` | Stredná (CI) | ✅ Opravené · odstránený override |
| [ISS-094](#iss-094) | Job scheduler `POST …/run` → 500 na prod (Docker storage + UI) | Vysoká (prod) | ✅ Opravené · **It.62** (`f7a73f1`) |
| [ISS-095](#iss-095) | Maintenance heroImageUrl — `/storage/` cesty odmietnuté | Stredná (admin UX) | ✅ Opravené · **main `88cbe31`** |
| [ISS-096](#iss-096) | 502 hneď po `stack.sh restart php` | Nízka (ops) | ℹ️ Informatívne — počkať 5–10 s |
| [ISS-097](#iss-097) | Newsletter odberatelia bez admin UI | Stredná | ✅ Opravené · **It.61** |
| [ISS-098](#iss-098) | Demo login 401 prázdna odpoveď (CORS / zlý `APP_URL`) | **Vysoká (demo)** | ✅ Opravené · **SameOriginCors** + `.env` |
| [ISS-099](#iss-099) | Demo CLI/cron `demo:reset-if-due` — Permission denied `plugins.json` | Stredná (demo ops) | ℹ️ Ops — storage `chown user:www-data`, dirs `2775` |
| [ISS-100](#iss-100) | S-DEMOCREDS — demo heslo v `GET /api/settings/public` | Stredná (audit) | ✅ **`v2.1.0-beta.11`** — quick-login, no password in GET |
| [ISS-101](#iss-101) | Editor biela obrazovka — `capabilities.includes is not a function` | Vysoká (demo/admin) | ✅ **`v2.1.0-beta.11`** — normalize API profile shape |
| [ISS-102](#iss-102) | Demo API celé HTTP 500 — chýba `backend/storage/app/demo/data/`, mkdir Permission denied | **Vysoká (demo)** | ✅ Ops — storage bootstrap (2026-07-27) |
| [ISS-103](#iss-103) | PHPUnit OTP/2FA flaky — lokálny `.env` (`DEMO_MODE=true`) polluluje HTTP testy | Stredná (dev/CI) | ✅ **`v2.1.0-beta.12`** — test bootstrap izolácia |
| [ISS-104](#iss-104) | A3-JOBDEPLOY — ADMIN obíde SUPER_ADMIN cez jobs API pri `system-deploy` | Stredná (audit) | ✅ **`v2.1.0-beta.15`** |
| [ISS-105](#iss-105) | A6-GEOIP — cleartext `ip-api.com` bez `OutboundUrlGuard` | Nízka (audit) | ✅ **`v2.1.0-beta.15`** |
| [ISS-106](#iss-106) | A8-DEMOMODE — `DEMO_MODE=true` na produkcii bez fail-closed | Nízka (audit) | ✅ **`v2.1.0-beta.16`** |
| [ISS-107](#iss-107) | A7-NEWSLETTER — maintenance subscribe bez honeypatu / bez dedikovaného rate limitu | Nízka (audit) | ✅ **`v2.1.0-beta.16`** |
| [ISS-108](#iss-108) | A9-GHSERVICE — `GitHubService` curl bez `OutboundUrlGuard` | Info (audit) | ✅ **`v2.1.0-beta.16`** |
| [ISS-109](#iss-109) | Newsletter footer CTA príliš objemný | Nízka (UX) | ✅ **`v2.1.0-beta.18`** |
| [ISS-110](#iss-110) | Prod SEO `GET /api/seo/*` → 500 (cache array vs Content) | **Vysoká (prod)** | ✅ **`v2.1.0-beta.21`** |
| [ISS-111](#iss-111) | LoggerTest / PHPStan regresia po skip logov v `APP_ENV=testing` | Stredná (CI/testy) | ✅ **`v2.1.0-beta.21`** |
| [ISS-112](#iss-112) | Lock badge „aktívne pred 56 rokmi“ (Unix s vs ms) | Nízka (admin UX) | ✅ opravené lokálne — **ďalší release** |
| [ISS-113](#iss-113) | Static SPA bez security headers (len PHP middleware) | Stredná (audit) | ✅ nginx snippet + prod/demo template |
| [ISS-114](#iss-114) | CSRF exempt prefix bez `/` word boundary | Stredná (audit) | ✅ `CsrfMiddleware::isExempt()` |
| [ISS-115](#iss-115) | `expose_php` odhaľuje PHP verziu | Nízka (audit) | ✅ `docker/php/php.ini` |
| [ISS-116](#iss-116) | Hardcoded LAN IP v `TRUSTED_PROXIES` default | Nízka (audit) | ✅ default `127.0.0.1,::1` + `.env` |
| [ISS-117](#iss-117) | GHSA-qwww-vcr4-c8h2 (RR RSC-only) | Nízka (N/A SPA) | ℹ️ ISS-089 · CI `--audit-level=critical` |
| [ISS-118](#iss-118) | `/.well-known/security.txt` chýba / SPA fallback | Nízka (audit) | ✅ `frontend/public/.well-known/` + nginx |
| [ISS-119](#iss-119) | Docker stack neštartuje po reboot hosta | Stredná (ops) | ✅ `restart: unless-stopped` v prod compose |
| [ISS-120](#iss-120) | CI PHPUnit log — TOTP/2FA secret echo v GitHub job logu | Stredná (security / CI) | ✅ sanitize wrapper + verify |

## CI failures (GitHub Actions)

Workflow: [`.github/workflows/ci.yml`](https://github.com/techberode/paginiumcms-architecture/blob/main/.github/workflows/ci.yml)

| CI job | Krok | Symptóm | Incident |
|---|---|---|---|
| `backend` | PHPUnit | `PluginManagerTest` + referenčný `hello-widget` → duplicate class fatal | [ISS-075](#iss-075) |
| `backend` | PHPUnit | Po `passwordConfirm` (2.0.56): `CoreHardeningTest` → kaskáda 401/422 v Media, Path ACL, OTP | [ISS-076](#iss-076) |
| `backend` | PHPUnit | Login error shape / AuthController → **429** namiesto 401 (lockout persistencia) | [ISS-073](#iss-073) |
| `backend` | PHPUnit | `SecurityAuditControllerTest` → 403 pre ADMIN | [ISS-072](#iss-072) |
| `backend` | PHPStan level 8 | `accessControl` / `SettingsSchema` / `AccessControlSyncService` (10×) | [ISS-074](#iss-074) |
| `backend` | PHPStan level 8 | `LocaleScaffoldService::$projectRoot` undefined (7×) | [ISS-045](#iss-045) |
| `backend` | PHP bootstrap | `services.php:301` parse error → all API 500 | [ISS-044](#iss-044) |
| `backend` | PHPUnit | Verbose log obsahoval `otpauth://` / 2FA secret z test echo | [ISS-120](#iss-120) |
| `backend` | PHPUnit | 429 Too Many Requests, 503 maintenance, flaky OTP, flaky search test | [ISS-015](#iss-015), [ISS-023](#iss-023) |
| `backend` | PHPUnit | `ContentRepositoryTest` — date/tag filter, distinct tags (It.44d) | [ISS-038](#iss-038) |
| `backend` | PHPUnit | `LogWriterTest` — chýbajúci súbor na vfs, corrupt recovery | [ISS-039](#iss-039) |
| `backend` | PHPUnit (prod) | `JsonException` v access log → 500 na všetkých API | [ISS-040](#iss-040) |
| `frontend` | `npm run type-check` | TS6133 — nepoužitý import `React` v `SettingsView.test.tsx` | [ISS-037](#iss-037) |
| `frontend` | `npm run type-check` | TS6133 — nepoužitý `refetch` v `PagesManager.tsx` (It.53) | [ISS-041](#iss-041) |
| `frontend` | `npm test` | Vitest — `screen` nájde toolbar z druhého renderu (It.54) | [ISS-043](#iss-043) |
| `frontend` | `npm test` (CI) | `useI18n must be used within I18nProvider` — 6 suites @ `f0a885c` | [ISS-059](#iss-059) |
| `frontend` | `npm run type-check` | TS2352 / TS6133 / TS2322 / 2FA DTO shape (`setup_pending`, `setUser`) | [ISS-019](#iss-019), [ISS-036](#iss-036) |
| `frontend` | `npm run lint` | Prekročenie `--max-warnings 65` (`react-hooks/exhaustive-deps`) | [ISS-020](#iss-020) |
| `frontend` | `npm test` | Worker crash, `act(...)` stderr, `MediaManager` text asserts | [ISS-005](#iss-005), [ISS-010](#iss-010), [ISS-022](#iss-022) |
| `frontend` | `npm audit --audit-level=moderate` | `react-router-dom@6.30.4` — 3× moderate GHSA (po tagu beta.2) | [ISS-078](#iss-078) |
| `frontend` | `npm ci` / Vitest | Dependabot PR #9/#11/#12 — len 1× `@tiptap` bump → peer `@tiptap/core@3.28.0` conflict | [ISS-081](#iss-081) |
| `backend` | PHPStan level 8 | `ContentMetaController` — `getGroup()` undefined (It.57 gate) | [ISS-080](#iss-080) |
| `backend` | PHPStan (historicky) | 15 typových chýb | [ISS-006](#iss-006) |

Každý kanonický záznam obsahuje podľa dostupnosti **symptóm**, **príčinu**, **navrhované alebo implementované riešenie**, **overenie**, súvisiace incidenty a odkazy na commit, CI alebo changelog.


---

<a id="iss-001"></a>

## ISS-001 – Debug client-event 404

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-001)

**Symptóm:** Konzola plná `XHR POST …/api/debug/client-event [404]`, hoci FE loguje `[PaginiumCMS] event: …`.

**Príčina:** Frontend posielal udalosti pri `VITE_DEBUG=true` alebo v DEV režime, ale backend registroval trasu len keď `APP_DEBUG=true`. Pri prod builde na `:8081` → trasa neexistovala.

**Oprava:**

- `backend/app/Http/Routes/debug.php` – trasa vždy registrovaná.
- `DebugController` – pri vypnutom debug vráti **204** (no-op).
- `frontend/src/utils/debugLog.ts` – POST na backend len pri `VITE_DEBUG=true` (nie automaticky v prod).

**Overenie:** Po redeploy by mali 404 zmiznúť; pri `VITE_DEBUG=false` zostane len `console.debug`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-002"></a>

## ISS-002 – GET /api/pages → 500

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-002)

**Symptóm:** Po prihlásení dashboard volá `/api/pages` a dostane **500 Internal Server Error**. Články (`/api/articles`) môžu prejsť.

**Pravdepodobné príčiny (serverové prostredie):**

1. Poškodený / neaktuálny content index (`data/index/content.json`) — orphan záznamy po trash/bulk testoch.
2. Neplatný Markdown / front matter v `content/pages/*.md` — jeden súbor padal celý zoznam.
3. Práva zápisu/čítania na `backend/storage/app/content/` alebo index (flock zlyhanie → `RuntimeException`).
4. Poškodená content cache po upgrade.

**Overenie (2026-07-19, server** `192.168.10.26:8081`**):**

```bash
curl -s http://192.168.10.26:8081/api/pages | jq '.success, (.data|length)'
# → true, 43

curl -s "http://192.168.10.26:8081/api/pages?page=1&per_page=20" | jq '.success, .meta.total'
# → true, <total>
```

API momentálne vracia **200** — issue je **intermittent / env**, nie trvalá regresia v PHPUnit.

**Diagnostika (ops):**

```bash
# Docker log
docker compose logs php | tail -100

# CLI diagnose (ISS-002 tooling)
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --json

# Oprava cache + index
php backend/bin/console content:cache-purge --reindex
# alias:
php backend/bin/rebuild-content-index.php

# Priamy test API (s cookies po login)
curl -s -b cookies.txt http://192.168.10.26:8081/api/pages | jq .
```

**Implementované hardening (2.0.26+):**

- `content:diagnose` — report indexu, orphanov, nečitateľných súborov, backup súborov v content dirs
- `ContentRepository::findByPath()` — pri chybe parse vráti `null` namiesto 500 celého listu
- `ContentController::serializeContentList()` — preskočí corrupt položky
- `FileReader::listFiles()` — ignoruje `*.backup.*` rotácie

**Stav:** 🟡 Monitorovať po deployi; pri opakovaní 500 spusti `content:diagnose --fix` na serveri a doplň stack trace z PHP logu sem.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-003"></a>

## ISS-003 – Phantom / duplicitní používatelia

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-003)

**Symptóm:** V admin `/users` pribúdajú záznamy (pocit „generovania každú sekundu“).

**Príčiny:**

1. **Backup súbory** v `data/users/` – `FileWriter` vytváral `*.json.backup.Ymd_His`; pri chybnom glob/scandir mohli byť načítané ako používatelia.
2. **Neplatný JSON** bez `id`/`email` – `User` konštruktor generoval nové `uniqid()` pri každom hydrate.
3. **Otvorená registrácia** (`general.allowRegistration`) – bot / opakované POST `/api/auth/register`.

**Oprava (kód):**

- `UserRepository::getAllUserFiles()` – ignoruje `*.backup.*`.
- `UserRepository::findAll()` – vyžaduje `id` + `email`, deduplikácia podľa ID.
- `FileWriter::pruneBackups()` – max. **5** záloh na súbor.

**Manuálne čistenie na serveri:**

```bash
# Náhľad backup súborov medzi používateľmi (NIE samotné účty!)
ls -la backend/storage/app/content/data/users/*.backup.* 2>/dev/null

# Odstránenie starých backupov (po backup celého priečinka!)
find backend/storage/app/content/data/users -name '*.backup.*' -delete
```

> **⚠️ Admin účet sa NEMAŽE.** Príkaz `find … '*.backup.*'` cieli len na súbory typu  
> `user_xxx.json.backup.20260718_120000`. Tvoj prihlasovací súbor `user_xxx.json` (SUPER_ADMIN / ADMIN)  
> zostáva nedotknutý. Pred mazaním vždy over: `ls …/data/users/*.json` — tieto súbory **nechaj**.

**Odporúčanie:** V administrácii vypnúť verejnú registráciu, ak nie je potrebná.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-004"></a>

## ISS-004 – navigation.json.backup.*

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-004)

**Symptóm:** V `data/` pribúdajú súbory `navigation.json.backup.20260718_104530`.

**Príčina:** Každý zápis cez `FileWriter::write(..., backup=true)` vytvorí timestamp backup (Navigation, users, content, …).

**Oprava:** `FileWriter::pruneBackups()` – ponechá posledných 5 backupov, staršie zmaže.

**Poznámka:** Backup ≠ nový používateľ. Súbor `navigation.json.backup.*` je normálna rotácia, nie chyba navigácie.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-005"></a>

## ISS-005 – Vitest worker crash (Node 26)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-005)

**Symptóm:** `Error: Worker exited unexpectedly` pri `npm test`.

**Príčina:** Nekonečná slučka v `useBulkSelection` – `useMemo(..., [visibleIds])` pri inline poli v `renderHook`.

**Oprava:** Stabilizácia cez `visibleKey = visibleIds.join('\0')` v `frontend/src/hooks/useBulkSelection.ts`.

**Výsledok:** 28 súborov, 102 testov OK (Node 22 aj 26).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-006"></a>

## ISS-006 – PHPStan level 8

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-006)

**Opravené súbory:** `LoginAttemptTracker`, `SeoMetaBuilder`, `MediaFormats`, `MediaRepository`, `BulkBatchResultTest`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-007"></a>

## ISS-007 – Dashboard user count = 0

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-007)

**Príčina:** `DashboardView` čítal `usersRes.data.length`, ale API vracia `{ users: User[] }`.

**Oprava:** `usersRes.data.users.length` v `DashboardView.tsx`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-008"></a>

## ISS-008 – Heslo cez HTTP (login, users, **settings**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-008)

**Symptóm:** Prehliadač varuje: „Polia s heslami sú umiestnené na nezabezpečenej stránke (http://).“  
Vidíš to na `/login`, `/users` aj `/settings` – nie je to chyba PaginiumCMS.

**Prečo:** Stránka beží na `http://192.168.10.26:8081` (nginx bez TLS). Prehliadač chráni heslá pred odpočúvaním v sieti (LAN/Wi‑Fi).

**Ktoré polia to spúšťajú v Nastaveniach:**

- SMTP password (`smtp.password`)
- Telegram bot token, webhook secret
- (Rovnako prihlasovacie heslo na `/login`)

**Čo to NIE je:** Chyba validácie, bug vo formulári ani „únik“ hesla z CMS. Ide o **varovanie prehliadača**.

### Riešenie (odporúčané): HTTPS pred nginx

Tvoj deploy: `docs/deploy/nginx-paginium-test.conf` (port **8081**). Pridaj TLS:

```bash
# Self-signed cert pre LAN (192.168.10.26)
sudo mkdir -p /etc/nginx/ssl
sudo openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/paginium-test.key \
  -out /etc/nginx/ssl/paginium-test.crt \
  -subj "/CN=192.168.10.26"
```

V nginx server bloku (okrem `listen 8081`):

```nginx
listen 8443 ssl;
listen [::]:8443 ssl;
ssl_certificate     /etc/nginx/ssl/paginium-test.crt;
ssl_certificate_key /etc/nginx/ssl/paginium-test.key;
```

Potom otvor `https://192.168.10.26:8443/settings` – varovanie pri heslách zmizne (pri self-signed certe raz potvrdíš výnimku v prehliadači).

**Produkcia s doménou:** Let's Encrypt cez Caddy/nginx (`mail.webland.fun` alebo vlastná doména) – pozri `docs/deploy/NGINX_API.md`.

### Dočasne na izolovanom LAN (bez HTTPS)

- Varovanie môžeš **ignorovať** len ak dôveruješ celej sieti a nikto iný na Wi‑Fi neodpočúva.
- **Neodporúčané** pre verejný internet ani produkciu.

**Backend (budúce vylepšenie):** Session cookie `Secure` + `SameSite=Strict` až pri `X-Forwarded-Proto: https` – samo o sebe HTTP neopraví.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-009"></a>

## ISS-009 – Settings crash: `n.max is not a function`

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-009)

**Symptóm:** Po navigácii na `/settings` stránka spadne: `TypeError: n.max is not a function`.

**Príčina:** `zodFromRules.ts` volal `.max()` / `.min()` na `ZodOptional` (po `z.string().optional()`), nie na vnútorný `ZodString`.

**Oprava:** Reťazenie pravidiel na `z.string()` / `z.coerce.number()`, optional wrapper až na konci (`wrapOptional`).

**Overenie:** `npm test -- src/validation/zodFromRules.test.ts` + otvorenie `/settings` v admin.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-010"></a>

## ISS-010 – Vitest stderr: `act(...)` a Router future flags

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-010)

**Symptóm:** Pri `npm test` testy prechádzali, ale stderr obsahoval desiatky warningov:
`An update to … was not wrapped in act(...)` a React Router v7 future flag hints.

**Príčina:** `fireEvent` bez `waitFor` v `DeveloperUnlockGate.test.tsx` a `MediaManager.test.tsx`;
`MemoryRouter` bez `future={{ v7_startTransition, v7_relativeSplatPath }}`.

**Oprava (2.0.24,** `b9a740f`**):**

- `frontend/src/test/renderWithRouter.tsx` — spoločný wrapper s future flags.
- Testy prepísané na `fastUser` (`userEvent`) + `waitFor`.

**Overenie:** `npm test` — 130 testov OK, stderr čistý.

---


### Súvisiace odkazy

- [Commit `b9a740f`](https://github.com/techberode/paginiumcms-architecture/commit/b9a740f)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-011"></a>

## ISS-011 – ESLint warnings (technický dlh)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-011)

**Symptóm:** `npm run lint` — 0 errors, warnings hlavne `@typescript-eslint/no-explicit-any`
(`client.ts`, `useApi.ts`) a `react-refresh/only-export-components`.

**Stav:** Baseline `--max-warnings 65` v CI — prekročenie spôsobí fail (pozri [ISS-020](#iss-020)).
Po oprave hook deps (2.0.26): **57 warnings** — rezerva 8 slotov do limitu.

**Plán:** Postupné znižovanie od API vrstvy; cieľ ≤50 v ďalšej iterácii.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-012"></a>

## ISS-012 – CSRF middleware nezapojený (audit S3) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-012)

**Symptóm / riziko:** `CsrfProtectionManager` existoval a bol testovaný, ale **nebol nikde zapojený** — žiadny `CsrfMiddleware`, backend `X-CSRF-TOKEN` nevalidoval. Jediná ochrana bola `SameSite=Lax` cookie. Navyše FE `authApi.getCsrfToken()` bol definovaný, ale **nikde sa nevolal** → token sa reálne ani neposielal.

**Implementované riešenie (synchronizer-token, SPA-kompatibilné):**

- `backend/app/Http/Middleware/CsrfMiddleware.php` (nový) — vynucuje token na mutujúcich metódach (`POST/PUT/PATCH/DELETE`):
  - Token z hlavičky `X-CSRF-TOKEN` sa porovnáva so session tokenom cez `hash_equals` (nie jednorazovo → SPA ho používa opakovane).
  - **Exempt** (prefix): `/api/auth/login`, `/api/auth/register`, `/api/auth/reset-password`, `/api/auth/verify-reset-token`, `/api/auth/csrf-token`, `/api/auth/sso`, `/api/contact`, `/api/comments`, `/api/debug/client-event` (pre-auth alebo anonymné akcie bez privilégií).
  - `APP_ENV=testing` → no-op (ako WAF); logika pokrytá dedikovanými testami.
  - Chýbajúci/neplatný token → `403 { code: "csrf_invalid" }`.
- **Zapojenie:** globálny stack v `bootstrap/app.php` (za `FirewallMiddleware`).
- **Frontend:**
  - `AuthContext` bootstrap vopred načíta token (`authApi.getCsrfToken()`) → prvý mutujúci request neskončí na 403.
  - `client.ts` — **self-healing**: pri `403 { code: "csrf_invalid" }` raz dofetchne čerstvý token a zopakuje pôvodný request. Nový `refreshCsrfToken()`.

**Prečo je to bezpečné:** cross-site útočník nedokáže prečítať token (SOP + CORS na `/csrf-token`) ani nastaviť custom hlavičku bez schváleného preflightu.

**Overenie:** PHPStan L8 0 chýb; PHPUnit **726** testov / 0 fail (`CsrfMiddlewareTest` – 6 testov); FE `tsc` clean, ESLint 0 errors, `client.security.test.ts` OK.

**Súbory:** `backend/app/Http/Middleware/CsrfMiddleware.php`, `backend/bootstrap/app.php`, `frontend/src/api/client.ts`, `frontend/src/context/AuthContext.tsx`. **Súvisí s:** audit S3 (`AUDIT_REPORT.md`, `SECURITY_ISSUES.md`).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)


---

<a id="feature-login-background"></a>

## Login pozadie — upload z médií / disku (2.0.46)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#feature-login-background)

**Symptóm:** V **Nastavenia → Stránka → Prihlásenie a registrácia** bolo pole `backgroundImageUrl` len textové URL — administrátor nemohol vybrať obrázok z knižnice médií ani nahrať súbor z lokálneho disku.

**Implementované riešenie (2.0.46):**

- Nový FE komponent `LoginBackgroundImagePicker` — URL pole + **Vybrať z médií** (`MediaPickerModal`, `urlFormat: storage`) + **Nahrať z disku** (`POST /api/media/upload`)
- Náhľad pozadia priamo v nastaveniach; tlačidlo odstránenia
- `useAuthBranding` — správne rozlíšenie `/storage/…` a `media/…` ciest pre CSS pozadie na `/login` a `/register`
- i18n SK/EN pre picker; help text v `SettingsSchema`

**Poznámka (2.0.46 fix):** Tlačidlá zobrazovali surové kľúče `settings.login.backgroundPicker.*` — správna cesta je `settings.fields.login.backgroundPicker.*` (modul `settings` → vetva `fields.login`).

**Overenie:** Nastavenia → login skupina → vybrať/nahrať obrázok → uložiť → overiť na `/login`. Vitest: `LoginBackgroundImagePicker.test.tsx`, `settings.test.ts`.

**Súvisí s:** It.19 auth UX (2.0.45), `uploadSecurity` validácia typu súboru pri lokálnom uploade.

---

---

<a id="iss-013"></a>

## ISS-013 – ntfy bez autentifikácie (privátne topicy)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-013)

**Symptóm:** `NtfyAdapter` posielal POST bez `Authorization` — zlyhá na ACL topic / self-hosted ntfy.

**Oprava (It.47):**

- Settings: `ntfyAuthMode` (`none` | `token` | `basic`), `ntfyAccessToken`, `ntfyUsername`, `ntfyPassword`
- `NtfyAdapter::buildAuthHeaders()` — Bearer alebo Basic
- `POST /api/admin/notifications/test-connector` — validácia + test odoslania
- Admin `/notifications` — badge Auth OK / Chýba auth, tlačidlo Verify auth

**Overenie:** PHPUnit `NtfyAdapterTest`, `NotificationFactoryTest`; Settings → Connectors → ntfy token → Verify auth.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-014"></a>

## ISS-014 – CORS dev wildcardy (audit nález S6)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-014)

**Symptóm:** Mimo produkcie CORS povoľuje `localhost:*`, `192.168.*`, `10.*`, `172.*` s `credentials: true`.

**Riziko:** Ak server beží s `APP_ENV` ≠ `production`, širšie CORS ostáva aktívne.

**Odporúčanie:** Pri nasadení na `mail.webland.fun` explicitne `APP_ENV=production`; overiť v health/deploy checkliste.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-015"></a>

## ISS-015 – PHPUnit: rate limit, maintenance a OTP persistencia

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-015)

**CI job:** `backend` → step **PHPUnit**

**Symptóm:** Náhodné / opakované zlyhania integračných testov:

- HTTP **429** (rate limit) pri sérii requestov v jednom teste
- HTTP **503** (maintenance mode) ak predchádzajúci test nechal `maintenanceMode=true` v `settings.json`
- OTP workflow testy padali kvôli neúplnému zápisu do `otp-challenges.json` alebo zdieľanej persistencii medzi testami

**Navrhované riešenie:**

1. V `APP_ENV=testing` obísť globálny rate limit (rovnako ako pre login limiter)
2. V `TestCase::setUp()` resetovať kritické settings skupiny a mazať OTP / cache stores
3. `SettingsRepository::setGroup()` validovať len odoslané polia, nie celú schému pri čiastočnom update

**Implementované riešenie** (`f54361d`):

- `RateLimitMiddleware` — early return pri `APP_ENV=testing`
- `TestCase` — `resetPersistedTestSettings()`, `clearOtpChallengeStore()`, `clearRateLimitCache()`, `clearApplicationCache()`
- `OtpChallengeStore` — spoľahlivejší zápis cez flock
- `SettingsRepository` — partial group update bez false-positive validácie

**Overenie:** `./vendor/bin/phpunit` — 587 testov OK, opakovateľné behy bez 429/503.

---


### Súvisiace odkazy

- [Commit `f54361d`](https://github.com/techberode/paginiumcms-architecture/commit/f54361d)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-016"></a>

## ISS-016 – PHPStan: nezhoda `phpVersion` s Composer

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-016)

**CI job:** `backend` → step **PHPStan level 8**

**Symptóm:** PHPStan padal alebo hlásil nekompatibilné správanie; v `phpstan.neon` bolo `phpVersion: 80500`, zatiaľ čo `composer.json` vyžaduje `"php": "^8.4"` (minimálna podporovaná verzia 8.4, nie 8.5).

**Navrhované riešenie:** Zosúladiť PHPStan target s Composer floor — nastaviť `phpVersion: 80400`, aby analýza zodpovedala najstaršej podporovanej verzii v produkcii/CI.

**Implementované riešenie** (`d5c2660`):

- `phpstan.neon` — `phpVersion: 80500` → `80400`

**Overenie:** `./vendor/bin/phpstan analyse backend --level=8` — 0 chýb.

---


### Súvisiace odkazy

- [Commit `d5c2660`](https://github.com/techberode/paginiumcms-architecture/commit/d5c2660)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-017"></a>

## ISS-017 – PHPStan: `match.alwaysTrue` v bulk akciách

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-017)

**CI job:** `backend` → step **PHPStan level 8**

**Symptóm:** PHPStan hlásil `match.alwaysTrue` v nových bulk metódach:

- `MessageController::bulkAction()` — vetva `match ($action)` mala nerealizovateľné vetvy (pred `$action` už prebehla validácia `in_array(...)`)
- `CommentsController::bulkAction()` — rovnaký pattern

**Navrhované riešenie:** Nahradiť `match` explicitným `if / elseif` reťazcom po validácii `$action`, alebo presunúť `match` pred validáciu s default vetvou. Preferované: `if / elseif` — čitateľnejšie pri zúženom type po guard clause.

**Implementované riešenie** (`d5c2660`):

- `MessageController::bulkAction()` — `read` / `processed` / `archive` / `delete` cez `if / elseif`
- `CommentsController::bulkAction()` — rovnaký refactor

**Overenie:** PHPStan L8 čistý; bulk API testované cez admin inbox UX (Správy / Komentáre).

---


### Súvisiace odkazy

- [Commit `d5c2660`](https://github.com/techberode/paginiumcms-architecture/commit/d5c2660)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-018"></a>

## ISS-018 – PHPStan: `TrashController::downloadBackup` a `fopen`

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-018)

**CI job:** `backend` → step **PHPStan level 8**

**Symptóm:** PHPStan `argument.type` — `Stream` konštruktor očakáva `resource`, ale `fopen(...)` môže vrátiť `false`; chýbala ochrana pred zlyhaním otvorenia súboru.

**Navrhované riešenie:** Skontrolovať návratovú hodnotu `fopen`; pri `false` vrátiť JSON chybu 500 namiesto pasovania do `Stream`.

**Implementované riešenie** (`d5c2660`):

```php
$handle = fopen($path, 'rb');
if ($handle === false) {
    return $this->json->error($response, 'Nepodarilo sa otvoriť zálohu', 500);
}
```

**Overenie:** PHPStan L8; `TrashControllerTest` pokrýva download flow.

---


### Súvisiace odkazy

- [Commit `d5c2660`](https://github.com/techberode/paginiumcms-architecture/commit/d5c2660)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-019"></a>

## ISS-019 – Frontend CI: `tsc --noEmit` strict errors

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-019)

**CI job:** `frontend` → step **TypeScript type-check** (`npm run type-check`)

**Symptóm:** Po release 2.0.25 CI padalo na strict TypeScript:


| Súbor                | Chyba  | Správa                                                         |
| -------------------- | ------ | -------------------------------------------------------------- |
| `api/comments.ts`    | TS2352 | Nebezpečný cast `res as Record<string, unknown>` pri OTP vetve |
| `api/workflows.ts`   | TS2352 | Priamy cast odpovede POST `/workflows/otp/verify`              |
| `MarkdownEditor.tsx` | TS2352 | Cast publish odpovede na `Record<string, unknown>`             |
| `BackupManager.tsx`  | TS6133 | Nepoužitá premenná `completedBackups`                          |
| `Navbar.tsx`         | TS2322 | `active` môže byť `boolean                                     |


**Navrhované riešenie:**

- OTP a workflow odpovede zužiť cez `as unknown as { ... }` + runtime `typeof` guardy
- Odstrániť mŕtvy kód; pre optional chain doplniť `?? false`

**Implementované riešenie** (`5398b48`):

- `comments.ts` — `body` cez `unknown`, `requires_otp === true`, `challenge_id` len ak `string`
- `workflows.ts` — typed intermediate object, `Comment` import, `parseOtpPending(res as unknown as ...)`
- `MarkdownEditor.tsx` — `response as unknown as Record<string, unknown>` pre `extractOtpPending`
- `BackupManager.tsx` — odstránená nepoužitá `completedBackups`
- `Navbar.tsx` — `?? false` na `active` boolean

**Overenie:** `cd frontend && npm run type-check` — exit 0; CI frontend job green.

---


### Súvisiace odkazy

- [Commit `5398b48`](https://github.com/techberode/paginiumcms-architecture/commit/5398b48)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-020"></a>

## ISS-020 – ESLint: prekročenie `--max-warnings 65`

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-020)

**CI job:** `frontend` → step **ESLint** (`npm run lint`)

**Symptóm:** CI padalo s exit code 1 pri **68 warnings** (limit v `package.json`: `--max-warnings 65`).
Konkrétny trigger v logu: `react-hooks/exhaustive-deps` — `useMemo` v `useToast.ts` bez
závislosti `notification`; ďalšie hook deps v komponentoch po release 2.0.26 (SettingsView,
CodeEditor, AuditTrail, MediaManager, …).

**Navrhované riešenie:**

1. Doplniť chýbajúce závislosti v `useMemo` / `useEffect` / `useCallback` (preferované)
2. Alebo stabilizovať referencie cez primitívy / `useCallback` namiesto inline loaderov
3. Neznižovať limit v CI bez vedomej zmeny politiky (errors fail, baseline warnings)

**Implementované riešenie** (`d24f0e0`):

- `useToast.ts` — `[notification]` v `useMemo`
- `SettingsView`, `CodeEditor`, `VersionHistory`, `AuditTrail` — loadery cez `useCallback`, effects s plnými deps
- `MediaManager`, `GitHubSyncPanel`, `MediaPreviewLightbox`, `PublicSiteContext`, `useBulkSelection` — opravené / zdokumentované deps

**Overenie:** `cd frontend && npm run lint` — **57 warnings**, exit 0; `react-hooks/exhaustive-deps` = 0.

---


### Súvisiace odkazy

- [Commit `d24f0e0`](https://github.com/techberode/paginiumcms-architecture/commit/d24f0e0)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-021"></a>

## ISS-021 – PHPStan: redundantné `is_array()` v ApplicationLogReader

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-021)

**CI job:** `backend` → step **PHPStan level 8**

**Symptóm:** PHPStan `function.alreadyNarrowedType` — 2 chyby v
`ApplicationLogReader.php`:

- riadok 125: `is_array($entry)` pri `$entry` už typovanom ako `array<string, mixed>` z `readDirectory()`
- riadok 167: `is_array($decoded)` pri `JsonHelper::decode()` s návratovým typom `array<int|string, mixed>`

**Navrhované riešenie:** Odstrániť redundantné guardy; runtime validáciu nechať len tam, kde je
premenná typovaná ako `mixed` (napr. položky v dekódovanom JSON poli).

**Implementované riešenie** (`d24f0e0`):

- `loadAll()` — priamy zápis `$entry['source']` bez `is_array($entry)`
- `readDirectory()` — po `JsonHelper::decode()` priamo `foreach`; `is_array($entry)` v slučke
ponechané (položka je `mixed`)

**Overenie:** `./vendor/bin/phpstan analyse backend --level=8` — 0 chýb.

---


### Súvisiace odkazy

- [Commit `d24f0e0`](https://github.com/techberode/paginiumcms-architecture/commit/d24f0e0)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-022"></a>

## ISS-022 – Vitest: `MediaManager.test.tsx` krehké asercie

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-022)

**CI job:** `frontend` → step **Vitest** (`npm test`)

**Symptóm:** 3 testy padali s `toBeInTheDocument()` timeoutom:

- `renders media grid after load` — `findByText('Hero')`
- `filters items by search query` — `findByText('Hero')` / `queryByText('Hero')`
- `saves metadata edits in list view mode via modal` — `findByText('Hero')`

**Príčiny:**

1. **Krehké textové asercie** — `Hero`, `hero.png`, `Alt: Hero banner` sa v `MediaManager` vykresľujú
  na viacerých miestach / režimoch (`MediaCard` vs `MediaListTable`), text môže byť skrátený alebo duplicitný.
2. **Nestabilný mock** `useToast` — po [ISS-020](#iss-020) (`toast` v `loadMedia` deps) mock vracal nový objekt
  pri každom renderi → nekonečný reload → spinner, obsah sa nikdy neobjavil v teste.

**Navrhované riešenie:**

1. Assertovať cez stabilné **role/label** selektory (`Preview hero.png`, `Select hero.png`, dialog)
2. V test mockoch vracať **hoisted stabilnú** referenciu pre `useToast` (rovnako ako pri iných hook mockoch)

**Implementované riešenie:**

- `MediaManager.test.tsx` — `findByRole('button', { name: /Preview hero\.png/i })` v preview režime;
`findByRole('checkbox', { name: /Select hero\.png/i })` v list režime; filter test cez `queryByRole`
- `mocks.toast` hoisted — stabilná referencia pre `useToast` mock

**Overenie:** `cd frontend && npm test -- src/components/backend/MediaManager.test.tsx` — 5/5 OK.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-023"></a>

## ISS-023 – PHPUnit: flaky admin draft search

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-023)

**CI job:** `backend` → step **PHPUnit**

**Symptóm:** Občasné zlyhanie `SearchControllerTest::testAdminSearchIncludesDraftPages`:

```
Failed asserting that an array contains 'seo-test-<uniqid>'.
```

**Príčiny:**

1. **Nestabilný dotaz** — test hľadal podľa `uniqid()` slug-u; index matchuje title/slug/excerpt/tags, nie náhodné slug suffixy vždy konzistentne.
2. **Stratený slug vo front matter** — `setSlug()` pred `setFrontMatter([...])` sa prepísal; index mal title, ale prázdny slug.

**Implementované riešenie:**

- Deterministický `searchToken` (`draftpalettekeyword`) v dotaze aj v title/body.
- Slug v `setFrontMatter(['slug' => $slug, ...])` (vzor ako `TrashControllerTest`).
- Commit `3fd8323`.

**Overenie:** `./vendor/bin/phpunit backend/tests/Http/Controllers/Content/SearchControllerTest.php` — 4/4 OK.

---


### Súvisiace odkazy

- [Commit `3fd8323`](https://github.com/techberode/paginiumcms-architecture/commit/3fd8323)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-024"></a>

## ISS-024 – AuthMiddleware → 500 na auth trasách

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-024)

**Symptóm:** Po deployi padajú chránené endpointy (**500**), napr. `POST /api/admin/settings/monitoring`, `POST /api/debug/client-event`. PHP log:

```
AuthMiddleware::__construct(): Argument #2 ($session) must be of type SessionManager, AuthenticationInterface given
```

**Príčina:** Do `AuthMiddleware` bol pridaný druhý parameter (`SessionManager`), ale DI v `bootstrap/app.php` stále injektoval len `AuthenticationInterface`. Každý request cez auth middleware spadol ešte pred controllerom.

**Implementované riešenie:**

- `touchSession()` presunuté do `AuthenticationManager` (implementácia `AuthenticationInterface`).
- `AuthMiddleware` zostáva s **jedným** argumentom — kompatibilné s existujúcim DI.

**Overenie:** PHPUnit `AuthControllerTest` + manuálne volanie chránenej trasy po prihlásení → **200**, nie 500.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-025"></a>

## ISS-025 – Odhlásenie počas editácie / pri uložení

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-025)

**Symptóm:** Používateľ sa prihlási, začne editovať článok alebo nastavenia a po uložení (alebo po chvíli) ho frontend presmeruje na `/login`. Nie je to nečinnosť — deje sa to pri aktívnej práci.

**Príčiny (kombinácia):**

1. **Krátka session** — `SESSION_LIFETIME=120` (kód vynucuje minimum 300 s, stále príliš málo na CMS).
2. **Viacero inštancií session** — viac `SessionManager` objektov v jednom requeste mohlo session zničiť alebo neobnoviť.
3. **IP binding za nginx** — `SESSION_STRICT=true` + proxy mení vnímanú IP → `ensureValid()` zruší session.
4. **Frontend** — axios interceptor pri **každom** 401 okamžite presmeruje na `/login` (aj pri lock/draft/2FA).

**Implementované riešenie:**

- `SecureSessionManager` — singleton v DI, lazy `ensureValid()`, IP cez `ClientIpResolver` + `TRUSTED_PROXIES`.
- `AuthenticationManager::touchSession()` — volané z `AuthMiddleware` pri každom auth requeste.
- `bootstrap/session.php` — dev default 8 h, `session.cookie_path=/`, komentáre k env premenným.
- **Frontend:** `AuthContext` keepalive každé 4 min (`probeSession`), `client.ts` nepresmeruje pri `/api/auth/me`, locks, drafts, `requires_two_factor`.

**Ops odporúčanie (LAN** `.26` **→** `.20`**):**

```env
SESSION_LIFETIME=28800
SESSION_STRICT=false
SESSION_USE_STRICT_MODE=true
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

Reštart PHP, vymazať cookies, znova prihlásiť.

**Overenie:** Network tab — po `POST /api/auth/login` (200 + `Set-Cookie`) nasleduje `GET /api/auth/me` (200); pri save ostáva session.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-026"></a>

## ISS-026 – Zámena SESSION_USE_STRICT_MODE ↔ SESSION_STRICT

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-026)

**Symptóm:** Admin nastavil `SESSION_USE_STRICT_MODE=false` v `.env` a očakával vypnutie „strict session“, no stále dochádzalo k odhláseniu.

**Príčina:** Dve **rôzne** premenné:


| Premenná                  | Čo riadi                                                              |
| ------------------------- | --------------------------------------------------------------------- |
| `SESSION_USE_STRICT_MODE` | PHP ini `session.use_strict_mode` (odmietnutie neplatného session ID) |
| `SESSION_STRICT`          | Paginium **IP/UA binding** v `SecureSessionManager`                   |


**Implementované riešenie:** Komentáre v `.env.example`, tabuľka v [DEV.md](deploy/DEV.md#troubleshooting), default `SESSION_STRICT=false`.

**Overenie:** Pri LAN deployi s proxy nechať `SESSION_STRICT=false`; IP binding zapínať len pri známej stabilnej IP topológii.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-027"></a>

## ISS-027 – Debug log: falošné login 401 z PHPUnit

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-027)

**Symptóm:** V `storage/logs/debug/*.log` desiatky riadkov:

```json
{"event":"http.request","context":{"method":"POST","path":"/api/auth/login"}}
{"event":"http.response","context":{"status":401,...}}
```

Vyzerá to ako opakované zlyhané prihlásenie v prehliadači.

**Príčina:** PHPUnit HTTP testy bežia s `APP_ENV=testing` a `sapi=cli`. `DebugEventLogger` zapisoval aj test suite (lockout testy volajú login s nesprávnym heslom).

**Implementované riešenie:** `DebugEventLogger::isEnabled()` vracia `false`, keď `APP_ENV=testing`.

**Overenie:** Po `./vendor/bin/phpunit` sa do debug logu nepridávajú nové riadky (pri zapnutom `APP_DEBUG` v dev).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-028"></a>

## ISS-028 – Frontend build: JSX v SettingsView

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-028)

**Symptóm:** `npm run build:prod` zlyhá:

```
Adjacent JSX elements must be wrapped in an enclosing tag.
SettingsView.tsx:162
```

**Príčina:** Pri pridávaní `CacheManagerPanel` sa rozbila JSX štruktúra karty 2FA (predčasné `</div>`, `Link` mimo karty).

**Implementované riešenie:** Opravená štruktúra karty 2FA (`card-body` + tlačidlo v jednom bloku).

**Overenie:** `cd frontend && npm run build:prod` → OK, `verify-dist-api-url: OK`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-029"></a>

## ISS-029 – Login loop (krátke prihlásenie → späť na login)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-029)

**Symptóm:** Prihlásenie prejde, dashboard sa na chvíľu zobrazí, potom okamžitý návrat na `/login`. Debug log môže ukazovať 401 na `/api/auth/me` alebo chránených trasách.

**Príčiny:** Rovnaká rodina ako [ISS-025](#iss-025) — session cookie sa neudrží medzi requestmi (proxy, IP binding, expirácia) alebo 401 redirect z FE.

**Implementované riešenie (2.0.29):** Súhrn [ISS-025](#iss-025) + `AuthController` po logine overí `isAuthenticated()`; `authApi.probeSession()` rozlišuje expirovanú session vs sieťovú chybu.

**Doplnenie (2.0.30):** Ak loop pretrvával pri **2FA / nových používateľoch**, pozri **[ISS-030](#iss-030)–[ISS-034](#iss-034)** (setup vs login TOTP, user bez secretu, FE hard redirect).

**Diagnostika (DevTools → Network):**

1. `POST /api/auth/login` → **200** + `Set-Cookie: PHPSESSID`
2. `GET /api/auth/me` → **200** (nie 401)
3. Pri páde: ktorý request vráti **401**? (me, settings, content…)

**Lockout:** Ak 429/401 po viacerých pokusoch → `php backend/bin/console security:clear-lockouts`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-030"></a>

## ISS-030 – 2FA setup: QR kód zmizne → TOTP login

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-030)

**Symptóm:** Po kliknutí „Začať nastavenie 2FA“ sa na `/account/security` na chvíľu zobrazí QR, potom okamžitý preskok na login obrazovku s požiadavkou **TOTP kódu** (bez možnosti naskenovať QR).

**Príčina:** Frontend po `enable()` volal `refreshUser()`, ktorý pri `twoFactorEnabled && !verified` nastavil `pendingTwoFactor=true` — rovnaký stav ako pri **login TOTP**, nie pri **prvom nastavení**. `ProtectedRoute` presmeroval na `/login`.

**Implementované riešenie:**

- API `/api/auth/2fa/status` → pole `setup_pending` (`enabled && verifiedAt === null`).
- `AuthContext`: `pendingTwoFactor` len ak user **už raz dokončil** setup (`verifiedAt !== null`).
- `TwoFactorMiddleware` + `AuthController::login` — TOTP až po prvom úspešnom overení QR (nie počas setupu).
- `TwoFactorSettings` — po enable nevolá `refreshUser()` (QR ostáva).
- `ProtectedRoute` — pri setup presmeruje na `/account/security`, nie `/login`.
- Banner v admin layoute: „Dokončite nastavenie 2FA“.

**Overenie:** Login → `/account/security` → Enable → QR zostane → scan → verify → dashboard bez presmerovania na login TOTP.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-031"></a>

## ISS-031 – Nový staff user: 2FA zapnuté bez tajného kľúča

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-031)

**Symptóm:** Admin vytvorí používateľa s vynúteným 2FA. Prihlásenie heslom → hneď TOTP krok, ale autentifikátor nemá čo skenovať. `POST /api/auth/2fa/enable` vráti **400** „2FA je už aktivovaná“.

**Príčina:** `UserController` pri `requireTwoFactorStaff` nastavil `twoFactorEnabled=true` **bez** generovania `twoFactorSecret`.

**Implementované riešenie:**

- Pri vytvorení/úprave staff usera sa **neprepína** `twoFactorEnabled` automaticky — flag vznikne až pri `POST /api/auth/2fa/enable`.
- `TwoFactorController::enable()` povolí reprovisioning, kým `twoFactorVerifiedAt === null`.

**Núdzová oprava existujúceho účtu** (na disku pred deployom):

```json
"twoFactorEnabled": false,
"twoFactorSecret": null,
"twoFactorVerifiedAt": null
```

v `backend/storage/app/users/{id}.json`, potom re-login → `/account/security`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-032"></a>

## ISS-032 – `twoFactorVerifiedAt` sa neukladalo

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-032)

**Symptóm:** Po úspešnom overení QR funguje session, ale po **odhlásení / novom login-e** systém stále správa 2FA ako nedokončenú alebo vyžaduje TOTP nesprávne.

**Príčina:** `UserRepository::extract()` / `hydrate()` neobsahovali pole `twoFactorVerifiedAt` — timestamp sa stratil pri každom `save()`.

**Implementované riešenie:** Pole pridané do serializácie flat-file user JSON.

**Overenie:** Po verify 2FA → logout → login → `requires_two_factor: true` (TOTP krok), nie setup QR.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-033"></a>

## ISS-033 – Frontend 401 hard redirect (dvojitý login)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-033)

**Symptóm:** Po úspešnom prihlásení krátky flash dashboardu, potom návrat na login — používateľ musí zadať **heslo znova** (full page reload).

**Príčina:** Axios interceptor pri 401 volal `window.location.href = '/login'`. Pri 2FA alebo transientnom 401 to zahodilo React stav a cookie kontext.

**Implementované riešenie:**

- Odstránený hard redirect.
- Custom eventy `paginium:totp-required` / `paginium:auth-expired` → `AuthContext` nastaví `pendingTwoFactor` alebo zavolá `refreshUser()`.
- React Router (`ProtectedRoute`) rieši navigáciu bez reloadu.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-034"></a>

## ISS-034 – Dev: ovládanie TOTP cez `.env`

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-034)

**Symptóm:** Počas vývoja/LAN testu je TOTP nepraktické; v `.env` nebol prepínač.

**Implementované riešenie:** `TwoFactorPolicy` + `.env`:

```env
APP_ENV=development
TWO_FACTOR_REQUIRED=false
```

Platí len v `development|local|testing` — na **produkcii** (`APP_ENV=production`) sa 2FA vždy vyžaduje.

**Alternatíva bez** `.env`**:** Nastavenia → Bezpečnosť → vypnúť „Vynútiť 2FA pre editorov a adminov“.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-035"></a>

## ISS-035 – PHPStan: ClientIpResolver mŕtvy null coalesce

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-035)

**Symptóm:** CI job „PHPStan level 8“ padá:

```
Offset 0 on non-empty-list<string> on left side of ?? always exists
ClientIpResolver.php — $parts[0] ?? $remoteAddr
```

**Príčina:** `explode()` vždy vráti neprázdne pole; `?? $remoteAddr` je mŕtvy kód. Neplatná IP sa rieši cez `filter_var()` na ďalšom riadku.

**Implementované riešenie:** `$clientIp = $parts[0];` (commit hotfix po 2.0.29).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-036"></a>

## ISS-036 – Frontend type-check: 2FA API shape + `setUser` (CI)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-036)

**Symptóm:** Po pushi release **2.0.30** (`f5061e6`) CI job **frontend → TypeScript type-check** padá:

```
src/api/auth.ts(182,35) — setup_pending does not exist on ApiResponse<…>
src/components/auth/TwoFactorSettings.tsx(65,9) — Cannot find name 'setUser'
src/components/auth/TwoFactorSettings.test.tsx — mock missing setupPending
```

**CI run:** [actions/runs/29705295632](https://github.com/techberode/paginiumcms-architecture/actions/runs/29705295632) · job `88241154494` · ref `f5061e6`.

**Príčina:**

1. `getStatus()` čítalo `res.setup_pending` priamo z `ApiResponse`, hoci generické pole `setup_pending` patrí do `data` (alebo flat payloadu z backendu) — typová nezhoda po pridaní `setup_pending` do 2FA status endpointu.
2. `TwoFactorSettings` volalo `setUser()` bez importu z auth kontextu (malé byť `updateUser` z `useAuth()`).
3. **Testy** neobsahovali povinné `setupPending` v mock odpovedi `getStatus()`.

**Implementované riešenie (hotfix** `3fbc595`**):**


| Súbor                                                     | Zmena                                                                         |
| --------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `frontend/src/api/auth.ts`                                | `const payload = res.data ?? res`; mapovanie `setup_pending` → `setupPending` |
| `frontend/src/api/client.ts`                              | `setup_pending?: boolean` na `ApiResponse` (flat backend odpovede)            |
| `frontend/src/components/auth/TwoFactorSettings.tsx`      | `updateUser({ ...user, twoFactorEnabled: true })` namiesto `setUser`          |
| `frontend/src/components/auth/TwoFactorSettings.test.tsx` | mocky + `updateUser` v `useAuth`                                              |


**Konvencia:** backend/API DTO = `setup_pending`; frontend doména = `setupPending` (konverzia len v `auth.ts`).

**Overenie:** `cd frontend && npm run type-check && npm test -- --run src/components/auth/TwoFactorSettings.test.tsx` — exit 0.

---


### Súvisiace odkazy

- [Commit `f5061e6`](https://github.com/techberode/paginiumcms-architecture/commit/f5061e6)
- [Commit `3fbc595`](https://github.com/techberode/paginiumcms-architecture/commit/3fbc595)
- [GitHub Actions run](https://github.com/techberode/paginiumcms-architecture/actions/runs/29705295632)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-037"></a>

## ISS-037 – Frontend type-check: nepoužitý React import v teste (CI)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-037)

**Symptóm:** Po pushi fixu admin deep linkov (`fbb574b`) CI job **frontend → TypeScript type-check** padá:

```
src/components/backend/SettingsView.test.tsx(4,1): error TS6133: 'React' is declared but its value is never read.
```

**Príčina:** Nový test `SettingsView.test.tsx` importoval `React` kvôli JSX, ale projekt používa moderný JSX transform (`react-jsx`) — import nie je potrebný a strict `noUnusedLocals` ho odmietne.

**Implementované riešenie (hotfix** `64cc894`**):**

- Odstránený riadok `import React from 'react';` z `SettingsView.test.tsx`.

**Overenie:** `cd frontend && npm run type-check` — exit 0.

---


### Súvisiace odkazy

- [Commit `fbb574b`](https://github.com/techberode/paginiumcms-architecture/commit/fbb574b)
- [Commit `64cc894`](https://github.com/techberode/paginiumcms-architecture/commit/64cc894)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-038"></a>

## ISS-038 – PHPUnit It.44d: index filtre tag / author / date (CI)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-038)

**Symptóm:** Po pushi It.44d (`743e922` / `05a4800`) CI job **backend → PHPUnit** padá v `ContentRepositoryTest.php`:


| Test                                                 | Riadok | Chyba                                                             |
| ---------------------------------------------------- | ------ | ----------------------------------------------------------------- |
| `testFindArticlesPaginatedFiltersByTagAuthorAndDate` | 172    | `Failed asserting that 0 is identical to 1` (date range filter)   |
| `testListDistinctTagsAndCountIndexed`                | 181    | Očakávané `['news', 'php']`, skutočné `['hidden', 'news', 'php']` |


**Príčiny:**

1. **Date filter** — Symfony YAML parsuje `date: 2024-02-10` ako **unix timestamp (**`int`**)**, nie string ani `DateTime`. `ContentIndexEntry::normalizeIndexedDate()` ignoroval integer → `createdAt` spadol na `modifiedAt` (dnešný dátum) a mimo rozsahu `date_from` / `date_to`.
2. **Distinct tags** — `listDistinctTags()` a `countIndexed()` volali `applyIndexFilters()` **bez filtra** `status` → tag `hidden` z draft článku sa počítal medzi publikované.

**Navrhované riešenie:**

- Normalizovať dátumy konzistentne na `Y-m-d` pri indexovaní aj filtrovaní (`DateTimeInterface`, `int` timestamp, string).
- Aplikovať `status` (a ostatné filtre) rovnako v `query()`, `listDistinctTags()` a `countIndexed()`.
- Tagy normalizovať cez spoločný helper (trim, prázdne vyhodiť).

**Implementované riešenie (**`54b013c`**):**

- `ContentIndexEntry::normalizeIndexedDate()` — podpora `DateTimeInterface`, `int`/`float` timestamp, ISO string.
- `ContentIndexEntry::normalizeTags()` — array aj reťazec (`news, php` / `[news, php]`).
- `ContentIndexService::applyIndexFilters()` — filter `status` + dátum cez `normalizeIndexedDate()` na `createdAt`.

**Súbory:** `ContentIndexEntry.php`, `ContentIndexService.php`, testy v `ContentRepositoryTest.php`.

**Overenie:**

```bash
php vendor/bin/phpunit --filter ContentRepositoryTest::testFindArticlesPaginatedFiltersByTagAuthorAndDate
php vendor/bin/phpunit --filter ContentRepositoryTest::testListDistinctTagsAndCountIndexed
```

---


### Súvisiace odkazy

- [Commit `743e922`](https://github.com/techberode/paginiumcms-architecture/commit/743e922)
- [Commit `05a4800`](https://github.com/techberode/paginiumcms-architecture/commit/05a4800)
- [Commit `54b013c`](https://github.com/techberode/paginiumcms-architecture/commit/54b013c)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-039"></a>

## ISS-039 – PHPUnit `LogWriterTest`: vfs súbor + corrupt JSON (CI)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-039)

**Symptóm:** CI na commite `743e922` — `LogWriterTest.php`:


| Test                                  | Riadok | Chyba                                                                                          |
| ------------------------------------- | ------ | ---------------------------------------------------------------------------------------------- |
| `testWrite`                           | 44     | `Failed asserting that file "vfs://storage/logs/app/YYYY-MM-DD.json" exists`                   |
| `testWriteMultipleEntries`            | 60     | rovnaké                                                                                        |
| `testWriteRecoversFromCorruptLogFile` | 68/77  | `RuntimeException` / warning z `FileHelper.php:36` (`file_get_contents` na neexistujúci súbor) |


**Príčiny:**

1. `flock(LOCK_EX)` **na** `vfsStream` — zápis cez `fopen('c+')` + zámok zlyhá alebo nevytvorí súbor v PHPUnit vfs.
2. `realpath()` **na** `vfs://` — cesta sa rozbila pred zápisom.
3. **Salvage corrupt JSON** — binary search predpokladal monotónny JSON prefix; pri useknutom poli to vrátilo `[]`.
4. `FileHelper::read()` — volanie `file_get_contents` bez kontroly existencie súboru → warning v testoch.

**Navrhované riešenie:**

- Pred zápisom vytvoriť parent adresár (`mkdir` rekurzívne).
- Na vfs testoch obísť `flock` a použiť priamy `readLogFile` + `writeLogFile`.
- Pri corrupt JSON skenovať od konca k poslednému `]` a dekódovať platný prefix.
- V `FileHelper::read()` vrátiť `''` ak súbor neexistuje (nepadať na warning).

**Implementované riešenie (**`54b013c`**):**

- `LogWriter` — vetva `vfs://` bez `flock`; produkcia ostáva s `flock(LOCK_EX)`.
- `LogWriter::salvageCorruptLogPayload()` — scan posledného `]` namiesto binary search.
- `LogWriter::ensureStorageDirectory()` pred zápisom.
- `LogWriter::readLogFile()` — guard na `is_file` + prázdna odpoveď.
- Test `testWriteRecoversFromCorruptLogFile` — bez `glob()` na vfs (vfsStream nepodporuje spoľahlivo).

**Poznámka:** Guard v `FileHelper::read()` aplikovaný — pri chýbajúcom/nečitateľnom súbore vráti `''` bez PHP warning.

**Súbory:** `LogWriter.php`, `LogWriterTest.php`.

**Overenie:** `php vendor/bin/phpunit --filter LogWriterTest`

---


### Súvisiace odkazy

- [Commit `743e922`](https://github.com/techberode/paginiumcms-architecture/commit/743e922)
- [Commit `54b013c`](https://github.com/techberode/paginiumcms-architecture/commit/54b013c)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-040"></a>

## ISS-040 – Corrupt HTTP access log → globálne API 500 (produkcia)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-040)

**Symptóm:** Po nasadení `743e922` verejný web a admin konzola — **všetky** requesty `500`:

```
GET /api/seo/page/home 500
GET /api/settings/public 500
GET /api/auth/me 500
POST /api/debug/client-event 500
```

PHP log:

```
Uncaught JsonException: Syntax error
  FileHelper.php:18 (JsonHelper::decode)
  LogWriter.php → AccessLogService → RequestLoggingMiddleware
```

**Príčina:** Súbor `backend/app/storage/logs/app/YYYY-MM-DD.json` mal **useknutý/neplatný JSON** (~4 MB, platný prefix + garbage suffix). Každý HTTP request volal middleware, ktorý pri zápise logu **čítal corrupt súbor** → `JsonException` → 500 na celej aplikácii.

**Navrhované riešenie:**

- `LogWriter` — atomic write s `flock`, pri corrupt JSON salvage + backup (`.corrupt-*`).
- `RequestLoggingMiddleware` — logging nikdy nesmie shodiť response (try/catch okolo `logRequest`).

**Implementované riešenie (**`743e922`**, doplnené** `54b013c`**):**

- `LogWriter::decodeLogPayload()` — backup corrupt súboru, salvage platného prefixu, pokračovanie s prázdnym/zachráneným poľom.
- `RequestLoggingMiddleware::safeLogRequest()` — chyba logovania sa nepropaguje.
- Salvage algoritmus vylepšený v `54b013c` (scan `]`, pozri [ISS-039](#iss-039)).

**Okamžitá náprava na serveri (ak API stále padá):**

```bash
mv backend/app/storage/logs/app/$(date +%Y-%m-%d).json \
   backend/app/storage/logs/app/$(date +%Y-%m-%d).json.bak
echo '[]' > backend/app/storage/logs/app/$(date +%Y-%m-%d).json
git pull   # 743e922 + 54b013c
```

**Súbory:** `LogWriter.php`, `RequestLoggingMiddleware.php`.

**Overenie:** `./scripts/iteration-gate.sh` + manuálne `/api/settings/public` → 200.

---


### Súvisiace odkazy

- [Commit `743e922`](https://github.com/techberode/paginiumcms-architecture/commit/743e922)
- [Commit `54b013c`](https://github.com/techberode/paginiumcms-architecture/commit/54b013c)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-041"></a>

## ISS-041 – Frontend type-check: nepoužitý `refetch` v PagesManager (CI)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-041)

**Symptóm:** Po pushi It.53 (`9101377`) CI job **frontend → TypeScript type-check** padá:

```
src/components/backend/PagesManager.tsx(161,38): error TS6133: 'refetch' is declared but its value is never read.
```

**Príčina:** Migrácia zoznamu stránok na `useAdminListQuery` deštrukturovala `refetch`, ale invalidácia cache už prebieha cez `queryClient.invalidateQueries()` v mutáciách — `refetch` sa nikde nevolal. Strict `noUnusedLocals` v `tsc --noEmit` build odmietne nepoužitú väzbu.

**Implementované riešenie (hotfix 2.0.40):**

- Odstránený `refetch` z deštrukturovania v `PagesManager.tsx` (~riadok 161):

```ts
// pred
const { data: listData, isLoading, refetch } = useAdminListQuery({ ... })

// po
const { data: listData, isLoading } = useAdminListQuery({ ... })
```

**Overenie:** `cd frontend && npm run type-check` — exit 0.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-042"></a>

## ISS-042 – Dvojitý login (1. pokus zlyhá, 2. prejde)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-042)

**Symptóm:** Po zadaní správneho hesla prvý pokus zlyhá (toast chyby alebo návrat na login). Druhý pokus s rovnakými údajmi prejde. V DevTools opakované `GET /api/auth/me` → **401** hneď po `POST /api/auth/login` → **200**.

**Príčiny (dve rodiny):**

1. **Race podmienka (FE):** `AuthContext` po úspešnom `login` okamžite volal `probeSession()` → `/api/auth/me`. Cookie zo `Set-Cookie` ešte nemusí byť v prehliadači → falošné „session chýba“.
2. **Cross-origin dev (ops):** FE na `localhost:5173` + API priamo na `192.168.10.26:8081` (CORS + cookies cross-site). Štandardný dev: `http://localhost:3025`, `VITE_API_URL=` prázdne, Vite proxy `/api` → `:8080`.

**Implementované riešenie (FE, release 2.0.42):**

- `authApi.probeSessionWithRetry()` — krátke opakované volania `/me` po logine.
- `AuthContext.login()` — dôvera odpovedi `POST /login` (BE overí `isAuthenticated()`); `/me` len synchronizuje stav.
- Rovnaký retry pre `verifyTwoFactorLogin()`.

**Overenie:** `npm run dev` → **localhost:3025**; jeden login → dashboard. Network: `/api/`* na **3025**, nie priamo IP:8081.

**Súvisí s:** [ISS-029](#iss-029), [ISS-033](#iss-033).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-043"></a>

## ISS-043 – Vitest: toolbar test — globálny `screen` (It.54)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-043)

**Symptóm:** `editorToolbar.test.tsx` padá — `expect(screen.queryByTitle('Obrázok')).not.toBeInTheDocument()` aj pre minimal profil.

**Príčina:** Dva rendery (minimal + developer) v jednom DOM; `screen` našiel tlačidlo z developer toolbaru.

**Implementované riešenie (2.0.41** `8526c19`**):** `within(minimalRoot)` / `within(developerRoot)`.

**Overenie:** `npm test -- src/components/backend/editorToolbar.test.tsx` — exit 0.

---


### Súvisiace odkazy

- [Commit `8526c19`](https://github.com/techberode/paginiumcms-architecture/commit/8526c19)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-044"></a>

## ISS-044 – `services.php` parse error (API 500)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-044)

**Symptóm:** Po úprave `ValidationController` DI backend nenačíta — `PHP Parse error: unexpected token "->", expecting "]"` v `backend/app/Http/Config/services.php` na riadku **301**. Všetky API volania (vrátane `POST /api/debug/client-event`) → **500**.

**Príčina:** Pri nahradení `ValidationController` registrácie closure zostal orphan riadok z pôvodného `create()` chainu:

```php
    },
        ->constructor(get(JsonResponder::class)),
```

**Implementované riešenie (2.0.45):** Odstránený duplicitný riadok; `ValidationController` injektuje `JsonResponder` + `PasswordPolicyInterface` cez closure.

**Overenie:** `php -l backend/app/Http/Config/services.php` — no syntax errors; API odpovedá 200.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-045"></a>

## ISS-045 – `LocaleScaffoldService::$projectRoot` (PHPStan + PHPUnit)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-045)

**Symptóm:** `alltests` — PHPUnit **exit 1** napriek `689 passed, 0 failed`; PHPStan **7 chýb** na `LocaleScaffoldService.php` (riadky 18, 53, 54, 83, 85, 101, 119). PHPUnit warning: `Creation of dynamic property ... $projectRoot is deprecated`.

**Príčina:** Konštruktor priraďoval `$this->projectRoot` bez deklarácie triednej vlastnosti (PHP 8.2+ deprecated dynamic properties; PHPStan level 8).

**Implementované riešenie (2.0.45):**

```php
private string $projectRoot;

public function __construct(
    private SupportedLocalesRegistry $locales,
    ?string $projectRoot = null,
) {
    $this->projectRoot = rtrim($projectRoot ?? dirname(__DIR__, 5), '/');
}
```

**Overenie:** `./scripts/iteration-gate.sh` — PHPUnit exit 0, PHPStan 0 errors on `LocaleScaffoldService`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-046"></a>

## ISS-046 – Audit udalosti sa zapisovali ako kategória `app`

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-046)

**Symptóm:** Uloženie stránky/článku vytvorilo verziu, ale v audit štatistikách chýbala udalosť. V `storage/logs/app/*.json` boli záznamy s `[CONTENT_CHANGE]`, no pole `category` bolo `app`, nie `audit_content_change`.

**Príčina:** `AuditTrailService::logAuditEvent()` vytvoril `LogEntry` s kategóriou `audit_`*, ale volal `$this->logger->log()`, ktoré vždy prepísalo kategóriu na default `app` z DI (`LoggerInterface`).

**Implementované riešenie (2.0.46):**

- `LoggerInterface::writeEntry(LogEntry)` — zapíše položku so zachovanou kategóriou
- `AuditTrailService` volá `writeEntry()` namiesto `log()`

**Overenie:** Nový zápis obsahu má v logu `category: audit_content_change`.

**Súvisí s:** [ISS-047](#iss-047).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-047"></a>

## ISS-047 – Dashboard „Prehľad aktivít“ prázdny

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-047)

**Symptóm:** Panel na `/dashboard` zobrazoval „Zatiaľ žiadne udalosti v audit logu“, hoci obsah sa ukladal a verzie vznikali.

**Príčina (dvojitá):**

1. `getAuditStats()` filtroval len záznamy s `category` začínajúcou na `audit_*` → legacy záznamy ([ISS-046](#iss-046)) boli neviditeľné.
2. Frontend volal správne `GET /api/admin/audit/stats`, ale `recent_events` prišlo prázdne.

**Implementované riešenie (2.0.46):**

- `AuditTrailService::isAuditEntry()` — rozpozná aj legacy záznamy (`context.category`, `[CONTENT_CHANGE]` v message)
- `getAuditStats()` skenuje 5000 posledných záznamov, zoradí `recent_events` podľa času
- API dopĺňa `display_message` pre každú udalosť

**Overenie:** Po reštarte BE dashboard zobrazí udalosti (min. z 20.7.).

**Poznámka:** Endpoint je len pre **ADMIN / SUPER_ADMIN** — EDITOR dostane 403 a panel ostane prázdny (zámer).

**Súvisí s:** [ISS-046](#iss-046), [ISS-048](#iss-048).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-048"></a>

## ISS-048 – Audit správy nečitateľné (zlý formát)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-048)

**Symptóm:** V dashboarde a `/audit` sa zobrazovalo napr. `[CONTENT_CHANGE] UPDATE: blog on maxxim@webland.fun by 2026-07-20 20:44:47` — nejasné, kto čo urobil.

**Príčina:**

1. `sprintf` v `logAuditEvent()` mal zamenené argumenty (`target` vs. email vs. timestamp).
2. Správy boli anglické, strojové; chýbal ľudský popis akcie (typ obsahu, verzia, zhrnutie diffu).

**Implementované riešenie (2.0.46):**

- Nový `AuditMessageFormatter` — SK texty typu: *„Maxxim upravil článok ‚blog‘ (verzia 12) · 3 pridaných“*
- Do `context.summary` sa ukladá finálna správa; `message` v logu = rovnaký text
- `summarizeDiff()` vracia SK („pridaných / odstránených / upravených“)
- FE utilita `formatAuditEvent.ts` — formátuje legacy aj nové záznamy v dashboarde a audit traile

**Overenie:** PHPUnit `AuditMessageFormatterTest`; Vitest `formatAuditEvent.test.ts`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-049"></a>

## ISS-049 – Korumpovaný denný log `2026-07-21.json`

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-049)

**Symptóm:** V `backend/app/storage/logs/app/` chýba platný súbor pre 21.7.; stovky prázdnych `.corrupt-`* backupov (752×). Nové udalosti z dnešného dňa sa nezapisujú — v dashboarde sú len záznamy zo včera.

**Príčina:** Bug v `LogWriter::decodeLogPayload()` — pri **prázdnom** dennom súbore (nový deň, `fopen c+`) sa volal `JsonHelper::decode('')`, čo hodí výnimku. Kód to vyhodnotil ako „corrupt“, vytvoril `.corrupt-`* backup a **zmazal** hlavný súbor. Každý ďalší HTTP request cyklus zopakoval → stovky prázdných corrupt súborov, žiadny perzistentný denný log.

**Implementované riešenie (2.0.46):**

- `decodeLogPayload()` — prázdny/whitespace payload = `[]` (nie corrupt)
- `backupCorruptLogFile()` — pri prázdnom raw len zmazať orphan súbor, **nevytvárať** `.corrupt-`*
- PHPUnit `testWriteToFreshEmptyDailyFileDoesNotCreateCorruptBackup`

**Po deployi:**

1. Reštart backendu (Docker/PHP).
2. Ľubovoľná akcia (login, uloženie stránky) vytvorí `2026-07-21.json`.
3. Voliteľne zmazať staré prázdne backupy: `rm backend/app/storage/logs/app/2026-07-21.json.corrupt-*`

**Overenie:** `./vendor/bin/phpunit backend/tests/Core/Logging/Services/LogWriterTest.php`

**Súvisí s:** [ISS-046](#iss-046), [ISS-047](#iss-047).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-050"></a>

## ISS-050 – Sekcia Logy prázdna (ApplicationLogReader)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-050)

**Symptóm:** `/logs` aj dashboard panel „Logy (24 h)“ zobrazujú **0 záznamov** / „Žiadne záznamy“, hoci `backend/app/storage/logs/app/` obsahuje veľké JSON súbory.

**Príčina (dvojitá):**

1. **Nesprávna cesta** — `ApplicationLogReader` v `Http/Config/services.php` čítal z `backend/storage/logs/` (adresár `app/` **neexistuje**). Zápis cez `LogWriter` išiel do `backend/app/storage/logs/app/`.
2. **Severity mismatch** — logy majú `INFO` (uppercase), frontend filtre a KPI očakávajú `info` (lowercase) → štatistiky vždy 0; filter `/logs?severity=info` vracal 400.

**Implementované riešenie (2.0.46):**

- Nový `LogStoragePaths` — jednotná cesta `backend/app/storage/logs/*` pre writer aj reader
- `ApplicationLogReader::severityStats()` vracia lowercase kľúče (`info`, `error`, …)
- Filter severity case-insensitive; `LogController` akceptuje `info` aj `INFO`
- Reader ignoruje poškodené JSON súbory (nepadne celé API)

**Overenie:** `./vendor/bin/phpunit backend/tests/Core/Logging/ApplicationLogReaderTest.php`

**Súvisí s:** [ISS-049](#iss-049).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-051"></a>

## ISS-051 – Boot crash: `DevTokenGenerator` výnimka pri `APP_ENV=production`

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-051)

**Symptóm:** Po security hardeningu (audit 2026-07-22) prestal fungovať boot aplikácie na prostrediach s `APP_ENV=production` — celý DI kontajner spadol s:

```
Uncaught RuntimeException: DEV_UNLOCK_SECRET must be configured outside local development.
  in backend/app/Http/Config/services.php:482
```

Prejavilo sa aj v teste `scripts/run-all-tests.zsh` krok 11:

```
[ Content diagnose (backend/bin/console) ]
Stats: Failed | issues: 0
ISSUE — thrown in .../backend/app/Http/Config/services.php
```

**Príčina:** Oprava S13 (dev-unlock secret fail-closed) hádzala `RuntimeException` **priamo v DI factory** `DevTokenGenerator::class`, keď bol `DEV_UNLOCK_SECRET` prázdny a `APP_ENV` nebol lokálny/testovací. Keďže:

1. `backend/.env` má `APP_ENV=production`, a
2. developer routy resolvujú `DevTokenGenerator` už **pri registrácii routov** (eager, `bootstrap/app.php`),

výnimka pri konštrukcii služby zhodila celý boot kontajnera — teda aj HTTP API aj CLI (`php backend/bin/console content:diagnose`).

**Implementované riešenie (security_fix hotfix):**

- Factory **nehádže výnimku**. Mimo lokál/test prostredia necháva secret **prázdny**.
- `DevTokenGenerator` je už fail-closed pri použití: `isConfigured() === false` → `generate()` vyhodí `InvalidArgumentException`, `validate()`/`verifyStructure()` vrátia „DEV_UNLOCK_SECRET nie je nastavený".
- Výsledok: dev-unlock je v produkcii bezpečne **vypnutý** a **nie predvídateľný** (žiadny fallback secret), pričom boot nespadne.
- Zámer S13 (žiadny predvídateľný fallback z čias `APP_DEBUG=true`) ostáva splnený.

**Ops poznámka:** Pre povolenie dev-unlock na konkrétnom stroji nastav `APP_ENV=development` alebo doplň `DEV_UNLOCK_SECRET=...` do `backend/.env`.

**Overenie:**

- `php backend/bin/console content:diagnose` → `[OK] Content storage looks healthy.`
- PHPStan L8 → 0 chýb; PHPUnit → 720 testov / 0 fail.

**Súbor:** `backend/app/Http/Config/services.php`. **Súvisí s:** audit S13 (`AUDIT_REPORT.md`, `SECURITY_ISSUES.md`).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-052"></a>

## ISS-052 – Šifrovanie tajomstiev „at-rest" (audit A1) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-052)

**Symptóm / riziko:** Citlivé tajomstvá boli vo flat-file úložisku uložené v **plaintexte**: `twoFactorSecret` (TOTP seed) v `data/users/*.json` a settings secrety (`smtp.password`, `connectors.ntfyAccessToken`/`ntfyPassword`, `telegramBotToken`, `webhookSecret`, SSO `*ClientSecret`) v `data/settings.json`. Pri úniku súborov (záloha, zlé práva, iná zraniteľnosť) → priame prevzatie 2FA a únik notifikačných/SSO/SMTP credentials.

**Implementované riešenie:**

- Nový `EncryptionService` (`backend/app/Core/Security/Services/EncryptionService.php`) — autentifikované symetrické šifrovanie s 32-bajtovým kľúčom odvodeným z `APP_KEY`. Preferuje **libsodium** `crypto_secretbox` (prefix `enc:s1:`), fallback **OpenSSL AES-256-GCM** (`enc:g1:`) pre PHP buildy bez `ext-sodium`.
- `UserRepository` šifruje/dešifruje `twoFactorSecret`; `SettingsRepository` šifruje polia typu `password` (`SettingsSchema::secretKeys()`) na zápise a dešifruje na čítaní.
- **Transparentná migrácia:** plaintext hodnoty sa čítajú nezmenené; šifrujú sa len nové zápisy (bez migračného skriptu). Ciphertext je non-deterministický, `encrypt()` idempotentné.
- **Fail-safe rollout:** neplatný/placeholder `APP_KEY` (napr. `base64:xxxx…`) → šifrovanie vypnuté (plaintext). Aktivuje sa nastavením reálneho kľúča; placeholder je explicitne odmietnutý.

⚠️ **Ops upozornenie:** `APP_KEY` je **per-prostredie** a po zašifrovaní dát ho **nikdy nemeň** (strata kľúča = strata tajomstiev → 2FA lockout). V produkcii nastav vlastný silný kľúč. Existujúce plaintext tajomstvá sa zašifrujú pri najbližšom uložení (napr. re-save nastavení / re-setup 2FA).

**Overenie:** PHPStan L8 → 0 chýb; PHPUnit → **741** testov / 0 fail (+`EncryptionServiceTest` a at-rest testy v `UserRepositoryTest`/`SettingsRepositoryTest`).

**Súbory:** `EncryptionService.php` (nový), `UserRepository.php`, `SettingsRepository.php`, `SettingsSchema.php`, `backend/bootstrap/app.php`, `backend/app/Http/Config/services.php`, `backend/storage/.htaccess`, `backend/.env`. **Súvisí s:** audit A1/A2, C-STORAGE (`SECURITY_ISSUES.md`).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-053"></a>

## ISS-053 – Log / CSV injection cez control znaky (audit C11) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-053)

**Symptóm / riziko:** User-controlled vstupy (query string, User-Agent, URI, Referer) sa zapisovali do logov/auditu bez odstránenia `\r\n`/control znakov. JSON logy CR/LF escapujú (fake-line injection do JSON nehrozí), ale zraniteľné ostávali: **CSV export** security auditu (embedded newline láme riadky), plaintext/terminálové zobrazenie a ne-JSON konzumenti (ANSI `\x1B`, DEL).

**Implementované riešenie:** Zdieľaný `LogSanitizer` (`backend/app/Support/LogSanitizer.php`) — beh control znakov (`\x00–\x1F`, `\x7F`) → jedna medzera. Aplikovaný cielene na netrusted polia na vstupe do log sinkov (`AccessLogService`, `FirewallIncidentLogger`, `SecurityLogger`) a na `SecurityAuditStore::exportCsv()` (str_replace `"` **+** strip newlines). Legitímne viacriadkové správy jadra sa nekolabujú.

**Overenie:** PHPStan L8 → 0 chýb; PHPUnit `LogSanitizerTest` (7 prípadov). **Súbory:** `LogSanitizer.php` (nový), `AccessLogService.php`, `FirewallIncidentLogger.php`, `SecurityAuditStore.php`, `SecurityLogger.php`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-054"></a>

## ISS-054 – SSRF cez admin-konfigurovateľné odchádzajúce URL (audit C14) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-054)

**Symptóm / riziko:** Odchádzajúce HTTP volania na admin-konfigurovateľné URL (generic OAuth `token_url`/`userinfo_url`, ntfy server, webhook, Discord webhook) bežali cez `file_get_contents` bez SSRF ochrany → server sa dal prinútiť volať interné služby: cloud metadata (`169.254.169.254`), `localhost`, privátne rozsahy, ne-HTTPS ciele.

**Implementované riešenie:** `OutboundUrlGuard` (`backend/app/Core/Security/Services/OutboundUrlGuard.php`) — v produkcii len `https://`, zákaz userinfo v URL, host → IP a odmietnutie privátnych/rezervovaných rozsahov (loopback, link-local metadata, `10/8`, `172.16/12`, `192.168/16`, IPv6 `::1`), fail-closed pri nerozlíšiteľnom hoste. V `testing`/`development`/`local` uvoľnený (http + privátne), aby fungoval lokálny SSO/ntfy. Zapojený v `OAuthSsoService` (`httpPostForm`/`httpGet`) a v ntfy/webhook/Discord adaptéroch (fail-safe → neodošle).

**Overenie:** PHPStan L8 → 0 chýb; PHPUnit `OutboundUrlGuardTest` (11 prípadov). **Súbory:** `OutboundUrlGuard.php` (nový), `OAuthSsoService.php`, `NtfyAdapter.php`, `WebhookAdapter.php`, `DiscordAdapter.php`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-055"></a>

## ISS-055 – Path ACL nezapojené do content/media operácií (audit S9) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-055)

**Symptóm / riziko:** `PathAclService` a admin UI `/security/acl` existovali od It.11, ale služba sa **nikde nevolala** — pravidlá v `data/security/acl.json` nemali vplyv na CRUD stránok/článkov, draftov ani médií.

**Implementované riešenie:** `ContentPathAclGuard` + `PathAclService::normalizeStoragePath()` (mapuje `pages/foo.md` → `content/pages/foo` pre glob pravidlá z admin UI). Zapojené do `ContentController`, `DraftController`, `MediaController`. Opt-in (`enabled: false` → bez zmeny správania); read deny → 404, write deny → 403.

**Post-2.0.51:** Path ACL + nastaviteľné RBAC mapovanie rolí presunuté do **Nastavenia → Bezpečnosť → Oprávnenia rolí** (`accessControl`, len SUPER_ADMIN). UI `/security/acl` → redirect. Dokumentácia: [docs/user/ACCESS_CONTROL.md](user/ACCESS_CONTROL.md).

**Overenie:** PHPUnit `PathAclServiceTest`, `ContentPathAclGuardTest`, `PathAclIntegrationTest` (17 scenárov spolu).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-056"></a>

## ISS-056 – WAF neskenuje POST/JSON telo (audit S-WAFBODY) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-056)

**Symptóm / riziko:** `FirewallMiddleware` kontroloval len URI, query string a User-Agent. SQLi/traversal/SSRF payloady v JSON tele (login, contact, settings webhook URL…) prešli bez matchu.

**Implementované riešenie:**

- `FirewallRequestBodyReader` — bounded snapshot (max 64 KiB), skip `multipart/form-data`, rewind stream.
- `FirewallBodyScanPolicy` — skenuje POST/PUT/PATCH/DELETE okrem editor API (`/api/pages`, `/api/articles`, `/api/drafts`, `/api/admin/code-editor`) kvôli false positive na markdown/SQL ukážkach.
- Nové scenáre: `sql_probe_body`, `ssrf_probe_body`; `path_traversal` + `env_probe` rozšírené o target `body`.
- Settings: `firewall.scanRequestBody` (default `true`). `APP_ENV=testing` stále bypassuje celý WAF.

**Overenie:** PHPUnit `FirewallScannerTest`, `FirewallBodyScanPolicyTest`, `FirewallRequestBodyReaderTest`, `FirewallMiddlewareTest` (+ body scenáre).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-057"></a>

## ISS-057 – UserRepository O(n) lookup (audit PERF-USERREPO) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-057)

**Symptóm / riziko:** `UserRepository::findByEmail()`, `findById()`, `findByResetToken()` a `existsByUsername()` pri každom volaní načítali a dekódovali **všetky** `data/users/*.json` súbory → O(n) I/O pri každom prihlásení, 2FA refreshi alebo reset hesla. Pri malom počte účtov zanedbateľné, pri raste inštancie zbytočná záťaž disku a latencia auth cesty.

**Implementované riešenie:**

- Nový `UserIndexService` — flat-file index v `data/index/users.json` (vzor ako `ContentIndexService`): mapy `by_email`, `by_username`, `by_id` + reset-token hash/expiry pre rýchly lookup.
- Atomický zápis cez `flock(LOCK_EX)`; lazy `ensureBuilt()` pri prvom lookup (rebuild zo existujúcich JSON ak index chýba/prázdny).
- `UserRepository` po každom `save`/`delete`/`saveResetToken`/`clearResetToken` synchronizuje index; lookup metódy čítajú **jeden** user súbor podľa ID z indexu.
- DI: `UserIndexService` registrovaný v `bootstrap/app.php` a `Modules/Security/Config/services.php`.

**Overenie:** PHPUnit `UserIndexServiceTest` (rebuild, ensureBuilt, upsert/remove, reset-token expiry, backup ignore) + existujúce `UserRepositoryTest` (regresia auth/2FA/reset). PHPStan L8 clean.

**Súbory:** `UserIndexService.php` (nový), `UserRepository.php`, `bootstrap/app.php`, `Modules/Security/Config/services.php`, `scripts/run-all-tests.zsh` (krok 17).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-058"></a>

## ISS-058 – OTP bez dedikovaného rate-limitu (audit S10) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-058)

**Symptóm / riziko:** OTP routy (`/api/auth/register*`, `/api/admin/workflows/otp/*`) mali len globálny limit 60/min — príliš voľný pre brute-force 6-miestneho kódu. `resendRegistration()`/`resendEditorChallenge()` navyše resetovali `attempts` na 0 → obídenie `otpMaxAttempts` opakovaným resendom.

**Implementované riešenie:**

- `OtpStartRateLimitMiddleware`, `OtpVerifyRateLimitMiddleware`, `OtpResendRateLimitMiddleware` — produkčné limity: start 5/h (email+IP), verify 10/15 min (challenge+IP), resend 3/h (challenge+IP).
- Zapojené na auth OTP routy v `bootstrap/app.php` a admin workflow routy v `workflows.php`.
- Service: `incrementResendCount()` — max 3 resend/challenge (nastaviteľné `otpMaxResends`), **bez resetu** `attempts` pri resend.

**Overenie:** PHPUnit `OtpRateLimitMiddlewareTest` (3 scenáre) + rozšírené `OtpWorkflowServiceTest` (resend neobnoví pokusy, max resend count). PHPStan L8 clean.

**Súbory:** `OtpRateLimitMiddleware.php`, `OtpVerifyRateLimitMiddleware.php`, `OtpResendRateLimitMiddleware.php`, `OtpStartRateLimitMiddleware.php`, `OtpWorkflowService.php`, `OtpChallengeStore.php`, `bootstrap/app.php`, `workflows.php`, `scripts/run-all-tests.zsh` (krok 18).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-059"></a>

## ISS-059 – Vitest: `useI18n()` bez `I18nProvider` (CI @ `f0a885c`) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-059)

**Symptóm / CI log:**

```
Error: useI18n must be used within I18nProvider
  at src/context/I18nContext.tsx:47
```

Spustené z komponentov, ktoré boli migrované na `useI18n()` v It.18f, ale unit testy stále používali surový `render()` z `@testing-library/react`:

| Test súbor | Komponent |
|------------|-----------|
| `MediaPreviewLightbox.test.tsx` | `MediaPreviewLightbox.tsx:35` |
| `SitePreviewModal.test.tsx` | `SitePreviewModal.tsx:137` |
| `editorToolbar.test.tsx` | `MarkdownContentEditor.tsx:44` |
| `HealthPanel.test.tsx` | `HealthPanel.tsx:18` |
| `LocksPanel.test.tsx` | `LocksPanel.tsx:15` |

**Druhá chyba (MediaManager.test.tsx):** `Unable to find role="dialog" and name /Edit metadata/i` — test očakával anglické labely, ale `renderWithRouter` už používal `TestI18nProvider` s locale `sk` (`Upraviť metadáta`, `Titulok`, `Uložiť zmeny`).

**Implementované riešenie (2.0.47):**

- Nový `frontend/src/test/renderWithProviders.tsx` — wrapper s `TestI18nProvider` (default `sk`, voliteľné `{ locale: 'en' }`)
- `renderWithRouter.tsx` refaktor — deleguje na `renderWithProviders` + `MemoryRouter`
- 6 test súborov prepnutých z `render()` na `renderWithProviders()` / SK asercie
- `MediaManager.test.tsx` — `findByRole('dialog')` bez hardcoded EN názvu; labely podľa `editor.mediaMeta.*` SK katalógu

**Overenie:** `npm test -- --run` → **210/210** OK. CI ref: `.github/workflows/ci.yml` @ commit `f0a885c787e2234f8c117921e75e42b555bfe5a5`.

**Súbory:** `renderWithProviders.tsx` (nový), `renderWithRouter.tsx`, `MediaPreviewLightbox.test.tsx`, `SitePreviewModal.test.tsx`, `editorToolbar.test.tsx`, `HealthPanel.test.tsx`, `LocksPanel.test.tsx`, `MediaManager.test.tsx`.

**Súvisí s:** It.18f i18n migrácia, [ISS-022](#iss-022) (MediaManager test pattern).

---


### Súvisiace odkazy

- [Commit `f0a885c787e2234f8c117921e75e42b555bfe5a5`](https://github.com/techberode/paginiumcms-architecture/commit/f0a885c787e2234f8c117921e75e42b555bfe5a5)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-060"></a>

## ISS-060 – Settings EN katalóg: SK copy-paste v `workflows` — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-060)

**Symptóm:** Pri prepnutí admin jazyka na **English** (`Nastavenia → Všeobecné → Jazyk`) zostali v sekcii **Workflows / OTP** slovenské labely a popisy. Používateľ videl zmiešané SK/EN bloky v inak anglickom admin rozhraní.

**Príčina:** `frontend/src/i18n/modules/settings/en.ts` — sekcia `workflows` obsahovala skopírovaný slovenský text namiesto anglického prekladu.

**Implementované riešenie (2.0.47 / commit `f0a885c`):**

- Opravené EN reťazce v `settings/en.ts` (`workflows.*`, OTP polia)
- `SettingsView` používa `translateSettingFieldLabel(t, …)` — pri chýbajúcom EN kľúči fallback na SK z `SettingsSchema.php`; oprava katalógu odstráni zmiešaný UI

**Overenie:** Manuálne — Settings → English → Workflows sekcia plne EN; `settings.test.ts` catalog parity.

**Súbory:** `frontend/src/i18n/modules/settings/en.ts`, `SettingsView.tsx` (bez zmeny logiky — fix v katalógu).

**Súvisí s:** It.18c, It.18f.

---


### Súvisiace odkazy

- [Commit `f0a885c`](https://github.com/techberode/paginiumcms-architecture/commit/f0a885c)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-061"></a>

## ISS-061 – Audit správy v EN admin locale zostávali po slovensky — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-061)

**Symptóm:** Pri **`Nastavenia → Všeobecné → Jazyk = English`** zostali audit správy v dashboard „Prehľad aktivít“ a v `/audit` po slovensky (napr. „Maxxim upravil článok…“), zatiaľ čo zvyšok admin UI bol anglický.

**Príčina:**

1. `AuditMessageFormatter` mal natvrdo SK slovníky; ignoroval `Lang::getLocale()`
2. `formatFromLog()` vracal uložený SK `context.summary` pred re-formátovaním
3. FE `formatAuditEvent.ts` duplikoval SK logiku namiesto `display_message` z API
4. `getContentAuditTrail` / `getUserAuditTrail` neposielali `display_message`

**Implementované riešenie (2.0.49 / wave 5b):**

- `backend/lang/{sk,en}/audit.php` — katalóg akcií, typov obsahu, diff počtov
- `AuditMessageFormatter` → `Lang::get()`; `formatFromLog()` preferuje re-formát z contextu
- `AuditTrailService::buildDiffMetadata()` — numerické diff polia + enrich `display_message` na read cestách
- `formatAuditEvent.ts` — tenký klient; `AuditTrail` + `DashboardActivityPanel` posielajú `locale`
- PHPUnit `AuditMessageFormatterTest` — SK + EN; Vitest 210/210

**Overenie:** Settings → EN → upraviť článok → dashboard activity EN text; PHPStan L8 clean (`enrichAuditLogEntry` return type fix).

**Súbory:** `audit.php` (lang), `AuditMessageFormatter.php`, `AuditTrailService.php`, `EnhancedVersionManager.php`, `formatAuditEvent.ts`, `AuditTrail.tsx`, `DashboardActivityPanel.tsx`, `i18n/modules/audit/*`.

**Súvisí s:** It.18, [ISS-048](#iss-048) (audit formatter), wave 5b [CONTINUATION.md](CONTINUATION.md).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-062"></a>

## ISS-062 – Verejný web hardcoded SK pri EN locale — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-062)

**Symptóm:** Pri **`Nastavenia → Všeobecné → Jazyk = English`** zostal verejný web (navbar, blog, footer, login/register modals, contact form) po slovensky, zatiaľ čo admin UI bol anglický.

**Príčina:** ~120 hardcoded SK reťazcov v `frontend/src/components/frontend/*`, auth modals, `PublicSiteContext`, `contentDates.ts`, `readingTime.ts`, `validatePasswordPolicy()`.

**Implementované riešenie (2.0.50 / wave 5c):**

- `frontend/src/i18n/modules/public/{sk,en}.ts` — katalóg `public.*` (~120 kľúčov)
- 18+ komponentov → `useI18n().t('public.*')`
- `formatContentDateLabels()` / `formatReadingTime()` — parameter `locale`
- `validatePasswordPolicy()` — `public.auth.password.validation.*`
- Vitest **217/217** OK; `public.test.ts` SK/EN parity

**Overenie:** Settings → EN → `/`, `/blog`, `/login` anglicky; SK späť po prepnutí jazyka.

**Súbory:** `i18n/modules/public/*`, `BlogRenderer.tsx`, `Navbar.tsx`, `LoginModal.tsx`, auth modals, `PublicSiteContext.tsx`, `contentDates.ts`, `readingTime.ts`.

**Súvisí s:** It.18, wave 5c [CONTINUATION.md](CONTINUATION.md).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-063"></a>

## ISS-063 – `RangeError: Invalid time value` (admin + verejný web) — OPRAVENÉ LOKÁLNE (release 2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-063)

**Symptóm:** Po deployi **2.0.50** pád Reactu v konzole:

```text
RangeError: Invalid time value
  at formatDistanceToNow … VersionHistory.tsx   ← admin /pages/:slug
  at toLocaleDateString … SiteSearchModal       ← verejný web
```

Stránka sa nevykreslí; pri admin editore to vyzeralo aj ako „odhlásenie“ (remount shellu).

**Príčina:**

1. **Admin:** `VersionHistory` volal `formatDistanceToNow(new Date(version.createdAt || ''))` — prázdny `createdAt` → Invalid Date → výnimka
2. **Verejný web:** `SiteSearchModal`, `PageRenderer`, `ArticleComments` — `new Date(undefined)` / `"undefined"` bez validácie
3. Wave 5c pridala priame `toLocaleDateString()` na nevalidované API hodnoty

**Implementované riešenie (lokálne → **2.0.51**):**

- `contentDates.ts` — `formatDisplayDate()`, `formatDisplayDateTime()`, `formatRelativeTime()`, `resolveContentDate()`
- `VersionHistory.tsx`, `AuditTrail.tsx`, `PagesManager.tsx`, `LockIndicator.tsx`, … — bezpečné helpery
- Neplatný dátum → em dash `—` namiesto výnimky
- Vitest: `contentDates.test.ts` rozšírené

**Overenie:**

1. `/pages/home` (existujúca stránka) — história verzií bez pádu
2. Site search — konzola bez `Invalid time value`
3. Stránka bez `createdAt` — zobrazí `—`

**Súbory:** `contentDates.ts`, `VersionHistory.tsx`, `SiteSearchModal.tsx`, `PageRenderer.tsx`, …

**Súvisí s:** [ISS-062](#iss-062), wave 5c, [RELEASE.md](developer/RELEASE.md) **2.0.51**.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-064"></a>

## ISS-064 – CI `tsc`: `DEFAULT_LOCALE` not exported — OPRAVENÉ LOKÁLNE (release 2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-064)

**Symptóm:** GitHub Actions / lokálne `npm run type-check`:

```text
error TS2459: Module '"../i18n"' declares 'DEFAULT_LOCALE' locally, but it is not exported.
  src/utils/contentDates.ts(1,10)
  src/utils/readingTime.ts(1,10)
  src/utils/validation.ts(9,10)
```

**Príčina:** Wave 5c pridala import `DEFAULT_LOCALE` z `../i18n` v utility súboroch, ale `frontend/src/i18n/index.ts` exportoval len typy, nie konštantu.

**Implementované riešenie (lokálne → **2.0.51**):**

```typescript
export { DEFAULT_LOCALE, type Locale, type MessageTree, type MessageValue } from './types';
```

**Overenie:** `npm run type-check` → 0 errors.

**Súbory:** `frontend/src/i18n/index.ts`.

**Súvisí s:** [ISS-062](#iss-062), wave 5c hotfix.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-065"></a>

## ISS-065 – Admin logy o 2 hodiny dozadu (timezone) — OPRAVENÉ LOKÁLNE (2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-065)

**Symptóm:** V administrácii (Logy, audit) čas záznamov o **2 h menej** než skutočný lokálny čas (SK leto = UTC+2). Nie je to NTP sync — ide o **časovú zónu PHP**.

**Príčina:** `APP_TIMEZONE=Europe/Bratislava` v `.env` sa **neaplikovalo** pri boote. PHP bežalo v UTC; `date('Y-m-d H:i:s')` zapisovalo UTC do logov.

**Implementované riešenie:**

- `backend/bootstrap/timezone.php` — aplikuje `APP_TIMEZONE` hneď po `.env`
- `LocaleMiddleware` — aplikuje `general.timezone` + `general.timezoneDst` z nastavení
- `AppTimezone` helper; health check `system` vracia `php_timezone`, `php_time`, `utc_time`

**Overenie:** Health panel → `php_timezone` = `Europe/Bratislava`, `php_time` sedí s hodinkami.

**Súbory:** `AppTimezone.php`, `bootstrap/timezone.php`, `LocaleMiddleware.php`, `SystemChecker.php`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-066"></a>

## ISS-066 – PHPUnit CronExpressionEvaluator same-minute — OPRAVENÉ (2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-066)

**Symptóm:** `./vendor/bin/phpunit` — `CronExpressionEvaluatorTest::testIsDueSinceLastRunSkipsSameMinute` failed (true !== false).

**Príčina:** `isDueSinceLastRun()` porovnával `$at->format()` v lokálnej zóne a `date()` cez `strtotime()` v default PHP zone — pri `Europe/Bratislava` v php.ini nesúlad o 2 h.

**Riešenie:** Parsovanie `lastRun` cez `DateTimeImmutable`, porovnanie v rovnakej zóne ako `$at`; testy s explicitným `UTC`.

**Súbor:** `CronExpressionEvaluator.php`, `CronExpressionEvaluatorTest.php`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-067"></a>

## ISS-067 – PHPUnit LocaleMiddlewareTest — OPRAVENÉ (2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-067)

**Symptóm:** `LocaleMiddlewareTest::testUsesConfiguredLanguageFromSettings` — mock očakával len `general.language`, middleware volá aj `general.timezone`.

**Riešenie:** Mock cez `willReturnCallback()` pre `general.language`, `general.timezone`, `general.timezoneDst`.

**Súbor:** `LocaleMiddlewareTest.php`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-068"></a>

## ISS-068 – Code policy v logoch ako ERROR — OPRAVENÉ (2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-068)

**Symptóm:** V admin Logoch záznam:

```text
Error in file backend/app/Modules/PolicyTest.php: Code policy validation failed
```

(+ celý stack trace). Vzniká pri teste `CodeEditorControllerTest::testSaveFileRejectsPolicyViolation` (zámerné `eval("bad")`) ale aj pri reálnom zamietnutí save v editore.

**Príčina:** `CodeEditorManager` volal `logError()` pre `CodePolicyViolationException` — očakávané 422, nie systémová chyba.

**Riešenie:** Nové `logPolicyRejection()` — severity **WARNING**, kategória `code_editor_policy`, bez stack trace, len `errors` z policy.

**Súbory:** `CodeEditorLogger.php`, `CodeEditorManager.php`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-069"></a>

## ISS-069 – Časové pásmo: vyhľadávateľný zoznam — OPRAVENÉ (2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-069)

**Symptóm:** Nastavenia → Všeobecné → Časové pásmo bolo obyčajné textové pole (preklep → neplatná zóna).

**Riešenie:**

- Schéma: typ `timezone`, validácia `timezone` (IANA)
- FE: `TimezoneSelect.tsx` — search + často používané pásma + offset v label
- `utils/timezones.ts`, pravidlo v `Validator.php` + FE zrkadlo

**Súbory:** `SettingsSchema.php`, `TimezoneSelect.tsx`, `timezones.ts`, `SettingsView.tsx`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-070"></a>

## ISS-070 – Letný čas (DST) v nastaveniach — OPRAVENÉ (2.0.51 ⏳)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-070)

**Symptóm:** Chýbal prepínač letného času; admin nevedel, či CMS aplikuje DST korekciu.

**Riešenie:**

- Nové pole `general.timezoneDst` (bool, default **true**)
- Zapnuté = IANA pásmo s automatickým DST (`Europe/Bratislava` → CET/CEST)
- Vypnuté = fixný zimný offset (bez letného posunu)
- UI: checkbox v nastaveniach + stav „Letný čas je aktívny/neaktívny“ pod výberom pásma

**Súbory:** `SettingsSchema.php`, `AppTimezone.php`, `TimezoneSelect.tsx`, i18n `settings.*`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-071"></a>

## ISS-071 – Logy: bulk akcie, delete-all, stránkovanie — VYRIEŠENÉ (**2.0.51**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-071)

**Symptóm:** Admin **Logy** (`/logs`) zobrazoval max 200 záznamov bez stránkovania, bez výberu riadkov, bez ručného mazania/archivácie vybraných položiek ani kompletného vymazania.

**Implementované riešenie (2.0.51):**

- **FE `LogsManager`** — checkboxy, `BulkActionBar` (Archivovať / Vymazať), filter Aktívne/Archivované/Všetky, `AdminListPagination`, number input pre page size (1–500)
- **BE** — `ApplicationLogReader`: `count()`, `deleteByIds()`, `archiveByIds()`, `deleteAll()`; list API vracia `total`
- **API** — `POST /api/admin/logs/bulk`, `POST /api/admin/logs/delete-all`
- **`AdminListToolbar`** — režim `pageSizeInputMode="number"` pre ručné zadanie

**Overenie:** PHPUnit `ApplicationLogReaderTest` + `LogControllerTest`; Vitest 226/226; full PHPUnit 816 OK.

**Súbory:** `ApplicationLogReader.php`, `LogController.php`, `logs.php`, `LogsManager.tsx`, `logs.ts`, `AdminListToolbar.tsx`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-072"></a>

## ISS-072 – Security audit 403 pre ADMIN — VYRIEŠENÉ (**2.0.52**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-072)

**Symptóm:** `GET /api/admin/security/audit` vracal **403** pre rolu **ADMIN** (PHPUnit `SecurityAuditControllerTest`, admin audit v UI).

**Príčina:** Po presune Path ACL do nastavení bola celá skupina `/api/admin/security/*` obmedzená na **SUPER_ADMIN** — vrátane audit endpointov, ktoré majú zostať dostupné pre ADMIN.

**Implementované riešenie:** `backend/app/Http/Routes/security.php` — dve route skupiny:

| Trasa | Role |
|-------|------|
| `GET /audit`, `GET /audit/export` | ADMIN, SUPER_ADMIN |
| `GET/PUT /acl` | SUPER_ADMIN only (legacy; prefer settings `accessControl`) |

**Overenie:** `./vendor/bin/phpunit --filter SecurityAuditControllerTest`

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-073"></a>

## ISS-073 – PHPUnit login → 429 namiesto 401 — VYRIEŠENÉ (**2.0.52**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-073)

**Symptóm:** V plnom behu suite padali testy očakávajúce **401** pri zlom hesle / neexistujúcom účte, ale dostávali **429**:

- `ApiResponseShapeTest::testLoginErrorShape`
- `AuthControllerTest::testLoginWithNonExistentEmail`

**Príčina:** `LoginAttemptTracker` ukladá neúspešné pokusy do flat-file `data/security/login_attempts.json`. HTTP testy neposielajú `REMOTE_ADDR` → všetky zdieľajú IP kľúč `unknown`. Po ≥ `maxLoginAttempts` (default 5) sa IP zablokuje a `AuthController::login()` vráti **429** ešte pred overením hesla.

**Implementované riešenie:** `backend/tests/Http/TestCase.php` — v `setUp()` po bootstrap volanie `LoginAttemptTracker::clearAll()` (izolácia medzi testami). Test `testLoginLockoutAfterRepeatedFailures` ostáva platný (lockout v rámci jedného testu s vlastnou IP).

**Overenie:** `./vendor/bin/phpunit` — full suite **820** testov (15 skipped).

**Súvisí s:** [ISS-015](#iss-015) (historické 429 v CI), [TESTING.md](developer/TESTING.md).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-074"></a>

## ISS-074 – PHPStan L8 po accessControl / branding — VYRIEŠENÉ (**2.0.52**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-074)

**Symptóm:** `./vendor/bin/phpstan analyse --level=8 backend/app` — **10 chýb** po pridaní skupiny `accessControl`, `AccessControlSyncService`, `PermissionCatalog`.

**Opravy:**

| Súbor | Zmena |
|-------|--------|
| `SettingsSchema.php` | `@phpstan-type SettingGroup` + voliteľné `superAdminOnly`; `filterValuesForUser()` akceptuje `array<int\|string, mixed>` |
| `AccessControlSyncService.php` | validácia JSON pravidiel cez `array_is_list()` namiesto redundantného `is_array()` |
| `PermissionCatalog.php` | return type `defaultAccessControlSettings()` → `array<string, bool\|string>` |
| `Http/Config/services.php` | `use AccessControlSyncService` |

**Overenie:** PHPStan L8 → **0 errors**; PHPUnit green.

**Súvisí s:** [ISS-055](#iss-055) (Path ACL v nastaveniach), [ACCESS_CONTROL.md](user/ACCESS_CONTROL.md).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-075"></a>

## ISS-075 – PHPUnit fatal: duplicate `HelloWidget\Hooks` class — VYRIEŠENÉ (**2.0.54**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-075)

**Symptóm:** Po pridaní referenčného pluginu `hello-widget` do repozitára padol celý PHPUnit suite:

```text
PHP Fatal error: Cannot redeclare class PaginiumCMS\Http\Extensions\HelloWidget\Hooks
(previously declared in …/backend/app/Http/Extensions/hello-widget/src/Hooks.php:10)
in /tmp/pag_plugins_mgr_…/extensions/hello-widget/src/Hooks.php on line 7
```

**Príčina:** Wave **5d** pridala skutočný plugin `hello-widget` s triedou `HelloWidget\Hooks`. Test `PluginManagerTest::testEnableRegistersHooksOnBoot` vytváral **dočasný** plugin s rovnakým `id` a **rovnakým** PHP namespace. `PluginManager::loadPluginClasses()` volá `require_once` — prvá deklarácia zostala v pamäti (z `HelloWidgetReferencePluginTest` alebo z autoloadu), druhá z temp adresára spôsobila fatal error.

**Oprava:**

| Súbor | Zmena |
|-------|--------|
| `PluginManagerTest.php` | Test hook registrácie používa izolovaný plugin `ping-demo` + namespace `PaginiumCMS\Http\Extensions\PingDemo\Hooks` |
| `EXTENSION_CODE_POLICY.md` | Pravidlo: test extension v PHPUnit **musí mať unikátny namespace**, ak existuje referenčný plugin v repozitári |

**Overenie:** `./vendor/bin/phpunit` → **833 passed**, 15 skipped.

**Prevencia:** Pri písaní extension testov nepoužívať rovnaký namespace ako bundled referenčný plugin; preferovať `ping-demo`, `temp-*` id v temp adresároch.

**Súvisí s:** [ITERATION_15D.md](ITERATION_15D.md), [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md) §8.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-076"></a>

## ISS-076 – PHPUnit kaskáda po zavedení `passwordConfirm` — VYRIEŠENÉ (**2.0.56**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-076)

**Symptóm:** Po release **2.0.56** (povinné potvrdenie hesla pri registrácii a admin CRUD používateľov) padlo **21 PHPUnit testov** v niekoľkých suite naraz:

| Suite | Príklad zlyhania |
|-------|------------------|
| `CoreHardeningTest` | Registrácia vypnutá → očakávané **403**, skutočné **422** |
| `CoreHardeningTest` | `GET /api/media` ako USER → očakávané **403**, skutočné **401** |
| `PathAclIntegrationTest` | `loginAsEditor()` → `findByEmail` **null** |
| `MediaControllerTest` | `loginAsAdminUser()` → login **401**, media **401** |
| `OtpWorkflowServiceTest` | `loginAsAdminUser()` → editor **null** |
| Security regression gate | Rovnaká kaskáda od `createTestUser` |

Typické hlášky:

```text
Failed asserting that 422 matches expected 403.
Failed asserting that 401 matches expected 200.
Failed asserting that null is not null.
```

**Príčina (reťazec):**

1. **`AuthController::register`** od 2.0.56 volá `ValidationRules::validatePasswordConfirmation()` **pred** kontrolou `allowRegistration` a maintenance → chýbajúce `passwordConfirm` vráti **422**, nie **403**.
2. Testy `testRegistrationDisabledBySetting` a `testRegistrationDisabledDuringMaintenance` v `CoreHardeningTest` posielali register **bez** `passwordConfirm`.
3. Pri zlyhaní assertion sa **nevykonala obnova** nastavení (`allowRegistration => true`, `maintenance.mode => off`) — zostalo `allowRegistration = false`.
4. Všetky nasledujúce testy volajúce `createTestUser()` / `loginAsAdminUser()` dostali registráciu **403** → login **401** → `currentUser` null → desiatky falošných failov v Media, Path ACL, OTP, Navigation.

**Oprava:**

| Súbor | Zmena |
|-------|--------|
| `CoreHardeningTest.php` | Register payloady doplnené o `passwordConfirm`; `try/finally` pre vždy obnoviť `general` / `maintenance` |
| `TestCase.php` | `applyTestSettingsOverrides()` v `setUp` vynúti `allowRegistration => true` (izolácia medzi testami) |
| `AuthControllerTest.php` | Už obsahoval `passwordConfirm` (referenčný pattern pre nové testy) |

**Overenie:** `./vendor/bin/phpunit` → **838 passed**, 15 skipped (commit `0664ba3`).

**Prevencia:**

- Každý priamy `POST /api/auth/register` v PHPUnit musí posielať **`passwordConfirm`** zhodné s `password`.
- Testy, ktoré dočasne menia `general.allowRegistration` alebo `maintenance.mode`, musia obnoviť stav v **`finally`** (nie len na konci metódy po assertion).
- Pri pridávaní validácie **pred** business pravidlom (403 maintenance) aktualizovať aj testy, ktoré očakávajú konkrétny HTTP kód z business vrstvy.

**Súvisí s:** [ITERATION_5.md](ITERATION_5.md#password-confirmation-2056), [CORE_HARDENING.md](architecture/CORE_HARDENING.md) §4, [CHANGELOG.md](../../CHANGELOG.md#2056--2026-07-23), commit `0664ba3`.

---

## Externé / irelevantné hlášky


| Hláška                                                 | Zdroj                                   |
| ------------------------------------------------------ | --------------------------------------- |
| `Failed to get subsystem status` / `content-script.js` | Rozšírenie prehliadača, nie PaginiumCMS |
| `GET /api/auth/me` → 401 pred loginom                  | Očakávané správanie                     |


---


### Súvisiace odkazy

- [Commit `0664ba3`](https://github.com/techberode/paginiumcms-architecture/commit/0664ba3)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-077"></a>

## ISS-077 – Audit trail CSV export bez LogSanitizer — VYRIEŠENÉ (**2.1.0-beta.2**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-077)

**Symptóm:** Pre-beta audit našiel Medium nález: `AuditTrailService::exportAuditToCsv()` neaplikoval `LogSanitizer` na bunky CSV (na rozdiel od `SecurityAuditStore::exportCsv()` po C11). EDITOR+ mohol cez auditovaný obsah (`\r\n`, `=CMD()`) poškodiť export alebo spustiť formula injection v Excel/LibreOffice.

**Riešenie:** Všetky bunky cez `LogSanitizer::value()` + jednotné CSV quoting. Regresný test `AuditTrailServiceTest::testExportAuditToCsvSanitizesAllCells()`.

**Súvis:** lokálny `SECURITY_ISSUES.md` → `C11-AUDITTRAIL-CSV`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-078"></a>

## ISS-078 – React Router npm advisories (post-beta.2) — VYRIEŠENÉ (**2.1.0-beta.3**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-078)

**Kontext:** Tag **`v2.1.0-beta.2`** (2026-07-23) prešiel pre-push gate s `npm audit --audit-level=high` → **0 CVE**. GitHub Security Advisories pre React Router boli **publikované až po tomto release**; pri `npm audit --audit-level=moderate` sa objavili **3 moderate** nálezy v transitívnom balíku `react-router@6.30.4` (via `react-router-dom@^6.20.0` → lock **6.30.4**). V vetve **6.x neexistuje patch** — oprava je až od **`react-router-dom@7.18.0`**.

**Advisories (GitHub):**

| ID | Názov | Závažnosť | Ovplyvnený rozsah | Odkaz |
|----|-------|-----------|-------------------|-------|
| GHSA-wrjc-x8rr-h8h6 | Open redirect cez backslash v `<Link>` / `useNavigate` (CVE-2025-68470 bypass) | Moderate | `react-router` ≥6.0.0 **<7.18.0** | https://github.com/advisories/GHSA-wrjc-x8rr-h8h6 |
| GHSA-jjmj-jmhj-qwj2 | Open redirect → XSS (`react-router-dom`) | Moderate | `react-router-dom` ≥6.30.2 **≤6.30.4** | https://github.com/advisories/GHSA-jjmj-jmhj-qwj2 |
| GHSA-337j-9hxr-rhxg | Arbitrary constructor injection cez `deserializeErrors()` (SSR hydration) | Moderate | `react-router` ≥6.4.0 **<7.18.0** | https://github.com/advisories/GHSA-337j-9hxr-rhxg |

**Praktický dopad na PaginiumCMS (Beta 1):**

| Advisory | SPA-only nasadenie | Poznámka |
|----------|-------------------|----------|
| Open redirect (×2) | Nízky–stredný | Vyžaduje navigáciu na nevalidovaný externý URL (typicky user-controlled `to`). Interné admin trasy sú fixné; verejný web používa `Link`/`navigate` na vlastné slugy. |
| SSR `deserializeErrors()` | **Neuplatniteľné** | Admin SPA = `BrowserRouter`, **bez SSR hydration**. Riziko len pri budúcom SSR/Remix nasadení. |

**Prečo sme to nevideli v beta.2:** CI aj release checklist používali `npm audit --audit-level=**high**`; tieto nálezy sú **moderate**. Nie je to regresia aplikačného auditu (C11), ale **oneskorené upstream GHSA** po tagu.

**Riešenie:**

- `frontend/package.json` — `react-router-dom`: **`^7.18.1`**
- Odstránené v7 `future` flagy z `BrowserRouter` / `MemoryRouter` (v RR7 default)
- Overenie: `npm audit --audit-level=moderate` → **0 vulnerabilities**; `tsc` + Vitest green

**Súvis:** [SECURITY_REVIEW.md](SECURITY_REVIEW.md#post-publication-dependency-disclosures-after-v2110-beta2) · lokálny `SECURITY_ISSUES.md` → `C-RR-NPM-078`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-079"></a>

## ISS-079 – Editor profil blog blokuje uloženie code blocku — VYRIEŠENÉ (**2.1.0-beta.5**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-079)

**Symptóm:** Článok s fenced code blockom (`` ```markdown … ``` ``) sa dal vytvoriť, ale pri **opätovnom editovaní a uložení** toast: *„Profil editora nepovoľuje bloky kódu."* (profil **blog**, It.54).

**Príčina (dvojitá):**

1. Profil **blog** mal v whiteliste **`code`** (inline), nie **`codeBlock`** (fenced `` ``` ``).
2. `EditorContentValidator` (It.54) pri save kontroloval **celý obsah** proti capability whitelistu — nie len nové vloženie cez toolbar. Existujúci legitímny obsah sa pri editácii **nemohol uložiť**.

**Prečo to nie je OK (design):** Profily majú riadiť **toolbar / paste guard** (UX), nie **cenzúru publikácie** existujúceho obsahu. Bezpečnosť rieši `ContentSecuritySanitizer` + zákaz `<script>` / raw HTML v Markdowne.

**Riešenie (`v2.1.0-beta.5`):**

- `EditorContentValidator` — z capability whitelistu na save ostáva len **security** (script/iframe, raw HTML tagy v MD); formátovanie (code block, tabuľka, …) sa pri save **neblokuje**.
- Profil **blog** — pridaný **`codeBlock`** + tlačidlo **Blok kódu** v Markdown toolbar.
- PHPUnit: `EditorContentValidatorTest` — nové scenáre (blog + `` ``` ``, security-only reject).

**Follow-up:** [ITERATION_60.md](ITERATION_60.md) — nahradiť rigidné profily modulárnymi rozšíreniami editora v nastaveniach + RBAC.

**Súvis:** It.54 · `EditorProfileService` · `editor.wysiwyg.blocked.codeBlock` (toast pri paste zostáva).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-080"></a>

## ISS-080 – PHPStan: `ContentMetaController::getGroup()` — VYRIEŠENÉ (**2.1.0-beta.4**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-080)

**Symptóm:** `./scripts/iteration-gate.sh` / PHPStan L8 padol pri It.57 (`ContentMetaSuggestPanel` + `POST /api/admin/content/suggest-meta`):

```text
Call to an undefined method SettingsRepositoryInterface::getGroup()
```

**Príčina:** V `ContentMetaController.php` (riadok ~48) bol copy-paste názov metódy — rozhranie má **`group(string $key)`**, nie `getGroup()`.

**Riešenie:** `$this->settings->group('content')` · commit **`2091076`** (`v2.1.0-beta.4`).

**Súvis:** [ITERATION_57.md](ITERATION_57.md) · `SettingsRepositoryInterface`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-081"></a>

## ISS-081 – Dependabot: split `@tiptap/*` PR → npm peer conflict — VYRIEŠENÉ (**2.1.0-beta.4**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-081)

**Symptóm:** GitHub Dependabot PR **#9**, **#11**, **#12** (jednotlivé balíky `@tiptap/extension-*` 3.27.3 → 3.28.0) — CI **frontend** job red: `npm ci` / Vitest.

**Príčina:** `@tiptap/extension-image@3.28.0` vyžaduje peer `@tiptap/core@3.28.0`, zatiaľ čo ostatné balíky v lockfile ostali na **3.27.3**. Čiastočný merge jedného PR bez synchronizácie **všetkých** `@tiptap/*` naraz → `ERESOLVE` peer dependency.

**Riešenie (beta.4, commit `2091076`):**

- V `frontend/package.json` naraz **všetky** `@tiptap/*` → `^3.28.0`.
- Regenerácia `package-lock.json` (`rm package-lock.json && npm install` — starý lock držal 3.27.3).
- Dependabot PR #9/#11/#12 zatvorené s komentárom „superseded by beta.4".
- Bundled aj bezpečné PR **#6** (`league/commonmark` 2.8.3) a **#8** (frontend dev group).

**Prevencia:** Pri Tiptap upgrade vždy bumpnúť **celú rodinu** balíkov v jednom commite; nespoliehať sa na izolované Dependabot PR pre peer-linked monorepo balíky.

**Súvis:** Dependabot PR #6 ✅ · #8 ✅ · #7 ⏳ [ISS-082](#iss-082) · #10 ⏳ [ISS-083](#iss-083).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-082"></a>

## ISS-082 – Dependabot: `symfony/yaml` 8.x (PR #7) — ODLOŽENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-082)

**Symptóm:** Dependabot navrhol `symfony/yaml` **7.4.14 → 8.1.1** (major). CI na PR prešlo, ale merge by porušil `composer.json` constraint **`^7.0`**.

**Rozhodnutie:** PR **#7** zatvorené bez merge — samostatná migrácia Symfony YAML 8.x (breaking changes, overiť PHPUnit + YAML config loadery).

**Stav:** ⏳ Tech. dlh · plánovať mimo beta patchov.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-083"></a>

## ISS-083 – Dependabot: `eslint` 10.x (PR #10) — ODLOŽENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-083)

**Symptóm:** Dependabot navrhol `eslint` **9.39.5 → 10.7.0** (major). CI frontend padol (breaking config / pravidlá).

**Rozhodnutie:** PR **#10** zatvorené bez merge — upgrade eslint 10 vyžaduje migráciu flat config + `@typescript-eslint` kompatibilitu mimo rýchleho Dependabot merge.

**Stav:** ⏳ Tech. dlh · naviazať na [ISS-011](#iss-011) (ESLint warnings baseline).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-084"></a>

## ISS-084 – Samovolné odhlásenie v Chrome (kaskáda 401) — VYRIEŠENÉ (**2.1.0-beta.5**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-084)

**Symptóm:** Po ~20–30 min v admin paneli (Chrome) náhle redirect na `/login`. V logoch naraz:

- `GET /api/auth/me` → 401
- `GET /api/admin/counts` → 401
- `GET /api/media` → 401
- `GET /api/admin/users` → 401

**Príčina:**

1. **`DemoMode::sessionLifetimeSeconds()`** mal default **1440 s (24 min)** pre non-demo — `session.cookie_lifetime` sa nastaví raz pri login a **neobnovuje sa**.
2. Keepalive (`refreshUser` každé 4 min) obnovoval server-side `$_SESSION`, ale **nie Max-Age cookie** v prehliadači → Chrome cookie zahodil → všetky API naraz 401.
3. FE interceptor poslal viacnásobné `paginium:auth-expired` → logout.

**Riešenie (`v2.1.0-beta.5`):**

- Default session lifetime: **28800 s** (dev) / **7200 s** (prod) cez `DemoMode`.
- `SessionManager::refreshCookieLifetime()` — sliding `Set-Cookie` pri `SecureSessionManager::touch()` (login + každý autentizovaný request cez `AuthMiddleware`).
- FE: debounce `auth-expired` (2,5 s), single-flight `refreshUser()`.

**Ops:** `.env` odporúčanie `SESSION_LIFETIME=28800`, `VITE_API_URL=` (same-origin), po deployi vymazať staré cookies.

**Súvis:** [ISS-025](#iss-025) · [ISS-029](#iss-029) · [ISS-042](#iss-042) · `backend/bootstrap/session.php`

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-085"></a>

## ISS-085 – Rich navigácia: prázdna ikona + chýbajúci popis — VYRIEŠENÉ (**2.1.0-beta.5**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-085)

**Symptóm:** V admin navigácii nastavená **Lucide ikona** — na webe len prázdny rámček (hover tooltip). **Popis položky** sa nezobrazil na desktop top-level linkoch (mobile OK).

**Príčina:**

1. `navigationRich.ts` mal **hardcoded mapu 7 ikon** + case-sensitive lookup → `Settings`, `Globe`, `home` atď. zlyhali.
2. `navigationItemHasVisual()` vracalo true aj pri neexistujúcej ikone → prázdny bordered tooltip.
3. `Navbar.tsx` renderoval popis len v dropdown deťoch, nie pri root linkoch.

**Riešenie:**

- Dynamický lookup cez `lucide-react` `icons` + normalizácia názvu (`book-open` → `BookOpen`).
- `NavItemContent` — ikona + label + popis na desktop aj mobile.
- Admin preview v `NavigationItemRichFields` zobrazuje Lucide ikonu.

**Súvis:** [ITERATION_56.md](ITERATION_56.md)

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-086"></a>

## ISS-086 – Stored XSS cez HTML obsah (audit) — VYRIEŠENÉ (**2.1.0-beta.6**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-086)

**Nález:** `ContentSecuritySanitizer::sanitizeHtml()` používal `strip_tags()` — odstránil zakázané tagy, ale **nečistil atribúty** na `<a>` / `<img>`. Payloady typu `onerror=`, `javascript:` v `href` prešli a renderovali sa cez `dangerouslySetInnerHTML` na verejných stránkach aj v admin preview.

**Riešenie (defense-in-depth):**

- Backend: `HtmlDomSanitizer` — allow-list tagov + atribútov, blok `on*`, `style`, `javascript:` / `data:` URI.
- Frontend: `sanitizePublicHtml()` (DOMPurify) pred `dangerouslySetInnerHTML` v `MarkdownRenderer` a `MarkdownContentEditor`.

**Súvis:** `ContentSecuritySanitizer.php` · `frontend/src/utils/sanitizeHtml.ts`

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-087"></a>

## ISS-087 – Deploy script hardcoded credentials — VYRIEŠENÉ (**2.1.0-beta.6**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-087)

**Nález:** `scripts/deploy-frontend-lan.sh` mal default `192.168.10.26`, `marian`, port `49555` v repozitári.

**Riešenie:** Povinné `DEPLOY_HOST` + `DEPLOY_USER`, voliteľný `DEPLOY_SSH_PORT` (default 22). Health-check URL používa rovnaký `HOST` (pri `DEPLOY_SSH_HOST` alias nastav `DEPLOY_HEALTH_URL` / `DEPLOY_PUBLIC_URL`).

```bash
DEPLOY_HOST=192.168.x.x DEPLOY_USER=yourName DEPLOY_SSH_PORT=22 ./scripts/deploy-frontend-lan.sh
```

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-088"></a>

## ISS-088 – Backup import Zip-Slip — VYRIEŠENÉ (**2.1.0-beta.6**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-088)

**Nález:** `BackupManager::importBackup()` volal `$zip->extractTo()` bez validácie názvov entries (na rozdiel od extension importu).

**Riešenie:** `ZipEntryGuard::isSafeEntry()` pred extrakciou — odmietne `..`, absolútne cesty a Windows drive prefix.

**Súvis:** [ISS-086](#iss-086) audit · `SECURITY.md` Zip-Slip sekcia

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-089"></a>

## ISS-089 – npm audit high: React Router RSC CSRF (GHSA-qwww-vcr4-c8h2) — AKCEPTOVANÉ (SPA)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-089)

**Nález:** `npm audit` hlási **high** pre `react-router` 7.12.0–8.2.0 (teda aj **7.18.1**). Patch existuje len v **≥ 8.3.0** (peer **React ≥ 19.2.7**).

**Kontext:** Advisory explicitne uvádza: *„This only affects your application if you are using the unstable RSC APIs.“* PaginiumCMS je **SPA** (`BrowserRouter`), RSC nepoužíva.

**Riešenie (beta.7):** Zostať na `react-router-dom@7.18.1` + override `"react-router": "7.18.1"` ([ISS-078](#iss-078) redirect/XSS fixy). CI gate: `npm audit --audit-level=critical` + `frontend/package.json` → `auditConfig.ignore` pre **GHSA-qwww-vcr4-c8h2** (RSC-only, N/A pre SPA).

**Plná oprava (budúcnosť):** React 19 migrácia + RR 8.3+ (súvis [ISS-083](#iss-083)).

**Nespúšťať:** `npm audit fix --force` (downgrade na 7.11.0) ani override `react-router@8.3.0` bez React 19 — rozbije Vitest (`useOptimistic`).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-090"></a>

## ISS-090 – ESLint 10 peer conflict — VYRIEŠENÉ (**2.1.0-beta.7**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-090)

**Symptóm:** `eslint: "latest"` inštalovalo ESLint 10; `eslint-plugin-react-hooks@5` podporuje max ESLint 9 → `npm audit fix` padá na ERESOLVE.

**Riešenie:** Pin `eslint@^9.39.0`, `@eslint/js@^9.39.0`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-091"></a>

## ISS-091 – Vitest: 14 failed (react-router / useOptimistic) — VYRIEŠENÉ (**2.1.0-beta.7**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-091)

**Symptóm:** Po deps update: `The requested module 'react' does not provide an export named 'useOptimistic'` pri mock `react-router-dom`.

**Príčina:** Override `"react-router": "^8.3.0"` nútil RR 8 + React 18.

**Riešenie:** Odstránený RR 8 override; `react-router-dom@7.18.1` + override `7.18.1`. Vitest **75/75** files.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-092"></a>

## ISS-092 – Deploy env + skript syntax — VYRIEŠENÉ (**2.1.0-beta.7**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-092)

**Zmeny:**

- `.gitignore` — `scripts/deploy-frontend-lan.env.local` (dokumentácia v `PRIVATE_DOMAIN_DEPLOY.md` §16)
- `deploy-frontend-lan.sh` — opravené `${DEPLOY_HOST:?…}` / `${DEPLOY_USER:?…}` (post-beta.6 syntax)

**⚠️ Ops:** Nikdy necommitovať tokeny do `.env.example` — len placeholdery.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-093"></a>

## ISS-093 – ESLint `expand is not a function` — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-093)

**Symptóm:** `npm run lint` padá s `TypeError: expand is not a function` v `minimatch@3` (ESLint `@eslint/config-array`).

**Príčina:** `package.json` override `"brace-expansion": "^5.0.8"` — v5 mení CJS export (`{ expand }`), ale `minimatch@3` volá `require('brace-expansion')` ako funkciu.

**Riešenie:** Odstránený globálny `brace-expansion` override. ESLint 9 + lint prebehne. Dev-deps `brace-expansion` high (GHSA-mh99) ostáva do ESLint 10 upgrade ([ISS-083](#iss-083)). CI audit gate: `--audit-level=critical` ([ISS-089](#iss-089) RR high).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-094"></a>

## ISS-094 – Job scheduler run → 500 na produkcii (Docker storage + UI) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-094)

**Symptóm:** Po deployi na `paginiumcms.com` admin **Plánovač** — `POST /api/admin/jobs/{id}/run` → **500** (backup-scheduled, monitoring-pipeline, content-scheduled-publish). Neskôr API vracalo 200, ale UI stále vyzeralo nefunkčné.

**Príčiny (vrstvené):**

1. **Docker `www-data`** nemohol zapisovať do `backend/storage/app/content/data/jobs/runs.json` a `scheduler-state.json` (host user `marian` mal práva, kontajner nie).
2. **`display_errors` On** → PHP `Warning:` pred JSON telom → FE neparsovalo odpoveď.
3. **Frontend** považoval `result.success === false` (napr. „Backup not due“) za chybu toastu, hoci job bežal.
4. **`git pull` na prod** blokovaný lokálnymi hotfixmi v `LogStoragePaths.php`, `composer.json`, `Dockerfile`.

**Riešenie (It.62, commity `0fe21ec`…`f7a73f1`):**

- Storage: `chown marian:www-data`, dirs `2775`, test `touch` ako `www-data` v kontajneri.
- Backend: `ScheduledJobRunner::finalizeRun()`, pole `outcome`, `jobs:run` CLI.
- PHP: `docker/php/php.ini` — `display_errors=Off`; `FileWriter` `@file_put_contents`, `chmod 0664`.
- FE: toast pri HTTP success; badges Hotovo/Preskočené/Zlyhanie.
- Docs: [ITERATION_62.md](ITERATION_62.md), [deploy/CRON.md](deploy/CRON.md).

**Overenie:**

```bash
./stack.sh exec -u www-data php sh -c 'touch .../data/jobs/.write-test && rm ... && echo WRITE_OK'
./stack.sh exec php php backend/bin/console jobs:run backup-scheduled
# Admin /scheduler — zelený toast „Backup not due“, badge Preskočené v histórii
```

**Stav:** ✅ Opravené na prod · tag **`v2.1.0-beta.9`** pending.

---


### Súvisiace odkazy

- [Commit `0fe21ec`](https://github.com/techberode/paginiumcms-architecture/commit/0fe21ec)
- [Commit `f7a73f1`](https://github.com/techberode/paginiumcms-architecture/commit/f7a73f1)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-095"></a>

## ISS-095 – Maintenance pozadie „Neplatná URL“ pri uložení — VYRIEŠENÉ (**main `88cbe31`**)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-095)

**Symptóm:** Admin → **Nastavenia → Režim údržby → Pozadie (URL)** — výber obrázku z médií alebo upload → pri **Uložiť** chyba **„Neplatná URL“** (frontend Zod + backend validátor).

**Príčina:**

1. Pole `maintenance.heroImageUrl` malo pravidlo **`url`** (`FILTER_VALIDATE_URL` / Zod `.url()`).
2. Media picker a upload ukladajú **relatívnu cestu** typu `/storage/app/content/media/…`, nie `https://…`.
3. Login pozadie (`login.backgroundImageUrl`) používalo len **`string`** — preto tam rovnaký picker fungoval.
4. HTML `<input type="url">` v pickeri navyše odmietalo `/storage/` cesty v niektorých prehliadačoch.

**Riešenie (`88cbe31`):**

- `SettingsSchema`: `heroImageUrl` rules → `['string', 'max:2000']` (zarovnané s login pozadím).
- `LoginBackgroundImagePicker`: `type="text"` namiesto `type="url"`.
- PHPUnit: `SettingsRepositoryTest::testMaintenanceHeroImageUrlAcceptsStoragePath`.

**Deploy incident (2026-07-27):**

- Prvý deploy po analýze problému: `git pull` → **Already up to date** — oprava ešte **nebola pushnutá** na GitHub (lokálny commit chýbal).
- Po pushi `88cbe31`: `git pull` + `composer install --no-dev` + `npm run build:prod` + `./stack.sh restart php` → **OK**.

**Smoke:** Settings → Režim údržby → pozadie z médií → Uložiť bez chyby → Coming Soon / Údržba stránka zobrazí pozadie.

---


### Súvisiace odkazy

- [Commit `88cbe31`](https://github.com/techberode/paginiumcms-architecture/commit/88cbe31)
- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-096"></a>

## ISS-096 – 502 Bad Gateway hneď po `./stack.sh restart php` — INFORMATÍVNE (nie bug)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-096)

**Symptóm:** Po `./stack.sh restart php` okamžitý `curl` na `/api/health` vráti **502 Bad Gateway** (HTML od host nginx).

**Príčina:** PHP-FPM kontajner ešte reštartuje (0–5 s). Host nginx proxy na `127.0.0.1:8089` nemá kam poslať request.

**Riešenie:** Po reštarte počkať **5–10 s**, potom:

```bash
cd /var/lib/docker/compose/paginiumcms
./stack.sh ps          # php = Up
curl -s http://127.0.0.1:8089/api/health
```

Ak 502 **pretrváva** >30 s → `./stack.sh logs --tail=50 php` (parse error, .env, permissions).

**Súvisiace logy (očakávané správanie, nie chyba deployu):**

- Počas **režimu údržby** bez staff session: `GET /api/pages`, `/api/navigation` → **503** (MaintenanceModeMiddleware).
- `GET /api/auth/me` → **401** keď session vypršala.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-097"></a>

## ISS-097 – Newsletter odberateľia bez admin prehľadu — VYRIEŠENÉ (It.61)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-097)

**Symptóm:** Návštevník sa prihlási na newsletter (Coming Soon / Údržba), admin **nevidí zoznam** prihlásení ani odhlásení v UI.

**Stav dnes (2026-07-27):**

| Čo funguje | Čo chýba |
|------------|----------|
| `POST /api/maintenance/newsletter` — uloží email | Admin stránka / API zoznam odberateľov |
| Flat-file `data/newsletter/subscribers.json` | Export CSV, filtre, odhlásenie (unsubscribe) |
| `NewsletterRepository::findAll()` v BE (bez HTTP) | Dashboard KPI „newsletter subscribers“ |

**Kde sú dáta (workaround na serveri):**

```bash
# APP_ROOT = /var/www/paginiumcms.com
cat backend/storage/app/content/data/newsletter/subscribers.json | jq .
# alebo v Dockeri:
./stack.sh exec php cat /var/www/html/backend/storage/app/content/data/newsletter/subscribers.json
```

Formát záznamu: `{ "id", "email", "subscribedAt", "source" }` — `source` napr. `coming_soon`, `under_maintenance`.

**Plán:** [It.61 — Newsletter vo footeri](ITERATION_61.md) — admin prehľad + export; rozšíri sa aj na existujúcich maintenance odberateľov. Odhlásenie (unsubscribe) mimo MVP It.61 alebo v2.

**Riešenie (It.61):** `POST /api/newsletter/subscribe`, admin `/newsletter`, `GET /api/admin/newsletter/subscribers` + CSV export; settings skupina `newsletter.footerEnabled`.

**Stav:** ✅ Opravené (It.61).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-098"></a>

## ISS-098 – Demo login 401, prázdna odpoveď v prehliadači (CORS) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-098)

**Symptóm:** Na `demo.paginiumcms.com` prihlásenie v prehliadači zlyhá; DevTools ukazuje `POST /api/auth/login` → **401**, `Content-Type: text/html`, **prázdne telo**. Konzola: `Unexpected end of JSON input`. Rovnaký request cez `curl` **bez** hlavičky `Origin` vracia **200 JSON**.

**Príčina:** Prehliadač posiela `Origin: https://demo.paginiumcms.com`. Tuupola `CorsMiddleware` pri neznámom origin vracia HTTP 401 s prázdnym telom (`ERR_ORIGIN_NOT_ALLOWED`). Backend auth vôbec nespustí — nie je to zlé heslo ani session.

Typický trigger: demo `.env` má `APP_URL=https://paginiumcms.com` (skopírované z produkcie) namiesto demo domény.

**Diagnostika (copy-paste na serveri):**

```bash
grep '^APP_URL=' /var/www/paginiumcms-demo/.env

curl -sS -o /dev/null -w 'bez Origin: HTTP %{http_code}\n' \
  -X POST 'https://demo.paginiumcms.com/api/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}'

curl -sS -o /dev/null -w 's Origin:    HTTP %{http_code} CT:%{content_type}\n' \
  -X POST 'https://demo.paginiumcms.com/api/auth/login' \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://demo.paginiumcms.com' \
  -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}'
# Ak prvá 200 a druhá 401 text/html → ISS-098 potvrdené
```

**Riešenie (okamžité — env):**

```bash
sed -i 's|^APP_URL=.*|APP_URL=https://demo.paginiumcms.com|' /var/www/paginiumcms-demo/.env
cd /var/lib/docker/compose/paginiumcms-demo && ./stack.sh up -d
```

**Riešenie (kód — odolné voči zlému APP_URL):**

- `SameOriginCorsMiddleware` — ak `Origin` sedí s `Host` (+ `X-Forwarded-Proto`), origin sa povolí pre daný request.
- CORS chyby vracajú JSON `{ "code": "cors_rejected" }` namiesto prázdneho HTML.
- Bootstrap dopĺňa `DEMO_PUBLIC_URL` a `VITE_PUBLIC_URL` do statickej CORS allow-listy.

**Deploy + smoke:** [deploy/DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md)

**Stav:** ✅ Opravené (2026-07-27). User potvrdil: login v prehliadači funguje.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-099"></a>

## ISS-099 – Demo `demo:reset-if-due` — Permission denied na `plugins.json` — OPS (nie kód)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-099)

**Symptóm:** Na demo serveri host príkaz `php backend/bin/console demo:reset-if-due` spadne:

```
fopen(.../storage/app/demo/data/plugins.json): Permission denied
RuntimeException: Unable to open plugin registry
```

Web login a admin API v Dockeri môžu fungovať normálne.

**Príčina:** Host CLI (SSH user, cron) vs Docker `www-data`. Console bootne `PluginManager` pred samotným reset príkazom — registry `data/plugins.json` vyžaduje zápis (`fopen` režim `c+`). Demo storage vytvoril kontajner; host user nemá group write. Rovnaký pattern ako **[ISS-094](#iss-094)** (It.62, `runs.json` na produkcii).

**Diagnostika:**

```bash
ls -la /var/www/paginiumcms-demo/backend/storage/app/demo/data/
id -un
cd /var/www/paginiumcms-demo && php backend/bin/console demo:reset-if-due
```

**Riešenie (odporúčané — zdieľaná skupina):**

```bash
cd /var/www/paginiumcms-demo
sudo chown -R "$(id -un):www-data" backend/storage
sudo find backend/storage -type d -exec chmod 2775 {} \;
sudo find backend/storage -type f -exec chmod 664 {} \;
php backend/bin/console demo:reset-if-due
```

**Alternatíva:** spúšťať cron cez `./stack.sh exec -T php php backend/bin/console demo:reset-if-due` v demo compose adresári.

**Crontab (po oprave práv):**

```cron
*/15 * * * * cd /var/www/paginiumcms-demo && /usr/bin/php backend/bin/console demo:reset-if-due >> /var/log/paginium-demo-reset.log 2>&1
```

**Docs:** [deploy/DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md) § [ISS-099](#iss-099) · [ITERATION_62.md](ITERATION_62.md) · [deploy/CRON.md](deploy/CRON.md)

**Stav:** ℹ️ Dokumentované (2026-07-27). Vyžaduje jednorazovú ops oprávu na demo serveri — nie zmenu aplikačného kódu.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-100"></a>

## ISS-100 – S-DEMOCREDS — demo heslo v public settings — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-100)

**Závažnosť:** Stredná (security audit finding)  
**Stav:** ✅ **`v2.1.0-beta.11`**

**Symptóm:** `curl …/api/settings/public` vracal `demo.credentials.password` v plain texte.

**Riešenie:** Heslo odstránené z public settings a admin demo status. Nový `POST /api/demo/quick-login` (len `DEMO_MODE=true`, rate limit). Login stránka — tlačidlo „Prihlásiť ako demo admin“.

**Overenie:**

```bash
curl -s https://demo.paginiumcms.com/api/settings/public | jq '.data.demo'
# žiadny kľúč credentials / password
curl -sS -X POST https://demo.paginiumcms.com/api/demo/quick-login | jq '.success'
# true
```

**Docs:** [SECURITY_REVIEW.md](SECURITY_REVIEW.md) S-DEMOCREDS · [ITERATION_13.md](ITERATION_13.md) v4

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-101"></a>

## ISS-101 – Editor biela obrazovka (`capabilities.includes`) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-101)

**Závažnosť:** Vysoká (demo trial blocker)  
**Stav:** ✅ **`v2.1.0-beta.11`**

**Symptóm:** Nová stránka/článok → biela obrazovka; Console: `TypeError: e.capabilities.includes is not a function`.

**Príčina:** BE `/api/settings/public` posiela `editor.profiles[].capabilities` ako `{ enabled: string[] }`; FE očakával flat array.

**Riešenie:** `normalizeEditorProfile()` v `frontend/src/utils/editorProfiles.ts`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-102"></a>

## ISS-102 – Demo celé API HTTP 500 — chýbajúci `demo/data/` strom — VYRIEŠENÉ (ops)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-102)

**Závažnosť:** Vysoká (demo outage — login, health, všetko)  
**Stav:** ✅ Ops opravené na demo serveri (2026-07-27)

**Symptóm:** Všetky endpointy vracajú HTTP **500** vrátane `/api/health`:

```json
{ "success": false, "error": "Vnútorná chyba servera" }
```

PHP log:

```
PluginRegistry.php: Unable to open plugin registry: .../backend/storage/app/demo/data/plugins.json
FirewallBanStore.php: mkdir(): Permission denied
FirewallBanStore.php: Failed to open stream: .../whitelist.json: No such file or directory
```

Diagnostika: `demo/data missing`; test `$APP_ROOT/storage/app/demo/...` → **WRITE FAIL** (nesprávna cesta).

**Príčina:** Adresár `backend/storage/app/demo/data/` neexistoval a Docker `www-data` nemohol vytvoriť podstrom (`mkdir` Permission denied). Bootstrap pri každom requeste otvára plugin registry a firewall flat-files — bez zapisovateľného `data/` padá celá aplikácia.

**Riešenie (jednorazový bootstrap na serveri):**

```bash
APP_ROOT=/var/www/paginiumcms-demo
STORAGE="$APP_ROOT/backend/storage"

sudo mkdir -p "$STORAGE/app/demo/data" "$STORAGE/app/demo/data/security/firewall" \
  "$STORAGE/cache" "$STORAGE/backups"
sudo chown -R "$(id -un):www-data" "$STORAGE"
sudo find "$STORAGE" -type d -exec chmod 2775 {} \;
sudo find "$STORAGE" -type f -exec chmod 664 {} \;
# minimálne JSON pre bootstrap — detail v DEMO_DEPLOY.md § First-run
```

**Overenie po fixe:**

```
HOST_WRITE_OK · DOCKER_WRITE_OK · GET /api/health → HTTP 200
demo:reset-if-due → ⏭ not_due   (normálne, ak interval ešte neuplynul)
```

Manuálny snapshot: admin **Demo** → **Reset demo seed**.

**Súvis:** **[ISS-099](#iss-099)** (host cron vs `www-data` — miernejší prípad, web môže bežať). Rovnaký permission pattern ako **[ISS-094](#iss-094)** (It.62).

**Docs:** [deploy/DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md) § First-run + [ISS-102](#iss-102) · [ITERATION_62.md](ITERATION_62.md)

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-103"></a>

## ISS-103 – PHPUnit OTP/2FA flaky — lokálny `.env` polluluje testy — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-103)

**Závažnosť:** Stredná (lokálny dev / CI — falošné regresie)  
**Stav:** ✅ **`v2.1.0-beta.12`**

**Symptóm:** `./scripts/run-all-tests.zsh` občas zlyhá na OTP/2FA testoch:

- `AuthControllerTest` — register verify-otp očakáva 201, dostane 403
- `CommentsControllerTest` — approve comment OTP očakáva 202, dostane 200
- `TwoFactorSimpleTest` — 2FA enable očakáva 200, dostane 404

**Príčina:** Vývojársky `.env` s `DEMO_MODE=true` (demo server) sa načítal aj počas PHPUnit HTTP testov. Demo guard a demo storage menili správanie auth/workflow endpointov.

**Riešenie:**

1. `bootstrap/app.php` — preskočiť Dotenv load keď `APP_ENV=testing`
2. `backend/tests/Http/TestCase.php` — vynútiť `DEMO_MODE=false` pred bootstrapom + reset v tearDown
3. `CsrfMiddleware` — explicit exempt pre `/api/auth/register/verify-otp` a `…/resend-otp`
4. `DeveloperModeGateTest` — obnoviť `APP_ENV` v tearDown

**Overenie:** Gate zelený aj s lokálnym `.env` obsahujúcim `DEMO_MODE=true`.

**Docs:** [developer/TESTING.md](developer/TESTING.md) · [CHANGELOG.md](../../CHANGELOG.md) beta.12

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-104"></a>

## ISS-104 – A3-JOBDEPLOY — ADMIN deploy bypass cez jobs API — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-104)

**Závažnosť:** Stredná (security audit 2026-07-27)  
**Stav:** ✅ fix **`v2.1.0-beta.15`**

**Symptóm:** `/api/admin/system/update/run` vyžaduje `SUPER_ADMIN`, ale ADMIN mohol:

1. `PUT /api/admin/jobs/system-deploy` s `payload.ref`
2. `POST /api/admin/jobs/system-deploy/run` → spustiť deploy chain

**Riešenie:**

- `JobRegistryStore` — `payload` systémových jobov sa pri update neprepíše
- `PrivilegedJobPolicy` + `JobsController` — handler `system.deploy` vyžaduje `SUPER_ADMIN` na run/update
- `ScheduledJobRunner::runDue()` — `system.deploy` sa nikdy nespustí automaticky cez cron API

**Testy:** `JobRegistryStoreTest`, `JobsControllerPrivilegedDeployTest`, `PrivilegedJobPolicyTest`

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-105"></a>

## ISS-105 – A6-GEOIP — cleartext outbound lookup — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-105)

**Závažnosť:** Nízka (defense-in-depth)  
**Stav:** ✅ fix **`v2.1.0-beta.15`**

**Symptóm:** `GeoIPService` volal `http://ip-api.com` cez `file_get_contents` bez `OutboundUrlGuard`.

**Riešenie:** HTTPS endpoint + `OutboundUrlGuard::isAllowed()` pred fetchom.

**Testy:** `GeoIPServiceTest`

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-106"></a>

## ISS-106 – A8-DEMOMODE — demo režim na produkcii — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-106)

**Závažnosť:** Nízka / informačná (audit 2026-07-27)
**Stav:** ✅ fix **`v2.1.0-beta.16`**

**Symptóm:** `DEMO_MODE=true` s `APP_ENV=production` mohlo aktivovať demo strom, quick-login a izolované úložisko na produkčnej inštancii (ops misconfiguration).

**Riešenie:** `DemoMode::isEnabledFromEnv()` fail-closed — na produkcii demo **nikdy** neaktivuje; `warnIfMisconfigured()` pri boote zapíše security warning do error logu.

**Testy:** `DemoModeTest`, `DemoControllerTest`

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-107"></a>

## ISS-107 – A7-NEWSLETTER — subscribe hardening — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-107)

**Závažnosť:** Nízka (audit 2026-07-27)
**Stav:** ✅ fix **`v2.1.0-beta.16`** (Newsletter v2 Phase 1)

**Symptóm:** `/api/maintenance/newsletter` bez honeypotu; footer/maintenance subscribe len s globálnym rate limitom; duplicitná odpoveď odhaľovala existujúci email.

**Riešenie:** `NewsletterSubscribeRateLimitMiddleware` (5/h IP, 3/deň email), honeypot na oboch endpointoch, generic success message, preference allow-list.

**Testy:** `NewsletterControllerTest`, `NewsletterSubscribeRateLimitMiddleware` (via HTTP suite)

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-108"></a>

## ISS-108 – A9-GHSERVICE — GitHub content sync bez OutboundUrlGuard — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-108)

**Závažnosť:** Informačná (konzistencia s [ISS-054](#iss-054) / A6)
**Stav:** ✅ fix **`v2.1.0-beta.16`**

**Symptóm:** `GitHubService::apiRequest()` volal curl priamo na `api.github.com` bez `OutboundUrlGuard` (fixný host, nízke riziko).

**Riešenie:** `OutboundUrlGuard::fromEnv()->assertAllowed($url)` pred každým outbound requestom (rovnako ako `GitHubReleaseClient`).

**Testy:** `GitHubServiceTest`

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-109"></a>

## ISS-109 – Newsletter footer CTA príliš objemný — medzistav — It.61 Phase 5

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-109)

**Závažnosť:** Nízka (UX / vizuál, nie funkčná chyba)  
**Stav:** ✅ **vyriešené** — variant B shipped **`v2.1.0-beta.18`**

**Symptóm:** Zapnutý footer newsletter (`footerEnabled`) zväčšuje pätičku — zvýraznený CTA box s e-mailom a odkazom pôsobí „neohrabane“. Modal s preferenciami je OK, problém je **footprint vo footeri**.

**Požadované riešenie (product):**

1. **Minimal** — len odkaz „Prihlásiť sa na novinky“ → modal (existujúci flow).
2. **Inline email** — samotné tenké pole alebo odkaz s rozklikom, bez objemného boxu.
3. **Side tab** — skrytá bočná záložka s rozbalením panelu (footer vizuálne čistý).

**Riešenie:** It.61 Phase 5 variant **B** — inline e-mail pole + šípka vo footer stĺpci (bez gradient boxu); modal s preferenciami nezmenený. Varianty A/C + `footerDisplayMode` zostávajú v backlogu.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-110"></a>

## ISS-110 – Prod SEO `/api/seo/page/home` → HTTP 500 (cache collision)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-110)

**Závažnosť:** Vysoká (prod — Googlebot + visitors)  
**Stav:** ✅ **opravené** — **`v2.1.0-beta.21`** (hotfix na `paginiumcms.com` 2026-07-30)

**Symptóm:** Admin → Logy → `http_access` ERROR: `GET /api/seo/page/home 500`, `GET /api/seo/article/… 500`. Intermittent: po `content:diagnose --fix` prvý request 200, ďalšie znova 500.

**Príčina:** `ContentController` a `SeoController` zdieľali cache kľúč `content.page.{slug}`. ContentController ukladá **API pole** (`path`, `frontMatter`, …). `FileDriver` je JSON-only — po načítaní SEO dostalo **array** a padlo na `$content->getStatus()`.

**Riešenie:**

1. `SeoController` číta obsah cez `ContentRepository` (nekache-uje model objekty).
2. `ContentCacheService` item kľúče → `content.page.payload.{slug}` (API only); legacy kľúče sa maže pri invalidate.
3. Access log: 401/404 → INFO (nie WARNING); scan/bot 404 šum.

**Overenie (prod):** 5× `GET /api/seo/page/home` = 200; po `GET /api/pages/home` stále SEO 200.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-111"></a>

## ISS-111 – LoggerTest + PHPStan regresia (`APP_ENV=testing` log skip)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-111)

**Závažnosť:** Stredná (CI / full test suite)  
**Stav:** ✅ **opravené** — **`v2.1.0-beta.21`**  
**Zdroj:** `alltests_300726_0842.log` (2026-07-30)

**Symptóm:**
1. PHPUnit — 6× `LoggerTest` fail: mock `write()` expected once, called 0×.
2. PHPStan L8 — `AccessLogServiceTest.php:59` redundant `is_array()` (always true).

**Príčina:** Po úprave proti znečisteniu Admin → Logs (`Logger` skipuje durable writes pri `APP_ENV=testing`) unit testy s mock writerom už nevolali `write()`. PHPStan: `FileHelper::readJson()` vždy vracia `array`.

**Riešenie:**
1. `Logger::isTestingEnvironment()` — výnimka `PAGINIUM_LOGGER_ALLOW_TESTING=1` pre unit testy s mock writerom.
2. `LoggerTest` — flag v setUp/tearDown + regresný test `testSkipsWritesInTestingWithoutAllowFlag`.
3. `AccessLogServiceTest::readEntries()` — priamy `array_values(FileHelper::readJson(...))`.

**Overenie:** PHPUnit `LoggerTest` + `AccessLogServiceTest`; PHPStan L8 na test súboroch.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-112"></a>

## ISS-112 – Lock badge „aktívne pred viac než 56 rokmi“

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-112)

**Závažnosť:** Nízka (admin UX)  
**Stav:** ✅ **opravené v working tree** — pripojiť k **ďalšiemu release** (po `v2.1.0-beta.23`)  
**Zdroj:** manuálny QA — editor stránky/článku, `LockIndicator` (2026-07-30)

**Symptóm:** Pri zámku dokumentu badge ukazuje napr. **„Upravujete vy · aktívne pred viac než 56 rokmi“** namiesto „pred chvíľou / sekundami“.

**Príčina:** Backend `/api/locks/*` posiela `lastHeartbeat` / `acquiredAt` ako **Unix sekundy**. `parseContentDate()` v `frontend/src/utils/contentDates.ts` mal zlú heuristiku:

```ts
// BUG: value * 1000 > 1e12 — pre každý timestamp po ~2001 (sekundy)
// vyhodnotí true → Date(seconds) ako ms → ~1970 → „56 rokov“
new Date(value * 1000 > 1_000_000_000_000 ? value : value * 1000)
```

`LockIndicator` volá `formatRelativeTime(lock.lastHeartbeat)` → `formatDistanceToNow` od epochy.

**Riešenie:**

1. `parseContentDate` — ms vs s podľa `Math.abs(value) >= 1_000_000_000_000` (ms) inak `value * 1000`.
2. Regresný Vitest v `contentDates.test.ts` (unix seconds „now“ nesmie obsahovať „rok“).

**Súbory:** `frontend/src/utils/contentDates.ts`, `contentDates.test.ts`  
**Deploy:** stačí **LAN/FE** `deploy-frontend-lan.sh` (čistý FE); na prod s ďalším tagom.

**Overenie:** otvoriť editor stránky → zámok „Upravujete vy · aktívne pred …“ bez rokov; `npx vitest run src/utils/contentDates.test.ts`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-113"></a>

## ISS-113 – Static content bez security headers (audit) — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-113)

**Nález:** CSP, HSTS, X-Frame-Options a ďalšie hlavičky prichádzali len z PHP `SecurityMiddleware` na `/api/*`. Statické súbory z `frontend/dist` (nginx `location /`, `/assets/`) ich nedostávali.

**Riešenie (repo):** Snippety `docs/deploy/nginx-security-headers-https.conf` / `-http.conf`, include v `nginx-paginiumcms.com.conf`, `nginx-demo.paginiumcms.com.conf`, LAN test/dev configoch; Docker `docker/nginx/security-headers.conf` + mount v `docker-compose.prod.yml`.

**Ops (server):**

```bash
sudo cp docs/deploy/nginx-security-headers-https.conf /etc/nginx/snippets/paginium-security-headers-https.conf
# Merge includes from docs/deploy/nginx-paginiumcms.com.conf into the ACTIVE vhost (see prod note below)
sudo nginx -t && sudo systemctl reload nginx
curl -sI https://paginiumcms.com/ | grep -iE 'strict-transport|content-security|x-frame'
```

**Prod deploy note (2026-07-31):** Homelab `paginiumcms.com` uses `/etc/nginx/sites-enabled/paginiumcms` (plain file — **not** `paginiumcms.com`). Snippet was present on disk but **missing `include` in location blocks**, so headers never appeared despite manual `add_header` attempts on the wrong file. Fixed by wiring `include …paginium-security-headers-https.conf` into `location /`, `/assets/`, and `/.well-known/security.txt`. Full runbook: [deploy/NGINX_API.md](deploy/NGINX_API.md#production-host-paginiumcmscom--ops-pitfalls-2026-07-31).

**Overenie prod (2026-07-31):** ✅ HSTS + CSP + X-Frame-Options on `/` and `/.well-known/security.txt`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-114"></a>

## ISS-114 – CSRF exempt prefix bez word boundary — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-114)

**Nález:** `str_starts_with($path, $prefix)` bez `/` mohlo v budúcnosti omylom exemptnúť napr. `/api/newsletter-admin/…` pri prefixe `/api/newsletter`.

**Riešenie:** `$path === $prefix || str_starts_with($path, $prefix . '/')` v `CsrfMiddleware::isExempt()`. Regresný test `testSimilarPrefixDoesNotExemptCsrf`.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-115"></a>

## ISS-115 – `X-Powered-By: PHP/x.y` fingerprinting — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-115)

**Riešenie:** `expose_php = Off` v `docker/php/php.ini` (rebuild/restart PHP kontajnera).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-116"></a>

## ISS-116 – Hardcoded `192.168.10.26` v TRUSTED_PROXIES default — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-116)

**Riešenie:** Default `127.0.0.1,::1` v `ClientIpResolver::trustedProxiesFromEnv()`; `bootstrap/app.php` používa jednotný helper. LAN/prod za nginx musí nastaviť `TRUSTED_PROXIES` v `.env` explicitne (dokumentované v `.env.example`).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-117"></a>

## ISS-117 – GHSA-qwww-vcr4-c8h2 (React Router RSC CSRF) — N/A (SPA)

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-117)

Pozri **[ISS-089](#iss-089)**. PaginiumCMS = `BrowserRouter` SPA bez RSC/SSR. CI: `npm audit --audit-level=critical`. Nepoužívať `npm audit fix --force` na RR 8 bez React 19.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-118"></a>

## ISS-118 – `security.txt` — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-118)

**Riešenie:** `frontend/public/.well-known/security.txt` (+ `backend/public/` pre API docroot), nginx `location = /.well-known/security.txt` pred SPA fallback, `Content-Type: text/plain`.

**Contact:** `security@paginiumcms.com` — overiť mailbox pred public launch.

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-119"></a>

## ISS-119 – Docker stack neštartuje po reboot servera — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-119)

**Symptóm:** Po reštarte Ubuntu hosta produkcia aj demo vracajú **502**; kontajnery `paginiumcms-prod-*` / `paginiumcms-demo-*` sú **Down**, kým admin manuálne nespustí `./stack.sh up -d`.

**Príčina:** `docker-compose.prod.yml` nemal `restart:` policy → Docker default **`no`**. Služba `docker.service` sa síce zapne, ale existujúce kontajnery sa nespustia.

**Riešenie:** `restart: unless-stopped` na `php` a `nginx` v `docs/deploy/docker-compose.prod.yml`; na serveri skopírovať do oboch stack dir a `./stack.sh up -d` (recreate s novou policy).

**Overenie:**

```bash
docker inspect --format '{{.Name}} restart={{.HostConfig.RestartPolicy.Name}}' $(docker ps -aq --filter name=paginium)
```

Očakávané: `unless-stopped` pre všetky štyri kontajnery.

Runbook: [deploy/DEPLOY.md](deploy/DEPLOY.md#f-boot-autostart-docker-restart-policy--iss-119).

---


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)

---

<a id="iss-120"></a>

## ISS-120 – CI PHPUnit log: TOTP / 2FA secret v GitHub job logu — VYRIEŠENÉ

[↑ Prehľad](#prehlad) · [English](../en/ISSUES.md#iss-120)

**Nález:** GitHub Actions `backend` job spúšťal `./vendor/bin/phpunit` priamo do konzoly. Verbose testy (napr. `TwoFactorSimpleTest`) vypisujú diagnostický echo (`otpauth://`, provisioning URI, secret polia) → **recoverable secrets v verejnom/tímovom CI logu**.

**Riziko:** Stredné — test secrets nie sú produkčné, ale log je dlhodobo indexovaný a zdieľateľný; porušuje baseline „žiadne tajomstvá v logoch“.

**Riešenie (CI only):**

- `.github/scripts/run-backend-tests-ci.sh` — PHPUnit → raw súbor mimo konzoly, potom publish len sanitizovaného logu.
- `.github/scripts/sanitize-ci-log.py` — redakcia `otpauth://`, JSON secret kľúčov, Bearer tokenov, …
- `.github/scripts/verify-ci-log-redaction.sh` — fail-closed grep pred publikáciou.
- `.github/workflows/ci.yml` — backend job volá wrapper namiesto priameho phpunit.

**Lokálne skripty ( zámerne bez zmeny ):**
- `iteration-gate.sh` — PHPUnit `--no-output` (echo sa nezobrazí).
- `run-all-tests.zsh` — plný log mimo repa; pred uploadom sanitizovať (pozri nižšie).

**Dokumentácia / maintainer:**
- `LOCAL_TEST_LOGS.md.example` → skopírovať na gitignored `LOCAL_TEST_LOGS.md` v koreni repa.
- [developer/TESTING.md](developer/TESTING.md#test-output--secret-redaction) — verejný súhrn.

**Overenie:**

```bash
.github/scripts/run-backend-tests-ci.sh
# očakávané: „CI log redaction verification: OK“, v konzole len [REDACTED] namiesto otpauth
```

---

## Súvisiace dokumenty

- [user/BRANDING.md](user/BRANDING.md) — logo a favicon (**2.0.52**)
- [user/ACCESS_CONTROL.md](user/ACCESS_CONTROL.md) — RBAC + Path ACL v nastaveniach (**2.0.52**)
- [developer/RELEASE.md](developer/RELEASE.md) — release **2.0.52** · **2.1.0-beta.7**
- [CHANGELOG.md](../../CHANGELOG.md) — 2.0.52 · **2.1.0-beta.7**
- [ITERATION_54.md](ITERATION_54.md) — editor profiles ([ISS-079](#iss-079))
- [ITERATION_56.md](ITERATION_56.md) — rich navigation ([ISS-085](#iss-085))
- [ITERATION_57.md](ITERATION_57.md) — suggest-meta ([ISS-080](#iss-080))
- [ITERATION_62.md](ITERATION_62.md) — scheduler prod hardening ([ISS-094](#iss-094))
- [ITERATION_61.md](ITERATION_61.md) — footer newsletter + admin zoznam odberateľov ([ISS-097](#iss-097)); Phase 5 footer UX ([ISS-109](#iss-109))
- [deploy/DEMO_DEPLOY.md](deploy/DEMO_DEPLOY.md) — demo nasadenie + [ISS-098](#iss-098) CORS + [ISS-099](#iss-099) cron/storage + [ISS-102](#iss-102) bootstrap
- [deploy/CRON.md](deploy/CRON.md) — produkčný crontab + Docker storage
- [developer/TESTING.md](developer/TESTING.md) — PHPUnit izolácia (`LoginAttemptTracker`)
- [ITERATION_44.md](ITERATION_44.md) — It.44d index filtre ([ISS-038](#iss-038))
- [ROADMAP.md](ROADMAP.md) – plánované iterácie (It.41+, It.47–49)
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) – It.29+ detail
- [ITERATION_47.md](ITERATION_47.md) – notification connector auth ([ISS-013](#iss-013))


### Súvisiace odkazy

- [CHANGELOG](../../CHANGELOG.md)
