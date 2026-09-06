# Release `v2.1.0-beta.66` — Analytics production readiness + retention

> **Date:** 2026-09-06  
> **Tag:** `v2.1.0-beta.66`  
> **Type:** Analytics, retention, WAF/bots, onboarding (cumulative since `beta.64`)

---

## One-line summary

Production-grade **analytics** (geo, bots, trends, platform labels, manual bot ban), **scheduled retention** for analytics and all log sources, plus **setup preflight**, **backup restore fixes**, and **article author picker** documented in `beta.65` but shipped in this tag if you skipped `beta.65`.

---

## What shipped (beta.66 scope)

| Area | Change |
|------|--------|
| **Analytics retention** | `analytics.retentionDays` (default 90) in Settings → System |
| **Log retention** | `LogRetentionService` purges app, audit, event, user logs (manual + scheduler) |
| **Scheduler** | Job `maintenance.cleanup` (`30 3 * * *`) — analytics + log purge |
| **Trend KPIs** | Overview cards: % change vs previous period of equal length |
| **Platform detection** | OS labels (Mobile, PC Windows, macOS, Linux…) + Devices chart |
| **Bot analytics** | Bots tab: human/bot share, top bots, recent visits |
| **Ban from analytics** | `POST /api/admin/analytics/bots/ban` → temporary WAF jail + `analytics_bot_ban` incident |
| **GeoIP** | Primary provider `ipapi.co` (HTTPS); flags in geo charts |
| **WAF bots** | `blockEmptyUserAgent`, `blockScraperTools`; search bots never blocked |
| **Logging** | `logging.enabled` is a real master switch for structured loggers |
| **GDPR blocks** | Custom cookie-policy blocks no longer vanish while editing empty drafts |

---

## Also in this tag (beta.65 milestone, if not previously deployed)

| Area | Issue |
|------|--------|
| Setup preflight | [ISS-162](ISSUES.md#iss-162) — `GET /api/setup/preflight` |
| Backup restore | [ISS-163](ISSUES.md#iss-163) — full `content/` ZIP, path fix, cache rebuild |
| Article author | Per-article author picker (`authorId`, bio, avatar override) |

See [RELEASE_2_1_0_BETA_65.md](RELEASE_2_1_0_BETA_65.md) for setup/backup detail.

---

## Fixes documented (ISS)

| ID | Summary |
|----|---------|
| [ISS-164](ISSUES.md#iss-164) | Analytics geo always `Unknown` — `ip-api.com` free tier rejects HTTPS |
| [ISS-165](ISSUES.md#iss-165) | GDPR custom blocks removed immediately in admin editor |
| [ISS-166](ISSUES.md#iss-166) | Analytics retention purge — `glob()` unreliable on vfs / some FS |
| [ISS-167](ISSUES.md#iss-167) | Article author fields missing from `ContentEditorLoadData` (TS) |

---

## Settings (new / relevant)

| Group | Key | Default | Notes |
|-------|-----|---------|-------|
| `analytics` | `retentionDays` | 90 | Purge visit/daily/visitor files |
| `logging` | `retentionDays` | 30 | All four log sources |
| `logging` | `enabled` | true | Master switch — off = no structured writes |
| `firewall` | `blockEmptyUserAgent` | true | WAF |
| `firewall` | `blockScraperTools` | false | WAF — curl/wget when enabled |

---

## API additions

| Method | Path | Auth |
|--------|------|------|
| `GET` | `/api/setup/preflight` | pre-auth |
| `POST` | `/api/admin/analytics/bots/ban` | ADMIN + 2FA |

Body for ban: `{ "ip": "203.0.113.10", "bot_name": "curl/8.4.0" }`.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.66 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.66 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

Post-deploy smoke:

```bash
curl -fsS https://paginiumcms.com/api/health
curl -fsS https://demo.paginiumcms.com/api/health
./scripts/smoke-it25.sh   # from checkout, against local or remote URL as configured
```

Ensure **scheduler cron** runs so `maintenance.cleanup` executes (see [CRON.md](../deploy/CRON.md)).

---

## Verification checklist

- [ ] `./scripts/iteration-gate.sh` green before tag
- [ ] Admin → Analytics → trend badges show real % (not placeholder)
- [ ] Admin → Analytics → Devices → Platforms chart
- [ ] Admin → Analytics → Bots → Ban IP → WAF incident visible
- [ ] Settings → analytics.retentionDays + logging.retentionDays visible
- [ ] Settings → Privacy → add empty GDPR block → survives until filled
- [ ] Backup create → soft-delete article → restore → article visible ([BACKUP_RESTORE.md](developer/BACKUP_RESTORE.md))

---

## Links

- [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-66)
- [BACKUP_RESTORE.md](developer/BACKUP_RESTORE.md)
- [DEPLOY.md](../deploy/DEPLOY.md)
- [DEMO_DEPLOY.md](../deploy/DEMO_DEPLOY.md)
