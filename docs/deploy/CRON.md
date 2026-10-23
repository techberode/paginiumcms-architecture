---
title: Cron, scheduler, and worker
description: Production execution of flat-file jobs, overlap protection, process identity, logging, demo reset, and monitoring
icon: material/timer-cog
---

# Cron, scheduler, and worker

> PaginiumCMS does not use an SQL job table. The job registry and queue state are flat-file data. Cron or a systemd timer merely invokes the application scheduler and worker.

## 1. What depends on the scheduler

The retained current profile includes, for example:

| Job ID | Handler | Default schedule | Purpose |
|---|---|---|---|
| `content-scheduled-publish` | `content.scheduled_publish` | every minute | publish due content |
| `monitoring-pipeline` | `monitoring.pipeline` | every minute | monitoring and log scanning |
| `backup-scheduled` | `backup.scheduled` | every minute (`* * * * *`) | checks `schedule.json` `next_run` — creates a ZIP only when due (see [BACKUP_RESTORE.md](../en/developer/BACKUP_RESTORE.md#scheduled-backup-cron); optional Nextcloud upload — [ISS-201](../ISSUES.md#iss-201)) |
| `storage-retention` | `storage.retention` | daily at 03:00 | trash / content-version / draft-orphan retention ([STORAGE.md](../en/architecture/STORAGE.md#14-storage-retention-trash-versions-drafts); Settings → **storageRetention** — [ISS-202](../ISSUES.md#iss-202)) |
| `admin-cache-warm` | `admin_cache.warm` | every minute | **disabled by default** — pre-warm admin P2 cache (It.100c); same as `cache:warm-admin` |
| `host-metrics-collect` | `host_metrics.collect` | every 5 min | **disabled by default** — host load/RAM/disk snapshot (It.82d); requires Engine host metrics ON |

Optional CLI equivalents (host cron instead of scheduler jobs):

```bash
php backend/bin/console cache:warm-admin
php backend/bin/console metrics:host-collect
php backend/bin/console backup:run-schedule   # same due check as backup.scheduled when not using scheduler:run
```

Configure backup scope and schedule in **Platform → Backups** (`storage/backups/schedule.json`). After each successful archive, off-site WebDAV upload runs when enabled in **Settings → System → Backups (off-site)**.

See [ADMIN_OPERATIONS.md](../developer/ADMIN_OPERATIONS.md) · [HOST_METRICS.md](../developer/HOST_METRICS.md).

**Queue handlers (not necessarily on a cron schedule):** when an admin enqueues work, `worker:process` must run so jobs complete. Examples:

| Handler | Trigger | Purpose |
|---|---|---|
| `agent.run` | `POST /api/admin/agent/runs` (202) | CMS AI assistant LLM/tool loop (It.75) |
| `content.translate` | translation enqueue (It.76) | long translation proposals |

Without `worker:process`, agent runs stay **queued** and translation jobs may not finish. See [AGENT_OPERATIONS.md](../en/runbooks/AGENT_OPERATIONS.md).

The authoritative list is the registry and implementation of the exact release tag. The admin UI may enable, disable, and edit job cron expressions.

## 2. Scheduler versus worker

```text
scheduler:run
→ evaluates due jobs
→ creates/runs scheduled work through its handler

worker:process
→ processes queued work
→ records outcome, audit, and retry state
```

Both processes must use the same checkout, `.env`, storage, and timezone as the web application.

## 3. Canonical Docker cron

For a production Docker stack, invoke CLI inside the same PHP container under the application identity:

```cron
* * * * * flock -n /run/lock/paginium-scheduler.lock /var/lib/docker/compose/paginiumcms/stack.sh exec -T -u www-data php php backend/bin/console scheduler:run >> /var/log/paginiumcms/scheduler.log 2>&1
* * * * * flock -n /run/lock/paginium-worker.lock /var/lib/docker/compose/paginiumcms/stack.sh exec -T -u www-data php php backend/bin/console worker:process >> /var/log/paginiumcms/worker.log 2>&1
```

Benefits:

- the same PHP version and extensions as the web runtime,
- the same storage mount,
- the same environment variables,
- less host/container permission drift.

`flock -n` prevents the next minute run from overlapping a still-running process.

**Off-site backup (Nextcloud/WebDAV):** scheduled runs use the same upload as manual backups. If local ZIPs appear but nothing reaches Nextcloud, compare CLI vs web: `php backend/bin/console backup:offsite-probe` (and `--upload`) **inside the same PHP container** as cron (`stack.sh exec …`). A missing or empty `APP_KEY` in the cron environment prevents decrypting the stored WebDAV password — manual runs from the admin UI can still work when FPM loads `.env` correctly. Ensure `.env` contains a stable `APP_KEY` and cron never invokes bare host PHP against a different checkout.

## 4. Host PHP alternative

Host cron is acceptable only when:

- host PHP and extensions match the release contract,
- the host user has controlled group permissions,
- it uses the same checkout and storage,
- `.env` is loaded consistently,
- testing proves behavior equivalent to the web runtime.

```cron
* * * * * flock -n /run/lock/paginium-scheduler.lock sh -lc 'cd /var/www/paginiumcms.com && /usr/bin/php backend/bin/console scheduler:run' >> /var/log/paginiumcms/scheduler.log 2>&1
```

Do not randomly mix host and container models between scheduler, worker, and manual operations.

## 5. Cron user and permissions

Use a dedicated deploy/service account that:

- can invoke only the required stack wrapper or Docker command,
- cannot modify application code outside the release process,
- has group access to storage and logs,
- has no plaintext production secrets in crontab,
- never prints `.env` into logs.

When `sudo` is required, use a narrow sudoers rule for the exact wrapper, not general `NOPASSWD: ALL`.

## 6. Time, timezone, and DST

Cron runs in the host timezone; the application may use its own `APP_TIMEZONE`. Document which layer interprets the cron expression.

Verify:

```bash
timedatectl
php -r 'echo date_default_timezone_get(), PHP_EOL;'
date --iso-8601=seconds
```

NTP must be synchronized. Scheduled publishing and TOTP are sensitive to clock drift. Test the DST transition day for locally scheduled jobs.

## 7. Logging and rotation

Logs belong outside the repository:

```text
/var/log/paginiumcms/scheduler.log
/var/log/paginiumcms/worker.log
/var/log/paginiumcms/demo-reset.log
```

Example logrotate policy:

```text
/var/log/paginiumcms/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0640 deploy-user www-data
}
```

Logs must not contain passwords, tokens, TOTP secrets, QR payloads, or complete sensitive requests. The same redaction contract applies to CI and production job logs.

## 8. Manual smoke

```bash
cd /var/www/paginiumcms.com
/var/lib/docker/compose/paginiumcms/stack.sh \
  exec -T -u www-data php \
  php backend/bin/console content:diagnose --json

/var/lib/docker/compose/paginiumcms/stack.sh \
  exec -T -u www-data php \
  php backend/bin/console scheduler:run

/var/lib/docker/compose/paginiumcms/stack.sh \
  exec -T -u www-data php \
  php backend/bin/console worker:process
```

Even with a successful exit, inspect the outcome and application log. A payload containing `success:false` must not be presented as a successful operation.

## 9. Scheduled publish acceptance

Practical test:

1. create a test draft,
2. schedule publication for about two minutes later,
3. verify it is not public before the due time,
4. run scheduler and worker,
5. verify `published` state, revision, and public endpoint,
6. remove the test artifact.

When OTP approval is mandatory, include approval or verify the expected block.

## 10. Demo automatic reset

Reference cron:

```cron
*/15 * * * * flock -n /run/lock/paginium-demo-reset.lock /var/lib/docker/compose/paginiumcms-demo/stack.sh exec -T -u www-data php php backend/bin/console demo:reset-if-due >> /var/log/paginiumcms/demo-reset.log 2>&1
```

Cron checks every 15 minutes; the actual reset follows `DEMO_AUTO_RESET_MINUTES`. Never enable demo reset in a customer/production profile.

Related issues:

- [ISS-099](../ISSUES.md#iss-099) — permission denied during demo reset,
- [ISS-102](../ISSUES.md#iss-102) — missing demo data tree.

## 11. Failure policy and monitoring

Alert at least on:

- repeated non-zero exit,
- no successful scheduler run beyond the expected interval,
- growing queue without processing,
- a lock held for too long,
- unreadable registry or storage,
- a backup job without a new verified artifact,
- scheduled content still unpublished after its due time.

Cron mail without a correctly configured MTA is not reliable monitoring. Use the application, systemd journal, ntfy, or the existing monitoring stack.

## 12. Troubleshooting

| Symptom | Cause | Resolution |
|---|---|---|
| job never runs | missing cron/timer or wrong user | `crontab -l`, journal/syslog |
| job runs multiple times | overlapping intervals | `flock`, idempotency |
| permission denied | UID/GID or mount mismatch | run in container, setgid |
| different storage from web | wrong path/mount | `pwd`, diagnose, compose config |
| empty registry | initial seed or corruption | admin scheduler, recovery contract |
| manual command works, cron fails | restricted PATH/environment | absolute paths and wrapper |
| log grows forever | no rotation | logrotate/journald policy |
| wrong publish time | timezone/NTP/DST | `timedatectl`, app timezone |

## 13. Related documents

- [DEPLOY.md](./DEPLOY.md)
- [DEMO_DEPLOY.md](./DEMO_DEPLOY.md)
- [LOGGING.md](../user/LOGGING.md)
- [TESTING.md](../developer/TESTING.md)
- [ITERATION_29.md](../ITERATION_29.md)
