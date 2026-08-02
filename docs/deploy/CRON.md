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
| `backup-scheduled` | `backup.scheduled` | daily at 02:00 | scheduled CMS backup |

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
