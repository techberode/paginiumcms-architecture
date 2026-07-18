# Testovanie – PaginiumCMS

> Posledná aktualizácia: 2.0.18 · **550+ PHPUnit testov**, PHPStan level 8, **107 Vitest testov**

## Spustenie

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
```

## Iterácia 21 – API kontrakt

| Komponent | Test / artefakt |
|-----------|-----------------|
| `JsonResponder` | `Http/Support/JsonResponderTest.php` |
| HTTP response shapes | `Http/Contract/ApiResponseShapeTest.php` |
| MSW handlery | `frontend/src/mocks/handlers.test.ts` |
| Postman smoke | `docs/api/PaginiumCMS.postman_collection.json` |
| Kontrakt docs | `docs/architecture/API_CONTRACT.md` |

### Postman / Newman (voliteľné)

```bash
# Vyžaduje bežiaci backend na :8080 a npx newman
npx newman run docs/api/PaginiumCMS.postman_collection.json \
  --env-var baseUrl=http://localhost:8080
```

Dev mocks: `VITE_MSW=true npm run dev` v `frontend/`.

### CI (GitHub Actions)

Workflow `.github/workflows/ci.yml` — PHPUnit, PHPStan, Vitest, Newman smoke (public endpoints).

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

Bootstrap aplikácie pre HTTP testy: `backend/tests/Http/TestCase.php` — načíta reálny `bootstrap/app.php`, session, rate-limit cache reset.

## Iterácia 20 – pokrytie core hardening

| Komponent | Test súbor |
|-----------|--------------|
| `PermissionMiddleware` | `Http/Middleware/PermissionMiddlewareTest.php` |
| `MaintenanceModeMiddleware` | `Http/Middleware/MaintenanceModeMiddlewareTest.php` |
| `StorageController` | `Http/Controllers/Storage/StorageControllerTest.php` |
| `TrashService` | `Core/FlatFile/Services/TrashServiceTest.php` |
| `TrashController` | `Http/Controllers/Admin/TrashControllerTest.php` |
| `AuthorizationManager :manage` | `Modules/Security/AuthorizationManagerManagePermissionTest.php` |
| RBAC, maintenance, registration, storage | `Http/Controllers/CoreHardeningTest.php` |
| Guest comments toggle | `Http/Controllers/Comments/CommentsControllerTest.php` |
| `BackupScheduler` | `Core/Backup/Services/BackupSchedulerTest.php` |
| `runScheduledBackupIfDue` | `Core/Backup/Services/BackupManagerTest.php` |
| Monitoring reports / log scan (It.7) | `Core/Monitoring/Services/*Test.php` |
| Trash meta sidecar | `Core/FlatFile/Services/FileWriterTest.php` |

## Preskočené testy (15)

Väčšinou závislosť na **ZipArchive + vfsStream** (create/restore backup) alebo GitHub API integrácia. Plánované doplnenie s reálnym temp filesystemom tam, kde to dáva zmysel.

## Pravidlá pre nové testy

1. **Unit** — izolované služby s `vfsStream` alebo mockmi (`Core/*`).
2. **HTTP** — dedičstvo od `PaginiumCMS\Tests\Http\TestCase`, reálne routy.
3. **Settings v testoch** — `SettingsRepository::setGroup()` s merge existujúcich hodnôt skupiny (validácia schémy).
4. **RBAC** — USER = 403 na mutácie; EDITOR/ADMIN = povolené.
5. Každá iterácia končí: PHPUnit green + PHPStan L8 + záznam v CHANGELOG.

## CI odporúčanie

```bash
composer install --no-interaction
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit --colors=never
cd frontend && npm ci && npm test && npm run build
```

Po It. 21: pridať `newman run docs/api/PaginiumCMS.postman_collection.json`.

## Známe incidenty a regresie (2026-07-18)

Detailný zoznam symptómov, príčin a opráv: **[ISSUES.md](../ISSUES.md)**.

| Problém | Test / overenie | Stav |
|---------|----------------|------|
| Vitest worker crash (`useBulkSelection` loop) | `npm test` – 102/102 | ✅ Opravené |
| PHPStan 15 chýb | `phpstan analyse backend --level=8` | ✅ Opravené |
| Debug `client-event` 404 | Konzola po redeploy, alebo `curl -X POST …/api/debug/client-event` → 204 | ✅ Opravené |
| Phantom users / backup v `data/users/` | `UserRepositoryTest::testFindAllIgnoresBackupFilesAndInvalidRecords` | ✅ Hardening |
| `navigation.json.backup.*` hromadenie | `FileWriterTest` + max 5 backupov na súbor | ✅ Retencia |
| `GET /api/pages` 500 na serveri | PHPUnit OK – skontrolovať server log + index obsahu | 🔍 Env |
| Settings `/settings` crash | `zodFromRules` – `.max` on optional | ✅ Opravené |

**Node:** CI používa Node 22. Lokálne odporúčané `nvm use 22` pred `npm test`.
