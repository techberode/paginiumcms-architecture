# PaginiumCMS – Známe incidenty a opravy

> Posledná aktualizácia: 2026-07-19 · verzia **2.0.26**

Tento súbor eviduje produkčné / integračné problémy zistené pri testovaní, ich príčinu a stav opravy.

> **Audit report:** Súbor `AUDIT_REPORT.md` v koreni repozitára je **gitignored** a slúži len
> ako lokálny prehľad auditov (nie je verejný). Otvorené nálezy a stav opráv sleduj tu a v
> [CHANGELOG.md](../CHANGELOG.md#2024--2026-07-19).

---

## Prehľad


| ID      | Symptóm                                                 | Závažnosť             | Stav                                    |
| ------- | ------------------------------------------------------- | --------------------- | --------------------------------------- |
| ISS-001 | `POST /api/debug/client-event` → 404                    | Nízka (šum v konzole) | ✅ Opravené                              |
| ISS-002 | `GET /api/pages` → 500 na dashboarde                    | Vysoká                | ✅ Intermittent — diagnose + hardening   |
| ISS-003 | „Noví používatelia“ každú chvíľu                        | Stredná               | ✅ Hardening BE                          |
| ISS-004 | Hromadenie `navigation.json.backup.*`                   | Nízka                 | ✅ Retencia záloh                        |
| ISS-005 | Vitest worker crash / visnutie                          | Stredná (CI)          | ✅ Opravené                              |
| ISS-006 | PHPStan 15 chýb                                         | Stredná (CI)          | ✅ Opravené                              |
| ISS-007 | Dashboard nesprávny počet používateľov                  | Nízka                 | ✅ Opravené                              |
| ISS-008 | HTTP heslo polia (login/users)                          | Info                  | ⏳ HTTPS v produkcii                     |
| ISS-009 | `/settings` crash `n.max is not a function`             | Vysoká                | ✅ Opravené                              |
| ISS-010 | Vitest stderr: `act(...)` + Router future flags         | Nízka (CI šum)        | ✅ Opravené (2.0.24)                     |
| ISS-011 | ESLint warnings (`any`, react-refresh)                  | Nízka (tech. dlh)     | ⏳ 57/65 baseline, postupné čistenie     |
| ISS-012 | CSRF middleware nezapojený (audit S3)                   | Stredná               | ⏳ Odložené — SameSite=Lax               |
| ISS-013 | ntfy bez auth — privátne topicy zlyhajú                 | Stredná               | ✅ It.47 (Bearer/Basic + test-connector) |
| ISS-014 | CORS dev wildcardy pri zlej `APP_ENV` (audit S6)        | Nízka                 | ⏳ Overiť deploy                         |
| ISS-015 | PHPUnit → 429 / 503 / OTP persistencia                  | Stredná (CI)          | ✅ Opravené (2.0.25)                     |
| ISS-016 | PHPStan `phpVersion` vs `composer.json`                 | Stredná (CI)          | ✅ Opravené (2.0.25)                     |
| ISS-017 | PHPStan `match.alwaysTrue` v bulk controlleroch         | Stredná (CI)          | ✅ Opravené (2.0.25)                     |
| ISS-018 | PHPStan `fopen` resource v `TrashController`            | Stredná (CI)          | ✅ Opravené (2.0.25)                     |
| ISS-019 | `tsc --noEmit` strict TypeScript chyby                  | Stredná (CI)          | ✅ Opravené (2.0.25)                     |
| ISS-020 | ESLint 68 warnings → prekročenie `--max-warnings 65`    | Stredná (CI)          | ✅ Opravené (2.0.26)                     |
| ISS-021 | PHPStan `function.alreadyNarrowedType` v log readeri    | Stredná (CI)          | ✅ Opravené (2.0.26)                     |
| ISS-022 | Vitest `MediaManager.test.tsx` — krehké textové asercie | Stredná (CI)          | ✅ Opravené (2.0.26)                     |




## CI failures (GitHub Actions)

Workflow: `[.github/workflows/ci.yml](../.github/workflows/ci.yml)`


| CI job     | Step                 | Symptóm                                                         | Issue                              |
| ---------- | -------------------- | --------------------------------------------------------------- | ---------------------------------- |
| `backend`  | PHPStan level 8      | Analýza zlyhá (verzia PHP, `match`, `fopen`, `is_array`)        | ISS-016, ISS-017, ISS-018, ISS-021 |
| `backend`  | PHPUnit              | 429 Too Many Requests, 503 maintenance, flaky OTP               | ISS-015                            |
| `frontend` | `npm run type-check` | TS2352 / TS6133 / TS2322                                        | ISS-019                            |
| `frontend` | `npm run lint`       | Prekročenie `--max-warnings 65` (`react-hooks/exhaustive-deps`) | ISS-020                            |
| `frontend` | `npm test`           | Worker crash, `act(...)` stderr, `MediaManager` text asserts    | ISS-005, ISS-010, ISS-022          |
| `backend`  | PHPStan (historicky) | 15 typových chýb                                                | ISS-006                            |


Každý záznam nižšie obsahuje **popis chyby**, **navrhované riešenie** a **implementované riešenie**.

---



## ISS-001 – Debug client-event 404

**Symptóm:** Konzola plná `XHR POST …/api/debug/client-event [404]`, hoci FE loguje `[PaginiumCMS] event: …`.

**Príčina:** Frontend posielal udalosti pri `VITE_DEBUG=true` alebo v DEV režime, ale backend registroval trasu len keď `APP_DEBUG=true`. Pri prod builde na `:8081` → trasa neexistovala.

**Oprava:**

- `backend/app/Http/Routes/debug.php` – trasa vždy registrovaná.
- `DebugController` – pri vypnutom debug vráti **204** (no-op).
- `frontend/src/utils/debugLog.ts` – POST na backend len pri `VITE_DEBUG=true` (nie automaticky v prod).

**Overenie:** Po redeploy by mali 404 zmiznúť; pri `VITE_DEBUG=false` zostane len `console.debug`.

---



## ISS-002 – GET /api/pages → 500

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



## ISS-003 – Phantom / duplicitní používatelia

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



## ISS-004 – navigation.json.backup.*

**Symptóm:** V `data/` pribúdajú súbory `navigation.json.backup.20260718_104530`.

**Príčina:** Každý zápis cez `FileWriter::write(..., backup=true)` vytvorí timestamp backup (Navigation, users, content, …).

**Oprava:** `FileWriter::pruneBackups()` – ponechá posledných 5 backupov, staršie zmaže.

**Poznámka:** Backup ≠ nový používateľ. Súbor `navigation.json.backup.`* je normálna rotácia, nie chyba navigácie.

---



## ISS-005 – Vitest worker crash (Node 26)

**Symptóm:** `Error: Worker exited unexpectedly` pri `npm test`.

**Príčina:** Nekonečná slučka v `useBulkSelection` – `useMemo(..., [visibleIds])` pri inline poli v `renderHook`.

**Oprava:** Stabilizácia cez `visibleKey = visibleIds.join('\0')` v `frontend/src/hooks/useBulkSelection.ts`.

**Výsledok:** 28 súborov, 102 testov OK (Node 22 aj 26).

---



## ISS-006 – PHPStan level 8

**Opravené súbory:** `LoginAttemptTracker`, `SeoMetaBuilder`, `MediaFormats`, `MediaRepository`, `BulkBatchResultTest`.

---



## ISS-007 – Dashboard user count = 0

**Príčina:** `DashboardView` čítal `usersRes.data.length`, ale API vracia `{ users: User[] }`.

**Oprava:** `usersRes.data.users.length` v `DashboardView.tsx`.

---



## ISS-008 – Heslo cez HTTP (login, users, **settings**)

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



## ISS-009 – Settings crash: `n.max is not a function`

**Symptóm:** Po navigácii na `/settings` stránka spadne: `TypeError: n.max is not a function`.

**Príčina:** `zodFromRules.ts` volal `.max()` / `.min()` na `ZodOptional` (po `z.string().optional()`), nie na vnútorný `ZodString`.

**Oprava:** Reťazenie pravidiel na `z.string()` / `z.coerce.number()`, optional wrapper až na konci (`wrapOptional`).

**Overenie:** `npm test -- src/validation/zodFromRules.test.ts` + otvorenie `/settings` v admin.

---



## ISS-010 – Vitest stderr: `act(...)` a Router future flags

**Symptóm:** Pri `npm test` testy prechádzali, ale stderr obsahoval desiatky warningov:
`An update to … was not wrapped in act(...)` a React Router v7 future flag hints.

**Príčina:** `fireEvent` bez `waitFor` v `DeveloperUnlockGate.test.tsx` a `MediaManager.test.tsx`;
`MemoryRouter` bez `future={{ v7_startTransition, v7_relativeSplatPath }}`.

**Oprava (2.0.24,** `b9a740f`**):**

- `frontend/src/test/renderWithRouter.tsx` — spoločný wrapper s future flags.
- Testy prepísané na `fastUser` (`userEvent`) + `waitFor`.

**Overenie:** `npm test` — 130 testov OK, stderr čistý.

---



## ISS-011 – ESLint warnings (technický dlh)

**Symptóm:** `npm run lint` — 0 errors, warnings hlavne `@typescript-eslint/no-explicit-any`
(`client.ts`, `useApi.ts`) a `react-refresh/only-export-components`.

**Stav:** Baseline `--max-warnings 65` v CI — prekročenie spôsobí fail (pozri ISS-020).
Po oprave hook deps (2.0.26): **57 warnings** — rezerva 8 slotov do limitu.

**Plán:** Postupné znižovanie od API vrstvy; cieľ ≤50 v ďalšej iterácii.

---



## ISS-012 – CSRF middleware (audit nález S3)

**Symptóm:** `CsrfProtectionManager` existuje, ale mutačné routy nemajú globálny CSRF middleware.

**Mitigácia dnes:** Session cookie `SameSite=Lax`; token endpoint pre SPA.

**Prečo odložené:** Aktuálny token je single-use — globálne vynútenie rozbije SPA a PHPUnit flow.

**Plán:** Koordinovaný rollout (FE token refresh + BE middleware + testy) — samostatná iterácia.

---



## ISS-013 – ntfy bez autentifikácie (privátne topicy)

**Symptóm:** `NtfyAdapter` posielal POST bez `Authorization` — zlyhá na ACL topic / self-hosted ntfy.

**Oprava (It.47):**

- Settings: `ntfyAuthMode` (`none` | `token` | `basic`), `ntfyAccessToken`, `ntfyUsername`, `ntfyPassword`
- `NtfyAdapter::buildAuthHeaders()` — Bearer alebo Basic
- `POST /api/admin/notifications/test-connector` — validácia + test odoslania
- Admin `/notifications` — badge Auth OK / Chýba auth, tlačidlo Verify auth

**Overenie:** PHPUnit `NtfyAdapterTest`, `NotificationFactoryTest`; Settings → Connectors → ntfy token → Verify auth.

---



## ISS-014 – CORS dev wildcardy (audit nález S6)

**Symptóm:** Mimo produkcie CORS povoľuje `localhost:`*, `192.168.*`, `10.*`, `172.*` s `credentials: true`.

**Riziko:** Ak server beží s `APP_ENV` ≠ `production`, širšie CORS ostáva aktívne.

**Odporúčanie:** Pri nasadení na `mail.webland.fun` explicitne `APP_ENV=production`; overiť v health/deploy checkliste.

---



## ISS-015 – PHPUnit: rate limit, maintenance a OTP persistencia

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



## ISS-016 – PHPStan: nezhoda `phpVersion` s Composer

**CI job:** `backend` → step **PHPStan level 8**

**Symptóm:** PHPStan padal alebo hlásil nekompatibilné správanie; v `phpstan.neon` bolo `phpVersion: 80500`, zatiaľ čo `composer.json` vyžaduje `"php": "^8.4"` (minimálna podporovaná verzia 8.4, nie 8.5).

**Navrhované riešenie:** Zosúladiť PHPStan target s Composer floor — nastaviť `phpVersion: 80400`, aby analýza zodpovedala najstaršej podporovanej verzii v produkcii/CI.

**Implementované riešenie** (`d5c2660`):

- `phpstan.neon` — `phpVersion: 80500` → `80400`

**Overenie:** `./vendor/bin/phpstan analyse backend --level=8` — 0 chýb.

---



## ISS-017 – PHPStan: `match.alwaysTrue` v bulk akciách

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



## ISS-018 – PHPStan: `TrashController::downloadBackup` a `fopen`

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



## ISS-019 – Frontend CI: `tsc --noEmit` strict errors

**CI job:** `frontend` → step **TypeScript type-check** (`npm run type-check`)

**Symptóm:** Po release 2.0.25 CI padalo na strict TypeScript:


| Súbor                | Chyba  | Správa                                                                |
| -------------------- | ------ | --------------------------------------------------------------------- |
| `api/comments.ts`    | TS2352 | Nebezpečný cast `res as Record<string, unknown>` pri OTP vetve        |
| `api/workflows.ts`   | TS2352 | Priamy cast odpovede POST `/workflows/otp/verify`                     |
| `MarkdownEditor.tsx` | TS2352 | Cast publish odpovede na `Record<string, unknown>`                    |
| `BackupManager.tsx`  | TS6133 | Nepoužitá premenná `completedBackups`                                 |
| `Navbar.tsx`         | TS2322 | `active` môže byť `boolean | undefined`, komponent vyžaduje `boolean` |


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



## ISS-020 – ESLint: prekročenie `--max-warnings 65`

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



## ISS-021 – PHPStan: redundantné `is_array()` v ApplicationLogReader

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



## ISS-022 – Vitest: `MediaManager.test.tsx` krehké asercie

**CI job:** `frontend` → step **Vitest** (`npm test`)

**Symptóm:** 3 testy padali s `toBeInTheDocument()` timeoutom:

- `renders media grid after load` — `findByText('Hero')`
- `filters items by search query` — `findByText('Hero')` / `queryByText('Hero')`
- `saves metadata edits in list view mode via modal` — `findByText('Hero')`

**Príčiny:**

1. **Krehké textové asercie** — `Hero`, `hero.png`, `Alt: Hero banner` sa v `MediaManager` vykresľujú
  na viacerých miestach / režimoch (`MediaCard` vs `MediaListTable`), text môže byť skrátený alebo duplicitný.
2. **Nestabilný mock** `useToast` — po ISS-020 (`toast` v `loadMedia` deps) mock vracal nový objekt
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



## Externé / irelevantné hlášky


| Hláška                                                 | Zdroj                                   |
| ------------------------------------------------------ | --------------------------------------- |
| `Failed to get subsystem status` / `content-script.js` | Rozšírenie prehliadača, nie PaginiumCMS |
| `GET /api/auth/me` → 401 pred loginom                  | Očakávané správanie                     |


---



## Súvisiace dokumenty

- [CHANGELOG.md](../CHANGELOG.md) — release 2.0.26 (WAF, logging, CI fixes ISS-020–022)
- [TESTING.md](developer/TESTING.md) – ako spúšťať testy a regresiu
- [ROADMAP.md](ROADMAP.md) – plánované iterácie (It.41+, It.47–49)
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) – It.29+ detail
- [ITERATION_47.md](ITERATION_47.md) – notification connector auth (ISS-013)

