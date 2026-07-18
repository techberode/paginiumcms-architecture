# Iteration 7 – Scheduled monitoring reports + log incidents

**Status:** Complete  
**Version:** 2.0.17

## Summary

Iteration 7 adds scheduled monitoring reports (hour / day / week), HTML email digest styled like a server dashboard, log incident alerts (ERROR/WARNING), admin UI on `/notifications`, and CLI `monitoring:run-schedule` for hosting cron.

## Backend

### Settings (`monitoring` group)

| Key | Purpose |
|-----|---------|
| `reportsEnabled` | Enable scheduled reports (cron required) |
| `reportInterval` | `hour` \| `day` \| `week` |
| `reportTime` | Send time `HH:MM` (daily / weekly) |
| `reportWeekday` | Weekly day (`mon`–`sun`) |
| `reportMinute` | Minute past hour (hourly) |
| `reportConnector` | `email`, `ntfy`, `discord`, `telegram`, `webhook`, `all` |
| `reportIncludeAnalytics` | Analytics section |
| `reportIncludeHealth` | Health checks section |
| `reportIncludeFlatFile` | Flat-file counts section |
| `notifyLogErrors` | Alert on log ERROR/CRITICAL |
| `notifyLogWarnings` | Alert on log WARNING |
| `logIncidentConnector` | Connector for log incidents |

### Services

| Class | Role |
|-------|------|
| `MonitoringReportBuilder` | Plain text + dark HTML email body |
| `MonitoringReportScheduler` | Due logic + send via connector |
| `LogIncidentScanner` | Scan app logs since last run |
| `MonitoringScheduler` | Orchestrator (report + log scan) |
| `SchedulerStateStore` | `data/scheduler-state.json` |
| `IncidentNotifier::notifyViaConnectorDetailed()` | Single connector + preflight reasons |

### Analytics extensions (`Reporter`)

- `getTopIpStats()` – IP visits + most visited URI
- `getTopArticles()` – URIs matching `/blog`, `/articles`, `/clanky`, …
- Week period aggregates last 7 days for top pages/referrers/devices

### CLI

```bash
php backend/bin/console monitoring:run-schedule
```

Symfony 8: `bin/console` uses `addCommand()` (not deprecated `add()`).

### Combined hosting cron (recommended)

```bash
* * * * * cd /path/to/paginiumcms && php backend/bin/console backup:run-schedule && php backend/bin/console monitoring:run-schedule >> /var/log/paginium-scheduler.log 2>&1
```

## API (admin, auth + 2FA + ADMIN)

| Method | Path | Body | Notes |
|--------|------|------|-------|
| GET | `/api/admin/notifications/overview` | — | Connectors + schedule preview |
| GET | `/api/admin/notifications/schedule` | — | Schedule + scheduler state |
| POST | `/api/admin/notifications/report/send` | `{ "force": true }` | Manual report; `force` bypasses schedule |
| POST | `/api/admin/notifications/schedule/run` | — | Simulate cron (report + log scan) |
| POST | `/api/admin/notifications/test` | `{ "adapter": "email" }` | Test connector |

### Report send errors (`422`)

| `result.reason` | Fix |
|-----------------|-----|
| `connector_inactive` | Enable connector in Settings → Connectors; match `reportConnector` |
| `missing_recipient` | Set Monitoring → alert email or General → admin email |
| `no_connectors` | Enable at least one connector |
| `delivery_failed` | SMTP host/port/auth; test with **Test** on `/notifications` |

## HTML email layout

Dark theme (`#070b14`), Slovak section titles:

1. Prehľad návštevnosti (metrics + mobile/desktop bar)
2. Štatistiky IP
3. Top stránky
4. Top články
5. Top odkazujúce stránky
6. Systémové informácie (PaginiumCMS health checks)
7. PaginiumCMS flat-file counts

**Not included** (host-level metrics – future server agent / It.29): uptime, CPU, RAM, `df`, Docker list, SSH failures, mail queue, top processes.

## Frontend

- `/notifications` – scheduled reports card, blockers hint, **Odoslať report teraz**, **Simulovať cron**
- `api/notifications.ts` – `sendMonitoringReport`, `runMonitoringSchedule`

## Tests

| Component | Test file |
|-----------|-----------|
| `MonitoringReportBuilder` | `Core/Monitoring/Services/MonitoringReportBuilderTest.php` |
| `MonitoringReportScheduler` | `Core/Monitoring/Services/MonitoringReportSchedulerTest.php` |
| `LogIncidentScanner` | `Core/Monitoring/Services/LogIncidentScannerTest.php` |
| `MonitoringScheduler` | `Core/Monitoring/Services/MonitoringSchedulerTest.php` |

## Quick start (SK)

1. **Settings → Connectors** – SMTP + Email channel enabled, test OK  
2. **Settings → Monitoring** – alert email, report interval/time, connector  
3. **Hosting cron** – combined line above (every minute)  
4. **Admin** – `/notifications` → Odoslať report teraz (works even when `reportsEnabled` is off)
