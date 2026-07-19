# PaginiumCMS – Roadmapa spolupráce a správy obsahu

> Flat-File architektúra. Žiadna databáza. Všetok stav je v JSON / Markdown / asset súboroch na disku.

Legenda: ✅ hotové · 🚧 rozpracované · ⏳ plánované · 🔴 kritická priorita jadra

**Aktuálna verzia:** 2.0.25 · **Posledná iterácia:** [42+](ITERATION_BACKLOG.md) ✅ · **Ďalšia:** [43+ backlog](ITERATION_BACKLOG.md) ⏳


| Iterácia | Názov                                                         | Priorita                             |
| -------- | ------------------------------------------------------------- | ------------------------------------ |
| 19       | FlatFile storage, indexácia, stránkovanie                     | ✅                                    |
| 20       | Core hardening & produkcia                                    | ✅                                    |
| 21       | API kontrakt & testovanie                                     | ✅                                    |
| **22**   | **Ops finish & verejné feedy**                                | **✅**                                |
| **24**   | **Full DAM v1 + stock knižnica**                              | **✅**                                |
| **26**   | **Media preview lightbox + 2.0.14 hotfix**                    | **✅**                                |
| **27**   | **Admin view modes + SEO panel**                              | **✅**                                |
| **28**   | **Bulk actions platform**                                     | **✅**                                |
| **6**    | **Notifikácie (SMTP, konektory, toast)**                      | **✅**                                |
| 29       | Cron planner + Job Queue                                      | ✅ [ITERATION_29.md](ITERATION_29.md) |
| 30       | Content editory + cache fix + admin zoznamy                   | ✅ [ITERATION_30.md](ITERATION_30.md) |
| 42       | Admin sidebar counts + list controls                          | ✅                                    |
| **50**   | **[In-App Micro Firewall (WAF)](ITERATION_50.md)**            | **✅ 2.0.26**                         |
| **43**   | **Pokročilé vyhľadávanie (FE + BE, rýchle skoky v kontexte)** | **⏳**                                |
| **44**   | **Filtre a zoradenia (admin + verejný FE)**                   | **⏳**                                |
| **45**   | **[Redis driver detail](ITERATION_45.md)**                    | **🔵 → It.49**                       |
| **46**   | **[Server metrics agent](ITERATION_46.md)**                   | **⏳ doplnok It.7**                   |
| **47**   | **[Notification connector auth](ITERATION_47.md)**            | **⏳ ntfy token / test**              |
| **48**   | **[PHP templates + static/dynamic web](ITERATION_48.md)**     | **⏳ static HTML + admin toggle**     |
| **49**   | **[Unified cache (file + Redis)](ITERATION_49.md)**           | **⏳ hosting-aware prepínač**         |
| 25       | Setup wizard (profil webu)                                    | ⏳ odložené                           |
| 30+      | [Backlog modulov](ITERATION_BACKLOG.md)                       | ⏳                                    |
| 6–7      | Notifikácie, dashboard                                        | ✅ It.6 · ✅ It.7                      |
| 8–10     | DAM, SEO, feedy                                               | 🟢                                   |
| 11–16    | SSO, plugins, Monaco                                          | 🔵 · It.16 ✅ (plugin editor → It.15) |
| 17–18    | API scaffold, i18n                                            | priebežne / po jadre                 |


---



## Iterácia 1 – Systém zamykania obsahu (Locking) ✅

Zabraňuje tomu, aby dvaja používatelia prepisovali ten istý dokument naraz.

**Backend (PHP 8.4+,** `backend/app/Core/Locking/`**):**

- `Models/ContentLock.php` – model zámku (vlastník, token, heartbeat, expirácia).
- `Contracts/LockManagerInterface.php` – kontrakt manažéra zámkov.
- `Services/LockManager.php` – flat-file manažér nad `data/locks.json`, atomický zápis cez `flock(LOCK_EX)`, auto-release po TTL.
- `Exception/LockConflictException.php` – konflikt zámku (HTTP 409).
- `Http/Controllers/Locking/LockController.php` – HTTP vrstva `/api/locks/*`.
- `Http/Routes/locking.php` – auto-discovered routes.

**Frontend (React + TypeScript):**

- `src/api/locks.ts` – typované API volania.
- `src/hooks/useContentLock.ts` – acquire pri otvorení, heartbeat každých 30 s, release pri odchode.
- `src/components/locking/LockIndicator.tsx` – vizuálny badge „kto upravuje".

**Kľúčové parametre:**


| Parameter                     | Hodnota                                       |
| ----------------------------- | --------------------------------------------- |
| Heartbeat interval (frontend) | 30 s                                          |
| Auto-release TTL (backend)    | 300 s (5 min)                                 |
| Register zámkov               | `backend/storage/app/content/data/locks.json` |
| Súbežnosť                     | `flock(LOCK_EX)` nad registrom                |


---



## Iterácia 2 – Auto-Save, verzovanie a detekcia konfliktov ✅

Optimistické zamykanie + auto-save konceptov + porovnanie verzií.

**Backend (PHP 8.4+):**

- `Core/FlatFile/Services/ContentRevision.php` – deterministický revízny odtlačok (`sha1` z obsahu + kanonický front matter), nezávislý od poradia kľúčov.
- `Core/FlatFile/Exception/ContentConflictException.php` – konflikt obsahu (HTTP 409) nesúci serverovú verziu.
- `Core/Drafts/` – auto-save koncepty:
  - `Models/Draft.php` – model konceptu (title, content, status, baseRevision, savedBy, savedAt).
  - `Contracts/DraftManagerInterface.php` – kontrakt.
  - `Services/DraftManager.php` – flat-file manažér nad `data/drafts/{type}/{slug}.json`.
- `Http/Controllers/Content/DraftController.php` + `Http/Routes/drafts.php` – API `/api/drafts/{type}/{slug}` (PUT/GET/DELETE).
- `Http/Controllers/Content/ContentController.php` – **optimistické zamykanie**: pri uložení sa kontroluje `baseRevision`; pri nezhode → HTTP 409 s `conflict` kontextom. Do serializácie pribudlo pole `revision`. Ukladá sa aj commit správa (`message`).
- `Core/Versioning/Services/VersionManager.php` – oprava `hydrate()` (predtým vracal prázdny objekt) + typové čistenie.

**Frontend (React + TypeScript):**

- `src/api/versions.ts` – typované volania histórie/porovnania/obnovy verzií.
- `src/api/drafts.ts` – typované volania auto-save konceptov.
- `src/hooks/useAutoSave.ts` – auto-save konceptu každých 60 s (len pri zmene, stavy idle/saving/saved/error).
- `src/components/versioning/DiffViewer.tsx` – porovnanie dvoch verzií vedľa seba (side-by-side).
- `src/components/backend/MarkdownEditor.tsx` – **integrácia**: `LockIndicator`, auto-save, `baseRevision`, commit správa, obnova konceptu a riešenie 409 konfliktu.
- `src/components/CodeEditor/VersionHistory.tsx` – **prepojený** na nový `DiffViewer` (opravené importy `useApi`/`DiffViewer` a deštrukturácia `delete: del`).

**Kľúčové parametre:**


| Parameter                     | Hodnota                                                      |
| ----------------------------- | ------------------------------------------------------------ |
| Auto-save interval (frontend) | 60 s                                                         |
| Register konceptov            | `backend/storage/app/content/data/drafts/{type}/{slug}.json` |
| Revízny odtlačok              | `sha1(content + canonical(frontMatter))`                     |
| Konflikt obsahu               | HTTP 409 + `conflict.serverContent/serverRevision`           |




## Iterácia 3 – Riešenie konfliktov (Conflict Resolution) ✅

Trojcestné zlúčenie (diff3) na klientovi + manuálne riešenie + flat-file log konfliktov.

**Frontend (React + TypeScript):**

- `src/utils/merge3.ts` – riadkový 3-way merge (diff3). Base = pôvodne načítané, mine = moje, theirs = server. Automaticky zlúči nekonfliktné zmeny, konfliktné bloky vráti na rozhodnutie. Overené 13 scenármi.
- `src/components/versioning/ConflictResolver.tsx` – modálne UI: pre každý konflikt voľba Moja / Serverová / Obe / Ručne, náhľad výsledku.
- `src/components/backend/MarkdownEditor.tsx` – pri 409 sa pokúsi o auto-merge; ak čistý → douloží, inak otvorí `ConflictResolver`. Udržiava `baseContent` (spoločný predok).
- `src/api/conflicts.ts` – admin klient logu konfliktov.

**Backend (PHP 8.4+):**

- `Core/Conflict/Models/ConflictRecord.php`, `Contracts/ConflictLoggerInterface.php`, `Services/ConflictLogger.php` – flat-file log `data/conflicts.json` (ohraničený, `flock`-safe).
- `Http/Controllers/Content/ContentController.php` – pri 409 zaznamená konflikt do logu.
- `Http/Controllers/Admin/ConflictController.php` + `Routes/conflicts.php` – `GET/DELETE /api/admin/conflicts` (ADMIN).

**Real-time notifikácie:** toast pri auto-merge (info), pri konflikte (error), pri zrušení (info).

**Kľúčové parametre:**


| Parameter        | Hodnota                                   |
| ---------------- | ----------------------------------------- |
| Merge algoritmus | riadkový diff3 (LCS kotvy) na klientovi   |
| Auto-merge       | nekonfliktné zmeny bez zásahu používateľa |
| Log konfliktov   | `data/conflicts.json` (max 200, `flock`)  |


---



## Iterácia 4 – Settings engine + Error Handler + zdieľaná validácia ✅

Základ pre všetky admin nastavenia (SMTP, notifikácie, SEO, feedy v ďalších iteráciách).

**Backend (PHP 8.4+):**

- `Core/Settings/SettingsSchema.php` – schéma skupín (general, content, editor).
- `Core/Settings/Services/SettingsRepository.php` – flat-file `data/settings.json`, ukladá iba odchýlky, `flock`.
- `Http/Controllers/Admin/SettingsController.php` – CRUD nastavení + `publicSettings` + `reset`.
- `Http/Routes/settings.php` – `/api/admin/settings/*`, `/api/settings/public`.
- `Core/Validation/Validator.php` + `ValidationException.php` – bezstavový validátor.
- `Core/Validation/ValidationRules.php` – katalóg pravidiel (login, password, content, user).
- `Http/Controllers/Validation/ValidationController.php` + `validation.php` – export pravidiel.
- `Http/Support/ApiErrorHandler.php` – jednotný JSON obal; registrovaný v `bootstrap/app.php`.

**Frontend (React + TypeScript):**

- `api/settings.ts` – typované volania nastavení.
- `api/validation.ts` – stiahnutie zdieľaných pravidiel.
- `components/backend/SettingsView.tsx` – generický formulár riadený schémou.
- `context/SettingsContext.tsx` + `hooks/useSettings.ts` – globálny prístup k nastaveniam.
- `utils/validation.ts` – FE zrkadlo Validatora + `validatePasswordPolicy`.
- `hooks/useAutoSave.ts` – interval z `content.autoSaveInterval` (SettingsContext).

**Kľúčové parametre:**


| Parameter          | Hodnota                                        |
| ------------------ | ---------------------------------------------- |
| Úložisko nastavení | `data/settings.json` (iba odchýlky)            |
| Validačná chyba    | HTTP 422 + `{ success: false, error, errors }` |
| Verejný výrez      | `GET /api/settings/public` (AUTH)              |
| Export pravidiel   | `GET /api/validation/rules`                    |


---

> **Ďalšie iterácie a stav požiadaviek:** súhrn v tejto roadmape (It. 19–22);
> detailný audit DONE/PARTIAL/MISSING doplní `docs/CHECKLIST.md` (refresh po 2.0.6).



## Iterácia 5 – Používatelia + kalenie autentifikácie ✅

- Admin CRUD používateľov + priradenie rolí (`UserController`, FE `UsersManager`, `api/users.ts`).
- 2FA vynútenie (`TwoFactorMiddleware` na `/api/admin/*`) + FE UI (setup/QR/login prompt).
- Kalenie auth: HttpOnly session cookie, odstránený mŕtvy Bearer kód z `api/client.ts`.



## Iterácia 6 – Notifikácie: SMTP + konektory + toast ✅

Hotové v **2.0.1** — detail [ITERATION_6.md](ITERATION_6.md).

- Reálne SMTP (`SmtpTransport`) z nastavení skupiny `smtp`
- Konektory: email, ntfy, Discord, Telegram, webhook — skupina `connectors` + test v `/notifications`
- Toast: pozícia, zap/vyp, debug mód — skupina `notifications`, FE `NotificationContext`
- Reset hesla e-mailom, incident alerty (`IncidentNotifier`)



## Iterácia 7 – Admin dashboard + monitoring + API Tracker ✅

**Stav:** hotové v **2.0.17** — detail [ITERATION_7.md](ITERATION_7.md).

- Panel zámkov (`GET /api/locks`) a konfliktov (`GET /api/admin/conflicts`) + Health metriky.
- Analytics (`AnalyticsManager/Reporter/RealtimeTracker/Middleware`), reporty návštevnosti, realtime.
- **Plánované monitoring reporty:** interval hodina/deň/týždeň, presný čas odoslania, konektor (email/ntfy/Discord/Telegram/webhook/all).
- **Obsah reportu:** analytics, flat-file štatistiky (pages/articles/users/backups/trash/locks/conflicts), celkové zdravie systému.
- **Log incidenty:** automatické notifikácie pri ERROR/WARNING v app logu cez zvolený konektor.
- CLI: `php backend/bin/console monitoring:run-schedule` (crontab každú minútu).
- FE: `/notifications` – prehľad plánu, manuálne odoslanie reportu, simulácia cronu.



## Iterácia 8 – Media manager / DAM (FE) + editory + developer unlock UI ✅

- ✅ FE `MediaManager` (upload/grid/altText/delete) + route `/media` nad `/api/media`.
- ✅ Prepínač Markdown / WYSIWYG v `MarkdownEditor` + `WysiwygEditor` + `MediaPickerModal`.
- ✅ Developer unlock UI (`DeveloperUnlockGate` → `/api/admin/developer/unlock`, TOTP/dev-token).
- ✅ **Plný DAM v1** ([Iterácia 24](ITERATION_24.md)): priečinky, `.meta.json` sidecar, hromadné mazanie, settings `media`, stock knižnica.
- ✅ **Náhľad obrázkov** ([Iterácia 26](ITERATION_26.md)): lightbox Fit / 1:1, natívne rozlíšenie.
- ✅ Monaco editor v Code Editore — od **2.0.21** (`MonacoCodeEditor.tsx`); create/delete/restore od **2.0.22** (It. 16).
- 📖 Príručka: [user/CODE_EDITOR.md](user/CODE_EDITOR.md) — unlock/lock, whitelist adresárov, bezpečnosť.



## Iterácia 9 – Prototype port (nav, comments, contact, GitHub) ✅

**Stav:** hotové v **2.0.5** — detail [ITERATION_9.md](ITERATION_9.md) (English).

- Navigation, Comments, Contact/Messages, GitHub admin API — port z `prototype/backend/` na `/api/*`
- FE: `NavigationManager`, `CommentsManager`, `MessagesViewer`, `GitHubSyncPanel`, public wiring
- **SEO meta engine** nie je It.9 — doručené v **[Iterácii 23](ITERATION_23.md)** ✅; admin SEO UX → **[Iterácia 27](ITERATION_27.md)** ✅



## Iterácia 10 – XML Feeds (RSS/sitemap/robots) ✅

**Stav:** hotové — RSS + sitemap + robots.txt + cache + head links. Detail: [ITERATION_10.md](ITERATION_10.md) (core v [Iterácii 22](ITERATION_22.md), 2.0.10).

- `GET /feed.xml`, `GET /sitemap.xml`, `GET /robots.txt`
- Admin skupina `feeds`; verejný RSS + sitemap v `<head>`
- `ContentCacheService` pre generované XML (TTL 300s, invalidácia pri publish)



## Iterácia 11 – SSO + jemnozrnné ACL + bezpečnostný audit log ✅ 🔵

- ✅ OAuth2 SSO (GitHub + generic) — settings `sso`, `/api/auth/sso/*`, FE login buttons.
- ✅ Jemnozrnné ACL — `data/security/acl.json`, `PathAclService`, admin `/security/acl`.
- ✅ Bezpečnostný audit log — `SecurityAuditStore`, CSV export, admin `/security/audit`.
- ⏳ SAML — mimo v1; `PathAclService` ešte nie je zapojený do každého content write.



## Iterácia 12 – Blueprint / Schema Engine ✅ 🔴

- ✅ Flat-file blueprinty `data/blueprints/{type}.json`, built-in `page` + `article`.
- ✅ `DynamicValidator` + admin API/UI (`/blueprints`, `DynamicForm`).
- ✅ Validácia `ContentController` save cez blueprint.



## Iterácia 13 – Demo modul (izolované MOCK dáta) ✅ 🟡

- ✅ `DEMO_MODE` + oddelené úložisko `storage/app/demo/`.
- ✅ Seed/reset API (`DemoStorageService`), SUPER_ADMIN reset.
- ✅ Admin banner + `/demo` UI; verejné `demo.enabled`.
- ⏳ Demo extensions cez plugin runtime — čaká na It. 15.



## Iterácia 14 – Politika kódu + oprava CodeEditor základov ⏳ 🔵

- `CodePolicyEngine` (syntax + security scan + kompatibilita), rozšírený `SyntaxChecker`.
- Oprava `CodeEditorManager` path resolution, zapojenie `CodeEditorLogger`.
- FE: `FileTree` align s API, policy chyby pred save.
- Dokumentácia: `docs/developer/CODING_STANDARDS.md`.



## Iterácia 15 – Externé doplnky mimo Core + runtime ⏳ 🔴

- **ZÁKON:** `Http/Extensions/{id}/` + `Http/Routes/extensions/` + `frontend/src/extensions/{id}/`.
- `PluginManager`: install/import/enable/disable, flat-file `data/plugins.json`.
- Validácia importu cez `CodePolicyEngine`; `HookManager` do DI.



## Iterácia 16 – CodeEditor plný stack (moduly, témy, doplnky) ✅ 🟢

- ✅ Monaco editor, Developer unlock UI, CMS témy (`resources/views/themes/`).
- ✅ Edit modulov/tém po prechode politiky kódu; create/delete, backup restore (**2.0.22**).
- ⏳ Edit doplnkov cez `PluginManager` — čaká na It. 15.



## Iterácia 18 – Lokalizácia admin rozhrania (i18n) ⏳ 🟡

**Stav dnes:** backend čiastočne hotový, frontend základ pridaný, migrácia UI nie.

**Backend (čiastočne ✅):**

- `Support/Lang.php` – prekladač, default `sk`, fallback na SK, `Lang::addPath()` pre pluginy
- `backend/lang/{sk|en}/{module}.php` – moduly: content, comments, contact, media, messages, navigation, github
- `LocaleMiddleware` – locale z `general.language` + `Accept-Language`
- `SettingsSchema` – pole `general.language` (`sk` | `en`)

**Frontend (základ ✅, migrácia ⏳):**

- `src/i18n/core/{sk,en}.ts` – jadrový katalóg administrácie
- `registerModuleMessages()` – každý modul importuje vlastný blok (`src/i18n/modules/{module}/`)
- `I18nProvider` + `useI18n().t('common.save')` – locale z `SettingsContext`

**Zostáva (Iterácia 18):**

- Migrovať všetky admin komponenty z hardcoded SK reťazcov na `useI18n()`
- Pridať FE modulové súbory (media, navigation, users, settings, …)
- Plugin/theme/extension loader: `Http/Extensions/{id}/lang/{locale}/*.php` + `frontend/src/extensions/{id}/i18n/`
- Verejný web (PublicSite) – samostatný katalóg alebo zdieľaný core
- API endpoint `GET /api/i18n/{locale}` pre dynamické načítanie (voliteľné)

**Zákon i18n:** 1 súbor na modul a jazyk; jadro má core katalóg; doplnky nikdy neprepisujú core kľúče.

---



## Iterácia 17 – ZÁKON API↔FE scaffold ⏳ 🟡

- Scaffold: endpoint → `api/*.ts` + komponent + `API.md` záznam.
- CodeEditor wizard „Nový doplnok"; CONTRIBUTING checklist.
- Oprava `frontend/src/api/index.ts` (broken barrel).
- Nahradiť raw `useApi` typovanými modulmi (`backup.ts`, `audit.ts`, `codeEditor.ts`).

---



## Iterácia 19 – FlatFile storage, indexácia a stránkovanie ✅

**Stav (release 2.0.7):** jadro hotové. Zostáva voliteľne: CLI `content:migrate --to=json`, pagination na media/messages list API.

**Hotové ✅:** index, pagination meta, search API, dual storage (md/json), FE pagination + search.

---



## Iterácia 20 – Core hardening & produkčná pripravenosť ✅

**Stav (release 2.0.8):** jadro hotové. Zvyšok (trash UI, brute-force) presunutý do [Iterácie 22](ITERATION_22.md).

**Hotové ✅:**

- `PermissionMiddleware` + RBAC na content/media mutácie (`content:*`, `media:*`; ADMIN `:manage` alias)
- `GET /storage/{path}` – servovanie médií (+ Vite proxy `/storage`)
- `MaintenanceModeMiddleware` – `general.maintenanceMode` (výnimka admin/editor session + `/api/admin/*`)
- `general.allowRegistration` – vypnutie registrácie
- `comments.allowGuestComments` – vynútenie v `CommentsController`
- Session fixation – `session_regenerate_id()` v `SessionManager::setUser()` (dokumentované)
- Trash API: `GET /api/admin/trash`, `POST /api/admin/trash/{id}/restore` + meta pri soft-delete
- Backup cron: `bin/console backup:run-schedule` + `BackupScheduler`
- FE: `/preview/:slug`, `AdminRoleGuard`, `document.title`, `VersionHistory` v editore, Developer logs viewer

**Dokončené v It. 22 🚧:**

- Trash admin UI v React → `/trash`, `TrashManager`, `api/trash.ts`
- Brute-force lockout per e-mail/IP → `SecurityLogger` + settings `security.*`

**Testy (2.0.8 + It. 22):**

- `CoreHardeningTest` – RBAC 403, maintenance 503, registration toggle, storage route
- `AuthorizationManagerManagePermissionTest` – `:manage` alias
- `TrashServiceTest`, `TrashControllerTest`, `PermissionMiddlewareTest`, `MaintenanceModeMiddlewareTest`
- `StorageControllerTest`, `BackupSchedulerTest`, `BackupManagerTest` (schedule/cron)
- `CommentsControllerTest` – guest comments toggle
- `FileWriterTest` – trash `.meta.json` sidecar

---



## Iterácia 21 – API kontrakt, automatizované testovanie & FE parita ✅

**Stav (release 2.0.9):** dokončené — JsonResponder everywhere, MSW, Newman CI, RHF+Zod, API.md refresh.

**Hotové ✅:**

- `JsonResponder` vo **všetkých** HTTP controlleroch (vrátane Backup, Version, AuditTrail)
- `docs/architecture/API_CONTRACT.md`, `docs/architecture/API.md`
- MSW, Postman smoke, `scripts/run-api-smoke.sh`, `.github/workflows/ci.yml`
- `SettingsView` — React Hook Form + Zod + mapovanie 422 `errors`
- `content.ts`, `user.ts`, opravené `backup.ts`, `audit.ts`, `api/index.ts`

**Odložené (post 2.0.9):**

- OpenAPI 3.1 YAML
- Plná migrácia `useApi` → typed klienty (It. 17)

**Testy (2.0.9):**

- `JsonResponderTest`, `ApiResponseShapeTest`, `zodFromRules.test.ts`
- `handlers.test.ts` (MSW)
- **503+ PHPUnit**, PHPStan L8

---



## Iterácia 22 – Ops finish & verejné feedy ✅

**Stav (release 2.0.10):** hotové — trash UI, brute-force lockout, RSS/sitemap. Detail: [ITERATION_22.md](ITERATION_22.md).

**Hotové ✅:**

- Trash admin UI — `/trash`, `TrashManager`, `trashApi`
- Brute-force lockout — `LoginAttemptTracker`, settings `security.*`, HTTP 429
- RSS + sitemap — `GET /feed.xml`, `GET /sitemap.xml`, settings `feeds.*`
- `<link rel="alternate">` v `PublicSiteLayout`
- Vite + nginx proxy pre feedy (`docs/deploy/NGINX_API.md`)
- Testy: `TrashManager.test.tsx`, `FeedGeneratorTest`, `FeedControllerTest`, `AuthControllerTest::testLoginLockout`

**Ďalej:**

- It. 6–7 — SMTP end-to-end, analytics alerty

---



## Iterácia 23 – SEO meta engine ✅

**Stav (release 2.0.11):** hotové — automatické meta tagy pre verejný web. Detail: [ITERATION_23.md](ITERATION_23.md).

**Hotové ✅:**

- `SeoMetaBuilder` — title, description, canonical, OG, Twitter, JSON-LD
- Settings skupina `seo` + verejné `publicSettings.seo`
- `GET /api/seo/{type}/{slug}` — publikovaný obsah, draft 404 pre anonymov
- FE `useSeoMeta` v `PublicSiteLayout`
- Testy: `SeoMetaBuilderTest`, `SeoControllerTest`, `useSeoMeta.test.ts`

**Ďalej:**

- It. 6–7 — SMTP end-to-end, analytics alerty
- It. 8 — plný DAM (upload pipeline, thumbnails)

---



## Iterácia 27 – Admin view modes + SEO panel ✅

**Stav (release 2.0.15):** hotové — list / list-preview / preview režimy, SEO panel v editore, metadata modal v Media Library. Detail: [ITERATION_27.md](ITERATION_27.md).

**Hotové ✅:**

- `useAdminViewMode` + `AdminViewModeToggle` — Media, Articles, Pages
- `SeoMetadataPanel` + SEO tab v `MarkdownEditor`
- `SeoHealthBadge` + filter „SEO issues only"
- `MediaMetadataModal` — edit title/alt v list režimoch (responzívny dialog)
- `ContentController` — persist SEO front matter fields

**Odložené:**

- `GET /api/content/seo-audit` — backlog

---



## Iterácia 28 – Bulk actions platform ✅

**Stav (release 2.0.16):** hotové — zdieľaný bulk UI, batch API pre content/trash/comments/users/backups. Detail: [ITERATION_28.md](ITERATION_28.md).

**Hotové ✅:**

- `useBulkSelection` + `BulkActionBar`
- Bulk delete/status (pages, articles), trash restore, comments, **users**, **backups**
- Backup: **import ZIP**, **SHA-256** metadata + verify endpoint + download header

**Súvisí s It.20:** backup ops (cron, trash) — import/hash doplnené v It.28, nie samostatná iterácia.

---



## Iterácia 28 – Bulk actions (platform) ✅

**Stav (release 2.0.16):** hotové — zdieľaný bulk UI + batch API pre content, trash, comments. Detail: [ITERATION_28.md](ITERATION_28.md).

---



## Odporúčaný postup implementácie

> **Princíp:** najprv dáta a bezpečnosť (BE), potom kontrakt a testy, nakoniec FE parita a Roadmap iterácie 6–10.
> Každá fáza končí: PHPUnit + PHPStan L8 + Vitest green + záznam v CHANGELOG.



### Fáza 0 – Stabilný základ (hotové ✅, release 2.0.6)

Locking, drafts, konflikty, settings, auth+2FA, admin moduly, verejný React web, PHPStan L8, 453 PHPUnit testov.

### Fáza 1 – Dátová vrstva (It. 19) — **ĎALŠIA PRIORITA**

```
1. JsonResponder (základ) + pagination meta kontrakt
2. ContentIndexService + rebuild hook
3. Pagination na /api/pages, /api/articles, /api/media, /api/admin/*
4. GET /api/search
5. ContentStorageInterface + JsonContentStorage (md zostáva default)
6. FE: pagination v admin listoch + search API
7. Testy: ContentControllerTest, index rebuild
```

**Prečo first:** bez indexu a stránkovania neškáluje nič ďalšie (admin, verejný blog, search).

### Fáza 2 – Bezpečnosť & prevádzka (It. 20)

```
1. Published filter na verejnom BE API          ← kritické
2. RBAC na content/media zápis                 ← kritické
3. /storage servovanie médií                    ← kritické
4. Maintenance mode + allowRegistration
5. Trash restore API + admin UI
6. Backup cron CLI
7. Preview route + VersionHistory v editore
8. FE role guard
```

**Prečo second:** produkčný CMS musí byť bezpečný skôr než SEO/pluginy.

### Fáza 3 – Kontrakt & testy (It. 21 + It. 17) — hotové ✅

```
1. API_CONTRACT.md + doplnenie API.md
2. JsonResponder vo všetkých controlleroch
3. Postman collection + Newman smoke
4. MSW handlery + RHF + Zod
5. Oprava api/index.ts barrel
6. Contract + integration testy
```



### Fáza 3b – Ops finish & feedy (It. 22) — hotové ✅

```
1. Trash admin UI (It. 20 zvyšok)           ← ✅ hotové
2. Brute-force lockout (SecurityLogger)     ← ✅
3. RSS /feed.xml + sitemap /sitemap.xml     ← ✅ (It. 10 scope)
4. Settings skupina feeds + PublicSite link
5. PHPUnit + Vitest + CHANGELOG 2.0.10
```

**Prečo teraz:** produkčný CMS musí mať obnovu z koša, ochranu loginu a verejné feedy skôr než SEO/pluginy.

### Fáza 3c – SEO meta engine (It. 23) — hotové ✅

```
1. SeoMetaBuilder + settings skupina seo
2. GET /api/seo/{type}/{slug}
3. FE useSeoMeta + PublicSiteLayout
4. PHPUnit + Vitest + CHANGELOG 2.0.11
```



### Fáza 4 – Komunikácia & monitoring (It. 6–7)

```
SMTP end-to-end → reset hesla e-mailom → konektory → toast perzistencia
Analytics dokončenie → dashboard reporty → API tracker
```



### Fáza 5 – Obsah & médiá (It. 8–9, 23–24, 26–27)

```
It.8 Media FE → It.9 prototype port → It.24 DAM → It.23 SEO engine → It.26/2.0.14 preview hotfix → It.27 admin SEO UX
Monaco editor (It. 16) môže ísť paralelne s It. 8 ak je developer gate hotový
```

*(RSS/sitemap presunuté do It. 22)*

### Fáza 6 – Enterprise & rozšírenia (It. 11–16)

```
SSO + jemnozrnné ACL → Blueprint engine → Demo modul
Plugin runtime (It. 15) až po stabilnom jadre (It. 19–21)
CodeEditor plný stack + CMS témy
```



### Fáza 7 – Lokalizácia (It. 18)

```
Migrácia admin UI na useI18n() – ideálne po ustálení textov z It. 19–21
Plugin i18n loader (závisí od It. 15)
```



### Diagram závislostí

```
It.19 (FlatFile+index+pagination)
  └─► It.20 (hardening) ──► It.21 (kontrakt+testy)
        └─► It.22 (trash UI + lockout + RSS/sitemap)  ← aktuálne
              └─► It.6–7 (notifikácie+dashboard)
                    └─► It.8–9 (DAM+SEO)
                          └─► It.11–16 (SSO+plugins+Monaco)
                                └─► It.18 (i18n migrácia UI)
It.17 (API scaffold) ── beží priebežne od It.21, povinný pred It.15
It.10 (feeds) ── implementované v rámci It.22
```



### Pravidlo pre každú novú iteráciu

1. Backend kontrakt + PHPUnit + PHPStan L8
2. Typovaný FE klient (`api/*.ts`) + route/komponent
3. Záznam v `API.md` / `API_CONTRACT.md`
4. Vitest aspoň pre kritickú cestu
5. CHANGELOG + bump verzie

---



## Architektonické ZÁKONY (platia od It. 14+)

1. **Externé doplnky mimo Core** – kód pluginov len v `Http/Extensions/`, nikdy v `Core/`.
2. **API↔FE** – každý endpoint = typovaný FE klient + komponent/route.
3. **Politika kódu** – vlastný kód sa uloží len po prechode `CodePolicyEngine`.



## Priebežne ⏳

- Postupné pridávanie admin API a napájanie frontendu na existujúce/nové endpointy.
- **Stály bod:** ku každému novému modulu/funkcionalite pridať testy (PHPUnit + Vitest) + PHPStan L8.



## Hotové mimo hlavných iterácií ✅ (release 2.0.6)

- **In-Memory + File cache** (`Core/Cache`, chained Memory→File) v `ContentController`.
- **Verejný React web** – `PublicSiteLayout`, blog, kontaktný formulár, client-side search.
- **Admin moduly s FE** – media, navigation, comments, messages, GitHub sync, WYSIWYG prepínač.
- **PHPStan level 8** – 0 chýb na `backend/app`, `tests`, `bootstrap`, `bin`.
- **PHPUnit** – 453 testov, 0 warnings, 0 deprecations.
- **Vitest** – happy-dom, security testy FE+BE.
- **i18n foundation** – `Lang.php`, `LocaleMiddleware`, `I18nContext` (migrácia UI → It. 18).

> **Inventár funkcií:** aktuálny stav hotové / čiastočné / plánované → audit v chate alebo `docs/CHECKLIST.md` (potrebuje refresh po 2.0.6).

