# Testovanie – PaginiumCMS

> Posledná aktualizácia: **2.0.58 (Wave 6)** · **838 PHPUnit** testov (15 skipped), PHPStan level 8 (0 errors)

## Beta release gate (Wave 6)

Pred tagom **Public Beta 1** alebo každým minor release:

```bash
composer gate                              # = scripts/iteration-gate.sh (PHPUnit, PHPStan, tsc, lint, api-barrel)
./scripts/run-all-tests.zsh                # plná sada vrátane content:diagnose
```

Checklist maintainera: [BETA_INFRA.md](./BETA_INFRA.md) · Cron na produkcii: [deploy/CRON.md](../deploy/CRON.md).

---


| Situácia                                              | Príkaz                                                          | Trvanie (orientačne) |
| ----------------------------------------------------- | --------------------------------------------------------------- | -------------------- |
| **Pred commitom / po iterácii**                       | `./scripts/iteration-gate.sh`                                   | ~2–5 min             |
| **Kompletná lokálna sada (BE + FE + SCA + diagnose)** | `./scripts/run-all-tests.zsh`                                   | ~15–25 min           |
| **Len backend**                                       | `./vendor/bin/phpunit` + PHPStan                                | ~1–2 min             |
| **Len frontend**                                      | `cd frontend && npm test && npm run type-check && npm run lint` | ~2–5 min             |
| **CI mirror (GitHub Actions)**                        | pozri `.github/workflows/ci.yml`                                | —                    |


---



## Kompletná testovacia procedúra (`run-all-tests.zsh`)

Automatický skript spustí **11 testovacích krokov** + **záverečný cleanup** (krok 12), streamuje výstup do terminálu, uloží celý log do súboru a na konci vypíše **súhrnnú tabuľku** metrik (Passed / Failed / Errors / Skipped) plus **detailné bloky chýb** pre zlyhané kroky. Cleanup test artefaktov beží **až po všetkých testoch**.

**Súbor:** `scripts/run-all-tests.zsh`  
**Shell:** zsh (shebang `#!/usr/bin/env zsh`)

### Predpoklady

1. Spustené z **ľubovoľného podadresára** projektu (skript sám nájde koreň podľa `vendor/bin/phpunit`).
2. Nainštalované závislosti:
  ```bash
   composer install
   cd frontend && npm ci   # alebo npm install
  ```
3. **Node 22** (rovnako ako CI): `nvm use 22` pred frontend krokmi.
4. Pre krok 11 (`content:diagnose`) musí existovať `backend/storage/app/content` s indexom — typicky po prvom behu aplikácie alebo deployi.



### Postup použitia (krok za krokom)

```bash
# 1. Prejdi do projektu
cd ~/projects/paginiumcms-architecture

# 2. (Voliteľne) nastav vlastný adresár pre logy — pozri sekciu nižšie
export PAGINIUMCMS_TEST_LOG_DIR="$HOME/logs/paginiumcms"

# 3. Spusti celú sadu
./scripts/run-all-tests.zsh

# 4. Po skončení skontroluj záverečný súhrn v termináli
#    - tabuľka 11 krokov so stavom ✅/❌
#    - pri zlyhaní sekcia „DETAILNÉ BLOKY CHÝB“

# 5. Otvor log (cesta sa vypíše na konci, napr. file:///home/.../alltests_190726_1510.log)
xdg-open "$(ls -t ${PAGINIUMCMS_TEST_LOG_DIR:-$HOME/projects/paginiumcms_tests}/alltests_*.log | head -1)" 2>/dev/null || true
```

**Exit kód:** `0` = všetkých 11 krokov OK · `1` = aspoň jeden krok zlyhal (vhodné pre CI wrapper alebo alias).

**Progress bar (terminál):** po **dokončení každého** z 11 krokov skript vypíše na **stderr** riadok `[####------] 3/11 (27%) ✅ PHPUnit … | Passed: … (45s)` — vrátane metrik a času behu. Počas samotného testu ide **celý výstup na stdout** (rovnako ako do logu), bez vnútorného sub-progressu, ktorý by prekrýval PHPUnit/Vitest riadky. Progress **nie je** v log súbore. Vypnutie: `PAGINIUMCMS_NO_PROGRESS=1` alebo keď stderr nie je TTY (CI, pipe).

### Čo skript spúšťa (11 krokov)


| #   | Krok                        | Príkaz (zjednodušene)                        | Metriky v súhrne                      |
| --- | --------------------------- | -------------------------------------------- | ------------------------------------- |
| 1   | PHPUnit (backend)           | `vendor/bin/phpunit --colors=always`         | Passed / Failed / Errors / Skipped    |
| 2   | PHPStan L8                  | `phpstan analyse backend --level=8`          | OK alebo počet errors                 |
| 3   | Composer Audit              | `composer audit`                             | advisories / OK                       |
| 4   | Vitest (funkčné)            | `cd frontend && CI=true npm test`            | Test Files + Tests                    |
| 5   | Bezpečnostné FE testy       | `npm run test:security`                      | Test Files + Tests                    |
| 6   | TypeScript                  | `npm run type-check`                         | počet TS errors                       |
| 7   | ESLint                      | `npm run lint`                               | problems (errors/warnings)            |
| 8   | Vitest MSW                  | `npm run test:msw`                           | Test Files + Tests                    |
| 9   | Produkčný build             | `npm run build:prod`                         | build OK / failed                     |
| 10  | NPM Audit                   | `npm run audit:security`                     | vulnerabilities                       |
| 11  | Content diagnose (ISS-002)  | `php backend/bin/console content:diagnose`   | index / pages / orphans / unreadable  |
| 12  | **Cleanup test artefaktov** | `php backend/bin/test-artifacts.php --purge` | počty users/trash/media/pages pred/po |


Krok 11 beží **bez** `--fix` — len kontrola flat-file úložiska. Oprava indexu/cache: `php backend/bin/console content:diagnose --fix`.

Krok **12** beží **až po súhrne** krokov 1–11 — vymaže iba generické test dáta (`*@example.com`, `bulk-`*, `seo-test-*`, …), nie reálne účty ani produkčné stránky.

### Ukladanie logov do iného adresára

Predvolene skript zapisuje logy do:

```text
~/projects/paginiumcms_tests/alltests_DDMMYY_HHMM.log
```

Formát názvu: `alltests_` + dátum `ddmmyy` + `_` + čas `hhmm` (napr. `alltests_190726_1510.log`).

**Vlastný adresár** — environment premenná `PAGINIUMCMS_TEST_LOG_DIR`:

```bash
# Jednorazovo pre jeden beh
PAGINIUMCMS_TEST_LOG_DIR=/var/log/paginiumcms ./scripts/run-all-tests.zsh

# Trvalo v ~/.zshrc alebo ~/.bashrc
export PAGINIUMCMS_TEST_LOG_DIR="$HOME/logs/paginiumcms_tests"
mkdir -p "$PAGINIUMCMS_TEST_LOG_DIR"
./scripts/run-all-tests.zsh
```

Skript adresár **vytvorí** (`mkdir -p`), ak neexistuje. Na konci vypíše `file://` URL k aktuálnemu logu.

### Výstup na konci behu (príklad)

```text
📊 SÚHRN NÁSTROJOV (Passed / Failed / Errors / Skipped)
✅  1. PHPUnit (backend testy)                    Passed: 584 | Failed: 0 | Errors: 0 | Skipped: 15
✅  2. PHPStan (statická analýza, Level 8)       OK | errors: 0
...
✅ 11. Content diagnose (backend/bin/console)     OK | index: 174 | pages: 173 | orphans: 0 | unreadable: 0
✅ Všetky testy dobehli ÚSPEŠNE (11/11).

⚙️ 12/12 Cleanup test artefaktov (iba generické / @example.com)
  • test_users (*@example.com) ............ 0
  ...
[############################] 12/12 (100%) ✅ Cleanup | …

👉 Log: file:///home/user/projects/paginiumcms_tests/alltests_190726_1510.log
```

Pri zlyhaní pribudne sekcia **KONKRÉTNE CHYBY (cesta + popis)** — jeden riadok na failure (nie celé stack trace bloky), napr.:

```text
/home/.../backend/tests/BarTest.php:42 — PaginiumCMS\Tests\Foo\BarTest::testSomething — Failed asserting that ...
frontend/src/App.test.tsx:12:5 — renders title — AssertionError: ...
```

Celý surový výstup nástroja zostáva v logu `alltests_*.log`.

### Live výstup počas kroku (Vitest — krok 4)

Po hlavičke `⚙️ 4/11 Vitest …` ide **priamo výstup nástroja** (rovnaký ako pri `cd frontend && npm test`). Typický tvar:

```text
==================================================
⚙️ 4/11 Vitest (frontend funkčné testy)
----------------

> paginiumcms-frontend@… test
> vitest run

 RUN  v4.1.10 /home/…/frontend

 ✓ src/components/auth/TwoFactorSettings.test.tsx (2 tests) 172ms
 ✓ src/components/backend/MediaManager.test.tsx (5 tests) 2297ms
     ✓ renders media grid after load  384ms
     ✓ filters items by search query  399ms
 …

 Test Files  36 passed (36)
      Tests  135 passed (135)
   Duration  52.77s (…)

✅ OK: Vitest (frontend funkčné testy) (53s) | Test Files  36 passed (36) | Tests  135 passed (135)
```

- Riadky `✓ …` a odsadené pod-testy sú **normálne** — Vitest 4 default reporter.
- **Vyhodnotenie kroku** (`✅ OK … | Test Files …`) príde až **po skončení** celého behu Vitestu.
- Progress bar `[####------] 4/11 …` ide na **stderr** (mimo log súboru).

Rovnaký princíp platí pre PHPUnit (testdox `✔/✘`), PHPStan, ESLint atď.

### Rozdiel oproti `iteration-gate.sh`


|                    | `run-all-tests.zsh`                                  | `iteration-gate.sh`        |
| ------------------ | ---------------------------------------------------- | -------------------------- |
| Účel               | Plná regresná + bezpečnostná sada + content diagnose | Rýchla brána pred commitom |
| Composer/NPM audit | áno                                                  | nie                        |
| Produkčný build    | áno                                                  | nie                        |
| PHP syntax scan    | nie                                                  | áno (changed files)        |
| Wiring integrity   | nie                                                  | áno                        |
| Log do súboru      | áno (s timestamp)                                    | nie                        |
| Odporúčanie        | pred release / veľká zmena                           | po každej iterácii         |


---



## Spustenie jednotlivých nástrojov (manuálne)

```bash
# Celá sada (z koreňa projektu)
./vendor/bin/phpunit

# S prehľadom
./vendor/bin/phpunit --testdox

# Statická analýza
./vendor/bin/phpstan analyse backend --level=8

# Frontend (Vitest)
cd frontend && npm test

# MSW contract testy (bez backendu)
cd frontend && npm run test:msw

# Content diagnose (ISS-002) — index, súbory, oprávnenia
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --fix    # purge cache + rebuild index
php backend/bin/console content:diagnose --json   # strojový výstup

# ACL + access control testy

vendor/bin/phpunit backend/tests/Modules/Security/Services/PathAclServiceTest.php \
  backend/tests/Modules/Security/Services/ContentPathAclGuardTest.php \
  backend/tests/Http/Controllers/Security/PathAclIntegrationTest.php \
  backend/tests/Modules/Security/PermissionCatalogTest.php \
  backend/tests/Modules/Security/Services/AuthorizationManagerSettingsReloadTest.php
```

User docs: [ACCESS_CONTROL.md](../user/ACCESS_CONTROL.md), [BRANDING.md](../user/BRANDING.md).



## Iterácia 21 – API kontrakt


| Komponent            | Test / artefakt                                |
| -------------------- | ---------------------------------------------- |
| `JsonResponder`      | `Http/Support/JsonResponderTest.php`           |
| HTTP response shapes | `Http/Contract/ApiResponseShapeTest.php`       |
| MSW handlery         | `frontend/src/mocks/handlers.test.ts`          |
| Postman smoke        | `docs/api/PaginiumCMS.postman_collection.json` |
| Kontrakt docs        | `docs/architecture/API_CONTRACT.md`            |




### Postman / Newman (voliteľné)

```bash
# Vyžaduje bežiaci backend na :8080 a npx newman
npx newman run docs/api/PaginiumCMS.postman_collection.json \
  --env-var baseUrl=http://localhost:8080
```

Dev mocks: `VITE_MSW=true npm run dev` v `frontend/`.

### CI (GitHub Actions)

Workflow `.github/workflows/ci.yml` — PHPUnit, PHPStan, Vitest, Newman smoke (public endpoints).

### Iteration gate (po každej iterácii, pred commitom)

```bash
./scripts/iteration-gate.sh
```

See `.cursorrules` § Iteration gate and `scripts/iteration-gate.sh` for the full checklist (syntax, PHPStan, PHPUnit, tsc, ESLint, wiring integrity).

```bash
./scripts/run-api-smoke.sh   # lokálne (backend na :8080)
```



## Štruktúra backend testov

```text
backend/tests/
├── Core/                    # Unit testy jadra (FlatFile, Backup, Validation, …)
│   ├── FlatFile/Services/   # ContentRepository, TrashService, FileWriter, …
│   ├── Backup/Services/     # BackupManager, BackupScheduler
│   └── …
├── Http/
│   ├── Controllers/         # Integračné HTTP testy (Content, Auth, Trash, …)
│   ├── Middleware/          # PermissionMiddleware, MaintenanceMode, Locale
│   └── ApplicationFlowTest.php  # End-to-end smoke
└── Modules/                 # Security, Demo, …
```

Bootstrap aplikácie pre HTTP testy: `backend/tests/Http/TestCase.php` — načíta reálny `bootstrap/app.php`, session reset, **`LoginAttemptTracker::clearAll()`** pred každým testom (inak zlyhané loginy z predchádzajúcich testov zablokujú IP `unknown` → HTTP **429** namiesto očakávaného **401**). Rate-limit middleware je v `APP_ENV=testing` no-op.

**ISS-103 (beta.12):** PHPUnit HTTP testy **nenačítavajú** lokálny `.env` keď `APP_ENV=testing` (`bootstrap/app.php` skip Dotenv). `TestCase` vynucuje `DEMO_MODE=false` pred bootstrapom — inak vývojársky `.env` s demo flagom spôsobí flaky OTP/2FA testy. Detail: [ISSUES.md](../ISSUES.md#iss-103--phpunit-otp2fa-flaky--lokálny-env-polluluje-testy--vyriešené).

## Iterácia 20 – pokrytie core hardening


| Komponent                                | Test súbor                                                      |
| ---------------------------------------- | --------------------------------------------------------------- |
| `PermissionMiddleware`                   | `Http/Middleware/PermissionMiddlewareTest.php`                  |
| `MaintenanceModeMiddleware`              | `Http/Middleware/MaintenanceModeMiddlewareTest.php`             |
| `StorageController`                      | `Http/Controllers/Storage/StorageControllerTest.php`            |
| `TrashService`                           | `Core/FlatFile/Services/TrashServiceTest.php`                   |
| `TrashController`                        | `Http/Controllers/Admin/TrashControllerTest.php`                |
| `AuthorizationManager :manage`           | `Modules/Security/AuthorizationManagerManagePermissionTest.php` |
| RBAC, maintenance, registration, storage | `Http/Controllers/CoreHardeningTest.php`                        |
| Guest comments toggle                    | `Http/Controllers/Comments/CommentsControllerTest.php`          |
| `BackupScheduler`                        | `Core/Backup/Services/BackupSchedulerTest.php`                  |
| `runScheduledBackupIfDue`                | `Core/Backup/Services/BackupManagerTest.php`                    |
| Monitoring reports / log scan (It.7)     | `Core/Monitoring/Services/*Test.php`                            |
| Trash meta sidecar                       | `Core/FlatFile/Services/FileWriterTest.php`                     |
| `validatePasswordConfirmation` (2.0.56)  | `Core/Validation/ValidationRulesTest.php`, `AuthControllerTest`, `UserControllerTest`, `validation.test.ts` |




## Preskočené testy (15)

Väčšinou závislosť na **ZipArchive + vfsStream** (create/restore backup) alebo GitHub API integrácia. Plánované doplnenie s reálnym temp filesystemom tam, kde to dáva zmysel.

## Pravidlá pre nové testy

1. **Unit** — izolované služby s `vfsStream` alebo mockmi (`Core/`*).
2. **HTTP** — dedičstvo od `PaginiumCMS\Tests\Http\TestCase`, reálne routy.
3. **Settings v testoch** — `SettingsRepository::setGroup()` s merge existujúcich hodnôt skupiny (validácia schémy).
4. **RBAC** — USER = 403 na mutácie; EDITOR/ADMIN = povolené.
5. Každá iterácia končí: PHPUnit green + PHPStan L8 + záznam v CHANGELOG.



### Automatické čistenie storage po testoch

Integračné HTTP testy zapisujú do `backend/storage/`. `TestStorageCleaner` sa volá **iba na konci** `./scripts/run-all-tests.zsh` (krok 12). Produkčné `data/settings.json` (SMTP, mail, workflows…) sa **nikdy nečíta ani nezapisuje** počas testov — PHPUnit používa `data/settings.testing.json` (`APP_ENV=testing`).

**Maže sa (iba testovacie artefakty):**


| Oblasť           | Čo sa vymaže                                                                      |
| ---------------- | --------------------------------------------------------------------------------- |
| Používatelia     | `data/users/*` s e-mailom `*@example.com` + backupy                               |
| Obsah            | stránky so slug prefixom `bulk-`, `trash-test-`, `seo-test-`, `otp-page-`, …      |
| Media            | súbory s test názvom (`test-upload.*`, `hardening-test.*`, …) + záznam v registry |
| Správy           | kontaktný formulár len s `@example.com`                                           |
| Komentáre        | iba záznamy s `@example.com` v `comments.json`                                    |
| Koš              | iba položky s test slug prefixom                                                  |
| Ephemeral        | cache, OTP, login attempts, firewall                                              |
| Test nastavenia  | `data/settings.testing.json` (nie produkčné `settings.json`)                      |
| Code editor test | `app/Modules/CodeEditorFlowTest_*.php`, `.bak` v `backups/code`                   |


**Nezmaže sa (produkčné dáta):**


| Oblasť                       | Prečo ostáva                                                               |
| ---------------------------- | -------------------------------------------------------------------------- |
| **SMTP a všetky nastavenia** | `data/settings.json` sa **nemení** — testy píšu do `settings.testing.json` |
| Navigácia                    | `data/navigation.json` sa **nemaže**                                       |
| Reálne komentáre             | ostávajú záznamy mimo `@example.com`                                       |
| Produkčné médiá              | ostávajú uploady mimo test názvov                                          |
| Zálohy CMS                   | `storage/backups/*` (okrem code editor `.bak`)                             |
| Logy aplikácie               | `storage/logs/**`                                                          |
| Dev tokeny                   | `dev/registered_tokens.json`                                               |
| Reálne správy                | kontakty s reálnym e-mailom                                                |
| Účty / stránky               | mimo test konvencií (`@example.com`, test slugy)                           |


**Diagnostika:** detail zlyhaní ostáva v PHPUnit výstupe / `run-all-tests.zsh` logu (`~/projects/paginiumcms_tests/`), nie v `storage/logs/` po testoch.

**Záverečný cleanup (krok 12):** po súhrne krokov 1–11 skript spustí `php backend/bin/test-artifacts.php --purge`. Manuálne (napr. po samostatnom `vendor/bin/phpunit`):

```bash
php backend/bin/test-artifacts.php --scan   # JSON prehľad pred/po
php backend/bin/test-artifacts.php --purge  # ľudsky čitateľný report
```

**Jednorazový úklid starých test userov (pred nasadením cleaneru):**

```bash
php -r "require 'vendor/autoload.php'; PaginiumCMS\Tests\Support\TestStorageCleaner::purgeAll();"
```

**Ak** `content:diagnose` **/ PHPUnit hlási poškodený index** (napr. po staršom cleanupe): rebuild z disku:

```bash
php backend/bin/console content:diagnose --fix
```

Index musí mať tvar `{ "version": 1, "items": [ … ] }` — cleanup test stránok z indexu tento formát zachováva.

## CI odporúčanie

```bash
composer install --no-interaction
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit --colors=never
cd frontend && npm ci && npm test && npm run build
```

Lokálny ekvivalent CI + audit + diagnose: `./scripts/run-all-tests.zsh` (pozri sekciu vyššie).

### Produkcia vs. dev (dôležité)

| Prostredie | Čo spúšťať | Čo nespúšťať |
|------------|------------|--------------|
| **Produkčný server** (po deployi) | `curl …/api/health`, `content:diagnose --fix`, voliteľne `CoreHardeningTest` | plných 861 PHPUnit ako gate kvality |
| **Laptop / CI** | `composer gate`, `./scripts/run-all-tests.zsh`, full PHPUnit | — |

Na prod serveri môže full PHPUnit skončiť s pár failures (práva na `backend/app/Modules/` pre Code Editor test, `storage/` owned by `www-data`) — to **neznamená** rozbitý deploy. Maintainer cheat sheet: `PRIVATE_DOMAIN_DEPLOY.md` §12 a §17 (gitignored).

Po PHPUnit na serveri (ak ho spustíš): vždy `php backend/bin/test-artifacts.php --purge` a `content:diagnose --fix`.

Po It. 21: pridať `newman run docs/api/PaginiumCMS.postman_collection.json`.

## Známe incidenty a regresie (2026-07-18+)

Detailný zoznam symptómov, príčin a opráv: **[ISSUES.md](../ISSUES.md)**.  
CI zlyhania (GitHub Actions): sekcia **CI failures** + **ISS-015–022** (2.0.25–2.0.26), **ISS-072–074** (Unreleased / 2.0.52).


| Problém                                        | Test / overenie                                                                    | Stav      |
| ---------------------------------------------- | ---------------------------------------------------------------------------------- | --------- |
| PHPUnit 429 / 503 / OTP persistencia           | `./vendor/bin/phpunit` opakovane                                                   | ✅ ISS-015 |
| PHPStan `phpVersion` vs Composer ^8.4          | `phpstan analyse backend --level=8`                                                | ✅ ISS-016 |
| PHPStan bulk `match.alwaysTrue`                | PHPStan L8                                                                         | ✅ ISS-017 |
| PHPStan `TrashController` fopen                | PHPStan L8                                                                         | ✅ ISS-018 |
| FE `tsc --noEmit` strict errors                | `npm run type-check`                                                               | ✅ ISS-019 |
| ESLint prekročenie `--max-warnings 65`         | `npm run lint`                                                                     | ✅ ISS-020 |
| PHPStan redundantné `is_array()` v log readeri | `phpstan analyse backend --level=8`                                                | ✅ ISS-021 |
| Vitest `MediaManager` krehké text asserts      | `npm test -- MediaManager.test.tsx`                                                | ✅ ISS-022 |
| Vitest worker crash (`useBulkSelection` loop)  | `npm test` – 102/102                                                               | ✅ ISS-005 |
| PHPStan 15 chýb (historicky)                   | `phpstan analyse backend --level=8`                                                | ✅ ISS-006 |
| Debug `client-event` 404                       | Konzola po redeploy, alebo `curl -X POST …/api/debug/client-event` → 204           | ✅ ISS-001 |
| Phantom users / backup v `data/users/`         | `UserRepositoryTest::testFindAllIgnoresBackupFilesAndInvalidRecords`               | ✅ ISS-003 |
| User lookup O(n) scan všetkých JSON            | `UserIndexServiceTest` + `UserRepositoryTest` (`run-all-tests.zsh` krok 17)        | ✅ ISS-057 |
| `navigation.json.backup.*` hromadenie          | `FileWriterTest` + max 5 backupov na súbor                                         | ✅ ISS-004 |
| `GET /api/pages` 500 na serveri                | `php backend/bin/console content:diagnose` + `./scripts/run-all-tests.zsh` krok 11 | ✅ ISS-002 |
| Settings `/settings` crash                     | `zodFromRules` – `.max` on optional                                                | ✅ ISS-009 |
| Login testy → 429 namiesto 401 (full suite)  | `Http\TestCase::setUp` → `LoginAttemptTracker::clearAll()`                         | ✅ ISS-073 |
| Security audit 403 pre ADMIN                 | `security.php` — audit rout oddelené od ACL                                        | ✅ ISS-072 |
| PHPStan po `accessControl` / branding        | `phpstan analyse --level=8 backend/app`                                          | ✅ ISS-074 |


**Node:** CI používa Node 22. Lokálne odporúčané `nvm use 22` pred `npm test`.