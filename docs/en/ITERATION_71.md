# Iteration 71 — Performance Guard (application APM)

> **Status:** ⏳ planned  
> **Priority:** 🟡  
> **Wave:** [Hybrid Engine HE-4](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Depends on:** [It.69](ITERATION_69.md)  
> **Complements:** It.7 reporting and the remaining host-metrics scope of It.46

## Goal

Introduce lightweight **in-request APM** for Slim: latency, memory delta, storage/cache operation counts, and error budgets. Performance Guard reports regressions and may perform only conservative, pre-approved actions on derived layers.

It is not a replacement for Prometheus, a host agent, or a profiler. It is an application guardrail that also works on a small self-hosted installation.

---

## Measurement model

| Metric | Source | Note |
|--------|--------|------|
| request duration | monotonic timer | route template rather than a raw URL containing a slug/token |
| peak/memory delta | PHP runtime | trend, not exact host RAM |
| storage reads/writes | instrumented It.68 interface | no sensitive filenames |
| cache hit/miss/fallback | It.69 | aggregated by resource type |
| response status/error class | middleware | no stack trace in the metric |
| queue latency | job metadata | optional for publish/translation/agent |

Samples are stored in a bounded ring buffer or exported through the existing metrics contract. Content payloads, query tokens, and secrets are not measured.

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `PerformanceGuardMiddleware` | timing, route label, status, and sample submission |
| `PerformanceContext` | request-local counters for storage/cache operations |
| `PerformanceSampleStore` | atomic bounded ring buffer under `data/metrics/` |
| `PerformanceGuardPolicy` | warning/critical budgets per route group |
| `PerformanceAggregator` | p50/p95/p99, error rate, and breach windows |
| `PerformanceIncidentService` | incident deduplication and notifier integration |
| `SafeRemediationService` | allow-listed actions on derived layers only |
| Admin API | summaries and recent breaches; `metrics:read` permission |

---

## Settings

```yaml
engine:
  performanceGuard:
    enabled: false
    sampleRate: 1.0
    latencyMsWarning: 200
    latencyMsCritical: 500
    breachCount: 3
    windowMinutes: 10
    remediationMode: suggest   # off | suggest | automatic
```

The feature is disabled by default. A production administrator sets budgets for their hardware and workload; the documentation does not present defaults as universal SLAs.

---

## Safe remediation model

### `suggest` — recommended default

The guard creates an incident and may recommend:

- rebuilding the index,
- purging a specific cache tag,
- enabling an already configured cache driver,
- checking the scheduler/worker queue.

### `automatic` — explicit opt-in

An automatic action is allowed only when:

1. the capability passed a prior test,
2. the change is idempotent and reversible,
3. it affects only index/cache/worker behavior, never primary content,
4. cooldown and maximum attempts exist,
5. every action has an audit record and result,
6. failure falls back to `suggest` rather than chaining random changes.

The guard **must not automatically enable Redis** without valid, verified Redis configuration. It never disables auth, CSRF, WAF, audit, or validation “for performance.”

---

## Frontend

- dashboard widget: p95, error rate, cache fallback, and breach trend,
- Settings → Engine → Performance Guard with clear overhead/retention help,
- incident detail with route group, time window, and recommendation,
- separate link to It.46 host metrics so PHP and OS layers are not conflated.

---

## Retention and data protection

- the ring buffer has a fixed limit and atomic rotation,
- routes are stored as templates (`/api/articles/{slug}`), not raw paths,
- IP, user-agent, and user IDs are excluded unless required by a separate security incident,
- metrics exports contain no title, body, prompt, or translation,
- administrators can clear samples without losing content.

---

## Out of scope

- host CPU/RAM/disk agent,
- full distributed tracing,
- infrastructure autoscaling,
- changes to primary content,
- automatic disabling of security middleware,
- promises of a specific performance level on unknown hardware.

---

## Tests

- middleware sample on health/content routes,
- route template contains no raw slug/token,
- counters from It.68/69 aggregate correctly,
- breach window deduplicates notifications,
- disabled fast path has measurably minimal overhead,
- `suggest` does not change settings,
- automatic without capability → no change,
- automatic with a verified reversible action → one audited action per cooldown,
- ring-buffer limit and concurrent writes,
- Classic without the guard behaves identically.

---

## Definition of Done

- [ ] Staging displays p95 and breach trends without content payloads.
- [ ] An intentionally slow test route creates a deduplicated incident.
- [ ] Defaults are `enabled=false` and `remediationMode=suggest`.
- [ ] Automatic mode has capability gate, cooldown, rollback, and audit.
- [ ] Redis is never enabled without verified configuration.
- [ ] It.46 and It.71 have clearly separated responsibilities.
- [ ] Retention, privacy, and runbook are documented in SK/EN.

## Related

[It.69 cache](ITERATION_69.md) · [It.46 host metrics](ITERATION_46.md) · [It.7 reports](ITERATION_7.md)
