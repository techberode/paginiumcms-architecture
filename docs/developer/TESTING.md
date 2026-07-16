# Testovanie – PaginiumCMS

> Posledná aktualizácia: 2.0.8 · **488 PHPUnit testov**, PHPStan level 8

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
