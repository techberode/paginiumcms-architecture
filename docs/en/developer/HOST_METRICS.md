# Host metrics snapshot (It.82d)

Optional **OS-level** load, RAM, and disk snapshot for admin dashboard and monitoring reports. PHP APM (Performance Guard) does not replace this — it measures request latency inside the app, not host pressure.

## Enable

1. **Settings → Engine**
   - `Enable host metrics snapshot` → on
   - Set **max age** (default 600 s) — older snapshots show as *stale*
   - Set **ingest token** (password field, encrypted at rest) if you use HTTP ingest
2. **Jobs → Scheduled jobs**
   - Enable system job **Host metrics snapshot (It.82d)** (`host-metrics-collect`, default cron `*/5 * * * *`), **or** run collection from host cron (below).

Scheduler already runs `scheduler:run` every minute on typical installs; the host job only executes when **both** the job is enabled and engine host metrics are on.

## Collect

| Method | When |
|--------|------|
| `php backend/bin/console metrics:host-collect` | Same machine as `data/` (writes `data/metrics/host-latest.json`) |
| `./scripts/host-metrics-collect.sh` | Wrapper for the console command |
| Scheduled job `host_metrics.collect` | In-app cron via job registry |
| `POST /api/admin/metrics/host/ingest` | Loopback / private IP + header `X-Host-Metrics-Token` (CSRF-exempt); for sidecar or host script posting JSON |

Ingest body must match the sanitizer shape (`collected_at`, optional `load`, `memory`, `disk`, `uptime_seconds`).

## Read

- Dashboard → Performance Guard panel (load, RAM, disk when snapshot is *ok*)
- `GET /api/admin/metrics/host` (auth + `metrics:read`)
- APM overview embeds `host_metrics` when cached
- Monitoring e-mail/report includes a **Host metrics** section when snapshot is fresh

## Production checklist (single VPS)

```cron
* * * * * cd /path/to/paginiumcms && php backend/bin/console scheduler:run && php backend/bin/console worker:process
```

After deploy: enable engine flag + enable `host-metrics-collect` job (or add `metrics:host-collect` every 5 min beside `cache:warm-admin`).

## Security

- Ingest is **not** a public metric endpoint: disabled by default, token required, IP restricted to loopback/private ranges.
- Snapshot file lives under `data/metrics/` — not web-served.
- No shell execution; collector reads `/proc` and `disk_*` only.

## Related

- [ADMIN_LOAD_SIMULATION.md](ADMIN_LOAD_SIMULATION.md) — admin concurrency vs host load
- [ITERATION_82.md](../ITERATION_82.md) — Origin catalog slice **82d**
