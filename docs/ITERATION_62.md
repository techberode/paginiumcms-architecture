# Iteration 62 – Scheduler production hardening & UX

**Release target:** 2.1.0-beta.9  
**Status:** ✅ Implemented

## Goal

Make the job scheduler reliable on **Docker + host shared storage** and unambiguous in admin UI: distinguish *executed/skipped/failed* from HTTP errors and permission issues.

## Problems solved

| Issue | Root cause | Fix |
|-------|------------|-----|
| POST `/jobs/…/run` → 500 | `www-data` could not write `data/jobs/runs.json`; PHP warnings leaked into JSON | Storage permissions (2775 + `marian:www-data`), `@file_put_contents`, `display_errors=Off` in Docker PHP |
| Admin „Run“ looked broken | UI treated `success: false` (e.g. „Backup not due“) as failure | Toast on HTTP success + backend `outcome` field |
| `content:diagnose` false positive | Host user `marian` writable ≠ Docker `www-data` | Document container `touch` test; diagnose fields kept as hint |
| Placeholder cron hint | Hardcoded `/path/to/paginiumcms` | `JobsController::buildCronHint()` uses project root / `APP_ROOT` |
| No CLI smoke for one job | Only `scheduler:run` (all due jobs) | `php backend/bin/console jobs:run {id}` |

## Backend

### Run outcome (`ScheduledJobRunner`)

Each run entry now includes:

```json
{
  "success": false,
  "outcome": "skipped",
  "reason": "no_schedule",
  "message": "Backup not due"
}
```

| `outcome` | Meaning |
|-----------|---------|
| `completed` | Handler did meaningful work |
| `skipped` | Ran OK, nothing due (`not_due`, `no_schedule`, `nothing_due`, …) |
| `failed` | Handler error or unexpected reason |

Skipped reasons: `not_due`, `no_schedule`, `disabled`, `nothing_due`, `some_items_skipped`.

When run log write fails, response still returns 200 with `run_log_persisted: false` and `run_log_error`.

### CLI

```bash
php backend/bin/console jobs:run backup-scheduled
php backend/bin/console jobs:run monitoring-pipeline
php backend/bin/console jobs:run content-scheduled-publish
```

### Storage / Docker

- `FileWriter`: mkdir `0775`, new files `0664`, suppressed write warnings
- `docker/php/php.ini`: `display_errors=Off`, `log_errors=On`
- `content:diagnose`: checks `data/jobs` and `scheduler-state.json` writability

## Frontend (`/scheduler`)

- Green toast when HTTP run succeeds (message from backend)
- Amber warning if `run_log_error` present
- Recent runs: badges **Hotovo / Preskočené / Zlyhanie**
- Copy-to-clipboard for generated crontab line

## Production checklist

```bash
# 1. Permissions (setgid for shared group)
sudo mkdir -p backend/storage/app/content/data/jobs
sudo chown -R marian:www-data backend/storage
sudo find backend/storage -type d -exec chmod 2775 {} \;
sudo find backend/storage -type f -exec chmod 664 {} \;

# 2. Write test as www-data inside Docker
./stack.sh exec -u www-data php sh -c \
  'touch /var/www/html/backend/storage/app/content/data/jobs/.write-test && rm ... && echo WRITE_OK'

# 3. Job smoke
./stack.sh exec php php backend/bin/console jobs:run backup-scheduled

# 4. OPcache
./stack.sh restart php
```

## Files touched

- `ScheduledJobRunner.php`, `ContentScheduledPublishHandler.php`, `JobsController.php`
- `FileWriter.php`, `ContentDiagnoseCommand.php`, `RunJobCommand.php`
- `SchedulerView.tsx`, `jobRunOutcome.ts`
- `docker/php/php.ini`, `docs/deploy/CRON.md`

## Related

- [ITERATION_29.md](ITERATION_29.md) — original scheduler
- [ITERATION_59.md](ITERATION_59.md) — scheduled content publish job
- [deploy/CRON.md](deploy/CRON.md) — production crontab
