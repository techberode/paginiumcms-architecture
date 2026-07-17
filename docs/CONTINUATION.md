# PaginiumCMS – Continuation context & implementation plan

> **Language note:** Project documentation is being migrated to English (see `.cursorrules`).  
> For per-iteration details see **`docs/ITERATION_{N}.md`** (1–21). Full index in [`docs/README.md`](README.md#documentation-index).

> For Iteration 6 details see [`docs/ITERATION_6.md`](ITERATION_6.md).

> This document is the “startup briefing” for continuing development. It contains:
> 1. what is **done and functional**, 2. **status** of all requirements (DONE/PARTIAL/MISSING),
> 3. **phased plan** for next iterations, 4. **ready-to-paste continuation prompt** at the end.

Architecture: React SPA (Vite 8, TS) ↔ REST API (Slim 4) ↔ PHP 8.5 core (PHP-DI, PHPStan L8,
strict types) ↔ **Flat-File** storage (no SQL database).

---

## Iteration 6 – DONE (2026-07-15)

- **Notifications:** SMTP settings, connectors (email/ntfy/Discord/Telegram/webhook), incident alerts, admin overview API + FE
- **Analytics:** `Reporter`, `AnalyticsMiddleware`, visit overview in notifications dashboard
- **Auth UI:** register, forgot/reset password, change password modals; email reset without production demo token
- **Toast settings:** position, duration, enable/disable, debug mode from admin settings
- **Tests:** PHPUnit + Vitest extended; see `docs/ITERATION_6.md`

---

## Iteration 14 – DONE (2026-07-15)

- **Code policy:** `CodePolicyEngine`, `SecurityScanner`, 422 violations, `codePolicy` settings group
- **Code editor:** fixed path resolution, `FileInfo[]` API, allowed `Http/Extensions` + theme paths
- **Developer unlock FE:** `DeveloperUnlockGate`, `api/developer.ts`
- See `docs/ITERATION_14.md`

---

## Iteration 7 – DONE (2026-07-15)

- **Dashboard:** locks panel, conflicts panel, health metrics, analytics chart + realtime visitors
- **API:** `GET /api/admin/dashboard/overview`, `GET /api/admin/health/*`, `GET /api/admin/analytics/realtime`
- **RealtimeTracker** implemented; Health module wired to DI and routes
- See `docs/ITERATION_7.md`

---

## 1. Hotové a plne funkčné (Iterácie 1–3 + infraštruktúra)

### Iterácia 1 – Zamykanie obsahu ✅
- Backend: `Core/Locking/*` (`ContentLock`, `LockManager` nad `data/locks.json`, `flock`), `Http/Controllers/Locking/LockController`, `Http/Routes/locking.php`.
- Frontend: `api/locks.ts`, `hooks/useContentLock.ts`, `components/locking/LockIndicator.tsx`.
- API: `POST /api/locks/{acquire,heartbeat,release}`, `GET /api/locks`, `DELETE /api/locks/{id}`.

### Iterácia 2 – Auto-save, revízie, detekcia konfliktov ✅
- Backend: `Core/FlatFile/Services/ContentRevision`, `Exception/ContentConflictException`, `Core/Drafts/*` (`DraftManager` nad `data/drafts/`), `DraftController` + `drafts.php`. `ContentController` posiela `revision`, kontroluje `baseRevision` → HTTP 409, ukladá commit správu. `VersionManager::hydrate()` opravený.
- Frontend: `api/versions.ts`, `api/drafts.ts`, `hooks/useAutoSave.ts` (60 s), `components/versioning/DiffViewer.tsx`, `MarkdownEditor.tsx` integrácia. `VersionHistory.tsx` prepojený na `DiffViewer`.

### Iterácia 3 – 3-way merge, riešenie konfliktov ✅
- Frontend: `utils/merge3.ts` (diff3), `components/versioning/ConflictResolver.tsx`, auto-merge + manuálne riešenie v `MarkdownEditor`. `api/conflicts.ts`.
- Backend: `Core/Conflict/*` (`ConflictLogger` nad `data/conflicts.json`, `flock`), `ContentController` loguje 409, `Admin/ConflictController` + `conflicts.php` (`GET/DELETE /api/admin/conflicts`).

### Iterácia 4 – Settings engine + Error Handler + zdieľaná validácia ✅
- Backend: `Core/Settings/*` (`SettingsSchema`, `SettingsRepository` nad `data/settings.json`, `flock`), `SettingsController` + `settings.php`. `Core/Validation/*` (`Validator`, `ValidationRules`, `ValidationException`), `ValidationController` + `validation.php`. `Http/Support/ApiErrorHandler` + Slim middleware; 404 zjednotený.
- Frontend: `api/settings.ts`, `api/validation.ts`, `SettingsView`, `SettingsContext` + `useSettings`, `utils/validation.ts` + `validatePasswordPolicy`. `useAutoSave` číta interval z nastavení.
- API: `GET/PUT /api/admin/settings/{group}`, `GET /api/settings/public`, `DELETE /api/admin/settings`, `GET /api/validation/rules`.

### Iterácia 5 – Používatelia + kalenie autentifikácie ✅
- Backend: `Admin/UserController` (CRUD + roly), `users.php`, `TwoFactorMiddleware` na všetkých `/api/admin/*` routách, `TwoFactorInterface::isTotpVerified()` / `isTwoFactorPassed()`, oprava `GET /api/auth/me`.
- Frontend: `api/users.ts`, `UsersManager`, `TwoFactorSettings`, dvojkrokový `LoginModal`, odstránený Bearer z `api/client.ts`.
- API: `GET/POST/PUT/DELETE /api/admin/users`, `POST /api/auth/2fa/verify-login`.

### Testovacia infraštruktúra ✅
- Backend: PHPUnit 10, PHPStan L8. Spúšťanie: `./vendor/bin/phpunit`, `./vendor/bin/phpstan analyse --level=8`.
- Frontend: **Vitest** + Testing Library + jsdom. `npm test` (26 testov). Testy: `merge3`, `ConflictResolver`, `validation`.

### In-Memory Cache ✅ (DONE)
- `Core/Cache/*`: `CacheManager` → `ChainedDriver` (Memory→File write-through), `MemoryDriver` (per-FPM-worker), `FileDriver`. Zapojené do `ContentController` (čítania cez `ContentCacheService`, zápisy invalidujú). Používa aj `RateLimitMiddleware`.
- Drobný dlh: `CacheManager::deleteByPrefix()` je v docblocku, ale neimplementované.

---

## 2. Stav požiadaviek (audit repozitára)

Legenda: ✅ DONE · 🟡 PARTIAL (existuje časť, treba dokončiť/prepojiť) · ⛔ MISSING

| # | Požiadavka | Stav | Čo existuje / čo chýba |
|---|---|---|---|
| A | **Rozšírená správa používateľov + roly** | ✅ | `UserController`, `users.php`, FE `UsersManager` + `api/users.ts`. RBAC cez `RoleMiddleware`. |
| B | **Bezpečné JWT / HttpOnly cookie auth** | ✅ | PHP session + HttpOnly cookie (`SessionManager`, `AuthMiddleware`). Bearer token odstránený z FE. `SecureSessionManager` zostáva voliteľný dlh. |
| C | **2FA / TOTP** | ✅ | `TwoFactorManager`, `TwoFactorController`, `TwoFactorMiddleware` na admin routách, FE setup + login flow. |
| D | **Developer mód + zamknutý CodeEditor** | 🟡 | Backend hotový (`DeveloperModeGate`, unlock cez TOTP/dev-token). FE unlock gate v Code Editor (It.14). Ďalší polish v It.8. |
| E | **Editory (Code/Markdown/WYSIWYG) + admin nastavenia** | 🟡 | Markdown editor plne integrovaný; CodeEditor základný s policy + file tree API (It.14); Monaco nevyužitý; WYSIWYG nezapojený. |
| F | **Validácia na dvoch úrovniach (zdieľané pravidlá)** | ✅ | `Validator`, `ValidationRules`, `ValidationException`, `GET /api/validation/rules`, FE `utils/validation.ts` + `validatePasswordPolicy`. |
| G | **SMTP nastavenia v administrácii** | 🟡 | `NotificationService` + `EmailAdapter` existujú, ale `EmailAdapter` používa iba `mail()` (bez SMTP), nie je v DI, reset hesla neposiela e-mail. **Settings engine hotový** – It. 6 pridá SMTP skupinu do schémy. |
| H | **Konektory ntfy / Discord / Telegram / …** | ⛔ | Žiadny kód/adaptéry/konfigurácia. |
| I | **Toast: zap/vyp, pozícia, debug mód** | 🟡 | `NotificationContext` + `useToast` fungujú (fix top-right, len `duration`). Chýba prepínanie, pozícia, debug mód, perzistencia. |
| J | **.XML Feeds (RSS/sitemap) + nastavenia** | ⛔ | Žiadny generátor/route/nastavenia. |
| K | **Demo modul (izolované MOCK dáta)** | ⛔ | Neexistuje; staré mocky odstránené. |
| L | **API Tracker + reporty návštevnosti + notifikácie** | 🟡 | `Analytics/*`: `Tracker`, `DeviceDetector`, `GeoIPService`, modely hotové. `AnalyticsManager`, `Reporter`, `RealtimeTracker`, `AnalyticsMiddleware` sú **prázdne stuby**. Bez API/route/dashboardu. |
| M | **Jednotný Error Handler** | ✅ | `ApiErrorHandler` + Slim error middleware v `bootstrap/app.php`; 404 catch-all zjednotený; 422 s `errors` mapou. |
| N | **Blueprint / Schema Engine** | ⛔ | Iba hardcoded `Page`/`Article`; žiadny systém definície typov/polí. |
| O | **In-Memory Cache + agresívna cache** | ✅ | Hotové (viď §1); dorobiť `deleteByPrefix()`. |
| P | **Media manager / DAM** | 🟡 | Backend kompletný (`Modules/Media/*`, `MediaController`, `media.php`: list/upload/patch/delete). Chýba FE (route, `MediaManager`, `api/media.ts`) a **plný DAM**: viacúrovňové priečinky, `.meta.json` sidecar, hromadné operácie, zamykanie assetov. |
| Q | **Nastavenia (settings) úložisko** | ✅ | `SettingsRepository`, `SettingsSchema`, `data/settings.json`, `SettingsController`, FE `SettingsView` + `SettingsContext`, `GET /api/settings/public`. **Odomknuté pre It. 6+ (SMTP, toast, SEO, feedy).** |
| R | **Priebežné pridávanie API + FE prepojení** | 🟡 | Priebežná úloha naprieč iteráciami. |
| S | **Automatické SEO tagy + rozšírené SEO nastavenia** | ⛔ | Žiadne generovanie meta/OG/Twitter/canonical/JSON-LD z obsahu ani admin SEO nastavenia (predvolené šablóny title/description, social image). |
| T | **SSO (SAML / OAuth)** | ⛔ | Iba lokálne prihlásenie; žiadny SAML/OAuth provider ani flat-file konfigurácia. |
| U | **Jemnozrnné ACL (na úrovni súborov/priečinkov)** | ⛔ | Existuje len RBAC podľa rolí (`AuthorizationManager`, `RoleMiddleware`); žiadne per-súbor/per-priečinok ACL (JSON). |
| V | **Bezpečnostný audit log (+ CSV export)** | 🟡 | Modul `Modules/Audit/*` + `AuditTrailController` + `audittrail.php` existujú; overiť/dotiahnuť pokrytie akcií (login, úpravy, ACL) a CSV export, plus FE prehľad. |
| W | **Politika validácie a kompatibility kódu (CodeEditor)** | 🟡 | `SyntaxChecker` (PHP `php -l`, JSON, YAML; JS/CSS vždy OK), `CodeEditorManager` allow/deny paths – **path resolution rozbitá**. Chýba security policy (zakázané konštrukty: `eval`, `exec`, …), JS/CSS lint, centrálna policy v `settings.json`, FE validácia pred save. |
| X | **Moduly / Témy / Funkcionality editovateľné v CodeEditori** | 🟡 | Backend: `GatedCodeEditorController`, Developer gate, deklarované cesty (`Modules`, `plugins`, `themes`, `config`). **Chýba:** opravené cesty, `backend/plugins/` a `resources/views/themes/` neexistujú, FE `FileTree` nesedí s API, Monaco nevyužitý, create/delete/restore backup. |
| Y | **Systém doplnkov: vytvorenie / inštalácia / import** | ⛔ | `HookManager` + `EventDispatcher` existujú (len testy), **žiadny** `PluginManager`, manifest (`plugin.json`), API install/import/enable/disable, admin UI. Import musí prejsť politikou kódu (W). |
| Z | **ZÁKON: Externé doplnky mimo Jadra Backendu** | ⛔ | Architektonické pravidlo schválené, **nie implementované**. Cieľ: všetok kód pluginov v `backend/app/Http/Extensions/` (+ FE v `frontend/src/extensions/`), **nikdy** v `backend/app/Core/`. Auto-discovery routes z `Http/Routes/extensions/`. |
| AA | **ZÁKON: API endpoint = kompatibilný FE súbor** | ⛔ | Priebežné pravidlo bez enforce. Pri každom novom endpointe musí vzniknúť `api/*.ts` + komponent/route podľa architektúry CMS. Chýba scaffold/checklist. |

### Architektonické ZÁKONY (platia od Iterácie 14+)

1. **ZÁKON EXTERNÝCH DOPLNKOV:** Všetky doplnky vytvorené mimo jadra CMS sa ukladajú/importujú výhradne do **HTTP vrstvy mimo Core**:
   - Backend: `backend/app/Http/Extensions/{plugin-id}/` (manifest, PHP, routes)
   - Routes: `backend/app/Http/Routes/extensions/{plugin-id}.php` (auto-discovered)
   - Frontend: `frontend/src/extensions/{plugin-id}/` (komponenty, api klient)
   - **Zakázané:** zápis do `backend/app/Core/`, `backend/bootstrap/`, `backend/vendor/`

2. **ZÁKON API↔FE:** Každý nový API endpoint v `Http/Routes/` musí mať zodpovedajúci frontendový súbor:
   - `frontend/src/api/{modul}.ts` – typovaný klient
   - komponent/route v `frontend/src/components/` alebo `frontend/src/extensions/`
   - záznam v `docs/architecture/API.md` (mapovanie endpoint ↔ FE)

3. **ZÁKON POLITIKY KÓDU:** Žiadny vlastný kód (modul/téma/doplnok) sa neuloží bez prechodu `CodePolicyEngine` (syntax + security + kompatibilita). Zlyhanie → HTTP 422, žiadny zápis na disk.

---

## 3. Fázový plán ďalších iterácií (podľa náročnosti a závislostí)

Poradie je zvolené tak, aby najprv vznikli **základy** (settings, error handler, validácia),
od ktorých závisí väčšina ostatných funkcií, a aby každá iterácia priniesla funkčný celok.

> **Stály bod (každá iterácia):** ku každému novému modulu/funkcionalite pridať **testy** –
> backend PHPUnit (`vendor/bin/phpunit`) + frontend Vitest (`npm test`), plus PHPStan L8.
> Iterácia sa považuje za hotovú až keď sú testy zelené a dokumentácia aktualizovaná.

### Iterácia 4 – Základy: Settings engine + Error Handler + zdieľaná validácia ✅
*Hotové – odomyká It. 6+.*
- **Settings engine (Q):** `SettingsRepository`, `SettingsSchema`, `data/settings.json`, `SettingsController`, `settings.php`, FE `SettingsView` + `SettingsContext` + `useSettings`, `GET /api/settings/public`.
- **Jednotný Error Handler (M):** `ApiErrorHandler` + Slim middleware; 404 zjednotený.
- **Zdieľaná validácia (F):** `Validator`, `ValidationRules`, `GET /api/validation/rules`, FE `utils/validation.ts`.

### Iterácia 5 – Používatelia + kalenie autentifikácie ✅
- **User management (A):** `UserController` (list/create/update/delete, priradenie rolí), `users.php`, FE `UsersManager` + `api/users.ts` + nav (ADMIN/SUPER_ADMIN).
- **2FA vynútenie + UI (C):** `TwoFactorMiddleware` na všetkých `/api/admin/*` routách + auth protected skupine; login s TOTP krokom (`verify-login`), FE `TwoFactorSettings` + dvojkrokový `LoginModal`.
- **Auth kalenie (B):** HttpOnly session cookie ako jediný zdroj pravdy; odstránený mŕtvy Bearer interceptor z `api/client.ts`; `GET /api/auth/me` vracia `{ success, user }`.

### Iterácia 6 – Notifikácie: SMTP + konektory + toast konfigurácia 🟡 (stredná) — *závisí od It. 4*
- **SMTP (G):** pridať mailer (Symfony Mailer / PHPMailer), `EmailAdapter` s reálnym SMTP z `settings.json`, zapojiť `NotificationService` do DI, reset hesla posiela e-mail, admin sekcia „E-mail / SMTP“ + test-send.
- **Konektory (H):** `Core/Notification/Adapters/{NtfyAdapter,DiscordAdapter,TelegramAdapter,WebhookAdapter}`, konfigurácia + aktivácia v admine, test-send na kanál.
- **Toast konfigurácia (I):** rozšíriť `NotificationContext` o pozíciu, zap/vyp, **debug mód**; perzistencia cez `settings.json`; admin sekcia „Notifikácie“.

### Iterácia 7 – Admin dashboard + monitoring + API Tracker 🟡 (stredná)
- **Dashboard prepojenia:** zapojiť existujúce `GET /api/locks` a `GET /api/admin/conflicts` do UI (panel zámkov + konfliktov), Health metriky.
- **API Tracker / Analytics (L):** dokončiť `AnalyticsManager`, `Reporter`, `RealtimeTracker`, `AnalyticsMiddleware`; route `/api/admin/analytics/*` (reporty návštevnosti, realtime); FE dashboard s grafmi; voliteľné **notifikácie cez konektor** (It. 6) pri udalostiach/prahoch.

### Iterácia 8 – Media manager / DAM (FE) + editory + developer unlock UI 🟡 (stredná)
- **Media FE + DAM (P):** `MediaManager` (grid, upload drag&drop, náhľady, metadáta/altText, mazanie), `api/media.ts`, route `/media`; **plný DAM**: viacúrovňové priečinky, `.meta.json` sidecar metadáta, hromadné operácie (upload/presun/mazanie), prepojenie assetov so systémom zamykania (It. 1).
- **Editory (E):** admin voľba markdown/WYSIWYG, zapojiť `WysiwygEditor` (TipTap) do obsahu, Monaco do `CodeEditor`; rozšírené admin nastavenia editorov (uložené v `settings.json`).
- **Developer unlock UI (D):** gate modal pred `/code-editor` → `POST /api/admin/developer/unlock` (TOTP alebo dev-token), stav zámku.

### Iterácia 9 – SEO: automatické tagy + rozšírené nastavenia 🟢 (nízka–stredná) — *závisí od It. 4*
- **SEO (S):** `Core/Seo/*` automatické generovanie meta description, canonical, Open Graph a Twitter Card + JSON-LD z obsahu/front matter; admin sekcia „SEO“ (predvolené šablóny title/description, default social image, robots, prepínače); prepojenie na feedy/sitemap.

### Iterácia 10 – XML Feeds 🟢 (nízka–stredná) — *závisí od It. 4, S*
- **Feeds (J):** `Core/Feed/*` generátor RSS + sitemap, verejné routy `/feed.xml`, `/sitemap.xml`, admin „Feeds“ nastavenia (počet položiek, kategórie, zapnutie).

### Iterácia 11 – SSO + jemnozrnné ACL + bezpečnostný audit log 🔵 (vyššia)
- **SSO (T):** SAML/OAuth provider(y) s konfiguráciou vo flat-file (`data/settings.json` / `data/sso/*.json`), mapovanie na role.
- **Jemnozrnné ACL (U):** ACL na úrovni súborov/priečinkov (JSON konfigurácie), rozšírenie `AuthorizationManager` o zdrojové oprávnenia.
- **Audit log (V):** dotiahnuť `Modules/Audit` – pokrytie akcií (login, úpravy, ACL zmeny), CSV export, FE prehľad.

### Iterácia 12 – Blueprint / Schema Engine 🔴 (vysoká)
- **Blueprints (N):** flat-file definície typov obsahu a polí (`data/blueprints/*.json`), `Core/Blueprint/*` (typy polí, validácia proti schéme, prepojenie na zdieľanú validáciu z It. 4), dynamické admin formuláre na FE. Veľká architektonická zmena – aplikovať po schválení návrhu.

### Iterácia 13 – Demo modul (izolované MOCK dáta) 🟡 (stredná)
- **Demo (K):** samostatný base-path úložiska (napr. `storage/app/demo/`) a `DEMO_MODE` prepínač, poskytovateľ MOCK dát, ktorý **nikdy** nezapisuje do reálneho obsahu; garancia integrity (read-only mosty, oddelené DI väzby).

### Iterácia 14 – Politika kódu + oprava CodeEditor základov 🔵 (vyššia) — *závisí od It. 4, D*
- **Politika kódu (W):** `Core/CodePolicy/*` (alebo `Http/CodePolicy/`) – `CodePolicyEngine`: syntax (`SyntaxChecker` rozšírený) + security scan (zakázané PHP konštrukty) + kompatibilita (whitelist namespaces/súborov). Konfigurácia v `settings.json` skupina `codePolicy`. HTTP 422 pri zlyhaní.
- **Oprava CodeEditor:** fix `CodeEditorManager` path resolution (`projectRoot` bez duplicitného `backend/`), zapojiť `CodeEditorLogger` do read/write.
- **FE:** zosúladiť `FileTree` s API response, zobraziť policy chyby pred save.
- **Testy:** `CodePolicyEngineTest`, `SyntaxCheckerTest`, opravené cesty CodeEditor.
- **Dokumentácia:** `docs/developer/CODING_STANDARDS.md` (politika kódu, ZÁKONY, checklist).

### Iterácia 15 – Externé doplnky: úložisko mimo Core + runtime 🔴 (vysoká) — *závisí od It. 14*
- **ZÁKON EXTERNÝCH DOPLNKOV (Z):** `backend/app/Http/Extensions/{id}/` – manifest `plugin.json`, PHP súbory, hooks. Routes v `Http/Routes/extensions/`. FE v `frontend/src/extensions/{id}/`. **Nikdy v Core.**
- **Plugin runtime (Y):** `Http/Extensions/PluginManager` – install (zip/upload), import, enable/disable, uninstall. Flat-file registry `data/plugins.json`. Validácia importu cez `CodePolicyEngine` – neprejde → odmietnutie.
- **HookManager** zapojiť do DI; pri `enable` registrácia hookov z manifestu.
- **API:** `GET/POST /api/admin/extensions`, `POST /api/admin/extensions/import`, `PUT /api/admin/extensions/{id}/enable|disable`, `DELETE /api/admin/extensions/{id}`.
- **FE:** `ExtensionsManager` (zoznam, install, enable/disable), `api/extensions.ts`.
- **Testy:** PluginManager, import s valid/invalid kódom, HookManager integrácia.

### Iterácia 16 – CodeEditor plný stack: moduly, témy, doplnky 🟡 (stredná) — *závisí od It. 14, 15*
- **CodeEditor (X):** Monaco editor namiesto textarea; Developer unlock UI (It. 8/D). Editovať `Modules/`, `Http/Extensions/`, `resources/views/themes/`, `config/` – len po prechode politiky kódu.
- **CMS témy:** vytvoriť `backend/resources/views/themes/` (alebo `backend/themes/`), settings skupina `theme`, základný render pipeline.
- **Create/delete súborov**, obnova záloh (`FileBackup`), diff pred save.
- **Testy:** FE CodeEditor komponent, E2E save s policy gate.

### Iterácia 17 – ZÁKON API↔FE scaffold + šablóny rozšírení 🟡 (stredná) — *závisí od It. 15*
- **ZÁKON API↔FE (AA):** CLI/admin scaffold – pri vytvorení endpointu generuje `Http/Routes/extensions/{id}.php` + `frontend/src/api/{id}.ts` + komponent šablónu + záznam v `API.md`.
- **CodeEditor wizard:** „Nový doplnok" – vygeneruje štruktúru podľa ZÁKONU (manifest, route, api, komponent) do `Http/Extensions/`.
- **CONTRIBUTING.md** checklist: endpoint bez FE = CI fail (voliteľný lint script).
- **Testy:** scaffold generuje validnú štruktúru, PHPStan na vygenerovanom kóde.

### Priebežne (R): postupné pridávanie API pre admin rozhranie a FE napájanie na existujúce/nové endpointy. **Vždy dodržať ZÁKON API↔FE (AA).**

---

## 4. Ready-to-paste pokračujúci prompt

> Skopíruj nasledujúci blok do nového chatu na pokračovanie.

```
Pokračujeme vo vývoji PaginiumCMS (Flat-File CMS, žiadna DB).
Stack: React SPA (Vite 8, TS, TailwindCSS, React Query, Axios) ↔ Slim 4 REST API ↔
PHP 8.5 jadro (PHP-DI, PHPStan level 8, striktné typy). Komunikuj po slovensky.
Pravidlá: striktné typovanie (PHP declare(strict_types=1), plné namespaces; TS strict),
absolútne cesty, čistý a rozšíriteľný kód, každú zmenu hneď zaznamenať do docs/,
bloky kódu komentovať, radikálne zmeny aplikovať až po schválení. Výsledky hneď v kóde.

ARCHITEKTONICKÉ ZÁKONY (docs/CONTINUATION.md §2):
1. EXTERNÉ DOPLNKY mimo Core → backend/app/Http/Extensions/{id}/ + Http/Routes/extensions/
   + frontend/src/extensions/{id}/. Nikdy do backend/app/Core/.
2. API↔FE: každý nový endpoint = typovaný api/*.ts + komponent/route + záznam v API.md.
3. POLITIKA KÓDU: vlastný kód (modul/téma/doplnok) sa uloží len po prechode CodePolicyEngine.
   Detail: docs/developer/CODING_STANDARDS.md

HOTOVÉ A FUNKČNÉ (nemeniť bez dôvodu):
- Iterácia 1: zamykanie obsahu (Core/Locking, LockIndicator, /api/locks/*).
- Iterácia 2: auto-save koncepty (Core/Drafts), revízie (ContentRevision), detekcia
  konfliktu (409, baseRevision), DiffViewer, VersionHistory.
- Iterácia 3: 3-way merge (utils/merge3.ts), ConflictResolver, ConflictLogger
  (data/conflicts.json), /api/admin/conflicts.
- Iterácia 4: Settings engine (data/settings.json, SettingsContext), ApiErrorHandler,
  zdieľaná validácia (ValidationRules, GET /api/validation/rules).
- Cache: Core/Cache (Memory+File chained) zapojená do ContentController.
- CodeEditor (čiastočný): GatedCodeEditorController, SyntaxChecker, FileBackup,
  DeveloperModeGate – path resolution rozbitá, FE FileTree/Monaco nehotové.
- Testy: PHPUnit + PHPStan L8 + Vitest (validation, merge3, ConflictResolver).

STAV PODNETOV je v docs/CONTINUATION.md (§2). Backend často existuje, chýba FE/prepojenie.

PLÁN (docs/CONTINUATION.md §3), poradie podľa náročnosti/závislostí:
It.5 Používatelia + roly, 2FA vynútenie + UI, kalenie auth (HttpOnly session).
It.6 Notifikácie: reálne SMTP, konektory (ntfy/Discord/Telegram/webhook), toast konfigurácia.
It.7 Admin dashboard: zámky + konflikty + Health + API Tracker/Analytics.
It.8 Media manager / DAM (FE) + editory (WYSIWYG/Monaco) + developer unlock UI.
It.9 SEO: automatické tagy + rozšírené SEO nastavenia v admine.
It.10 XML Feeds (RSS/sitemap) + admin nastavenia.
It.11 SSO + jemnozrnné ACL + bezpečnostný audit log.
It.12 Blueprint/Schema engine (po schválení návrhu).
It.13 Demo modul s izolovanými MOCK dátami.
It.14 Politika kódu (CodePolicyEngine) + oprava CodeEditor path/FE kontraktu.
It.15 Externé doplnky mimo Core: PluginManager, install/import, HookManager DI.
It.16 CodeEditor plný stack: moduly/témy/doplnky editovateľné s policy gate.
It.17 ZÁKON API↔FE: scaffold + šablóny rozšírení + CONTRIBUTING checklist.

STÁLY BOD: ku každému novému modulu/funkcionalite pridať testy (PHPUnit + Vitest).

Začni Iteráciou 5 (User management + 2FA + kalenie auth), alebo It.14 ak priorita
je CodeEditor/doplnky. Postupuj po iteráciách; po každej: PHPUnit + PHPStan + Vitest + docs.
Radikálne zmeny (Blueprint, SSO, Plugin runtime, zmena auth) najprv navrhni a počkaj na schválenie.
```

---

*Aktualizované po Iterácii 4 + rozšírení plánu (doplnky, politika kódu, ZÁKONY). Podrobnosti: `SETTINGS.md`, `STORAGE.md`, `API.md`, `VERSIONING.md`, `ROADMAP.md`, `PLUGINS.md`.*
