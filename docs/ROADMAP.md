# PaginiumCMS – Roadmapa spolupráce a správy obsahu

> Flat-File architektúra. Žiadna databáza. Všetok stav je v JSON / Markdown / asset súboroch na disku.

Legenda: ✅ hotové · 🚧 rozpracované · ⏳ plánované

---

## Iterácia 1 – Systém zamykania obsahu (Locking) ✅

Zabraňuje tomu, aby dvaja používatelia prepisovali ten istý dokument naraz.

**Backend (PHP 8.4+, `backend/app/Core/Locking/`):**
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

| Parameter | Hodnota |
|---|---|
| Heartbeat interval (frontend) | 30 s |
| Auto-release TTL (backend) | 300 s (5 min) |
| Register zámkov | `backend/storage/app/content/data/locks.json` |
| Súbežnosť | `flock(LOCK_EX)` nad registrom |

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

| Parameter | Hodnota |
|---|---|
| Auto-save interval (frontend) | 60 s |
| Register konceptov | `backend/storage/app/content/data/drafts/{type}/{slug}.json` |
| Revízny odtlačok | `sha1(content + canonical(frontMatter))` |
| Konflikt obsahu | HTTP 409 + `conflict.serverContent/serverRevision` |

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

| Parameter | Hodnota |
|---|---|
| Merge algoritmus | riadkový diff3 (LCS kotvy) na klientovi |
| Auto-merge | nekonfliktné zmeny bez zásahu používateľa |
| Log konfliktov | `data/conflicts.json` (max 200, `flock`) |

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

| Parameter | Hodnota |
|---|---|
| Úložisko nastavení | `data/settings.json` (iba odchýlky) |
| Validačná chyba | HTTP 422 + `{ success: false, error, errors }` |
| Verejný výrez | `GET /api/settings/public` (AUTH) |
| Export pravidiel | `GET /api/validation/rules` |

---

> **Ďalšie iterácie a stav všetkých požiadaviek:** kompletný audit repozitára
> (DONE/PARTIAL/MISSING) a fázový plán je v **`docs/CONTINUATION.md`**. Nižšie je súhrn.

## Iterácia 4 – Základy: Settings + Error Handler + zdieľaná validácia ✅
*(detail v sekcii vyššie)*

## Iterácia 5 – Používatelia + kalenie autentifikácie ✅
- Admin CRUD používateľov + priradenie rolí (`UserController`, FE `UsersManager`, `api/users.ts`).
- 2FA vynútenie (`TwoFactorMiddleware` na `/api/admin/*`) + FE UI (setup/QR/login prompt).
- Kalenie auth: HttpOnly session cookie, odstránený mŕtvy Bearer kód z `api/client.ts`.

## Iterácia 6 – Notifikácie: SMTP + konektory + toast ⏳ 🟡
- Reálne SMTP (mailer) z nastavení, `NotificationService` do DI, reset hesla e-mailom.
- Konektory: ntfy, Discord, Telegram, webhook – konfigurácia + aktivácia v admine.
- Toast: pozícia, zap/vyp, **debug mód**, perzistencia v nastaveniach.

## Iterácia 7 – Admin dashboard + monitoring + API Tracker ⏳ 🟡
- Panel zámkov (`GET /api/locks`) a konfliktov (`GET /api/admin/conflicts`) + Health metriky.
- Dokončenie Analytics (`AnalyticsManager/Reporter/RealtimeTracker/Middleware`), reporty návštevnosti, realtime, notifikácie cez konektor.

## Iterácia 8 – Media manager / DAM (FE) + editory + developer unlock UI 🟡
- ✅ FE `MediaManager` (upload/grid/altText/delete) nad existujúcim `/api/media`.
- ⏳ **Plný DAM:** viacúrovňové priečinky, `.meta.json` sidecar, hromadné operácie, zamykanie assetov.
- ⏳ Admin voľba editora (markdown/WYSIWYG/Monaco) + zapojenie `WysiwygEditor`.
- ⏳ Developer unlock UI (gate modal → `/api/admin/developer/unlock`, TOTP/dev-token).

## Iterácia 9 – SEO: automatické tagy + rozšírené nastavenia ⏳ 🟢
- `Core/Seo/*` – automatické meta description, canonical, Open Graph, Twitter Card, JSON-LD z obsahu/front matter.
- Admin sekcia „SEO“ – predvolené šablóny title/description, default social image, robots, prepínače; prepojenie na sitemap/feedy.

## Iterácia 10 – XML Feeds (RSS/sitemap) ⏳ 🟢
- Generátor `/feed.xml`, `/sitemap.xml` + rozšírené admin nastavenia feedov.

## Iterácia 11 – SSO + jemnozrnné ACL + bezpečnostný audit log ⏳ 🔵
- SSO (SAML/OAuth) s flat-file konfiguráciou, mapovanie na role.
- Jemnozrnné ACL na úrovni súborov/priečinkov (JSON), rozšírenie `AuthorizationManager`.
- Bezpečnostný audit log: dotiahnuť `Modules/Audit` (pokrytie akcií, CSV export, FE prehľad).

## Iterácia 12 – Blueprint / Schema Engine ⏳ 🔴
- Flat-file definície typov obsahu a polí, dynamická validácia a admin formuláre (po schválení návrhu).

## Iterácia 13 – Demo modul (izolované MOCK dáta) ⏳ 🟡
- Oddelený base-path + `DEMO_MODE`, MOCK dáta bez zásahu do reálneho obsahu (garancia integrity).

## Iterácia 14 – Politika kódu + oprava CodeEditor základov ⏳ 🔵
- `CodePolicyEngine` (syntax + security scan + kompatibilita), rozšírený `SyntaxChecker`.
- Oprava `CodeEditorManager` path resolution, zapojenie `CodeEditorLogger`.
- FE: `FileTree` align s API, policy chyby pred save.
- Dokumentácia: `docs/developer/CODING_STANDARDS.md`.

## Iterácia 15 – Externé doplnky mimo Core + runtime ⏳ 🔴
- **ZÁKON:** `Http/Extensions/{id}/` + `Http/Routes/extensions/` + `frontend/src/extensions/{id}/`.
- `PluginManager`: install/import/enable/disable, flat-file `data/plugins.json`.
- Validácia importu cez `CodePolicyEngine`; `HookManager` do DI.

## Iterácia 16 – CodeEditor plný stack (moduly, témy, doplnky) ⏳ 🟡
- Monaco editor, Developer unlock UI, CMS témy (`resources/views/themes/`).
- Edit modulov/tém/doplnkov len po prechode politiky kódu; create/delete, backup restore.

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

## Architektonické ZÁKONY (platia od It. 14+)

1. **Externé doplnky mimo Core** – kód pluginov len v `Http/Extensions/`, nikdy v `Core/`.
2. **API↔FE** – každý endpoint = typovaný FE klient + komponent/route.
3. **Politika kódu** – vlastný kód sa uloží len po prechode `CodePolicyEngine`.

## Priebežne ⏳
- Postupné pridávanie admin API a napájanie frontendu na existujúce/nové endpointy.
- **Stály bod:** ku každému novému modulu/funkcionalite pridať testy (PHPUnit + Vitest) + PHPStan L8.

## Hotové mimo hlavných iterácií ✅
- **In-Memory + File cache** (`Core/Cache`, chained Memory→File) zapojená v `ContentController`.
- **Backendy bez FE** (čakajú na napojenie): Media (`/api/media`), Developer gate + gated CodeEditor, 2FA/TOTP API.
