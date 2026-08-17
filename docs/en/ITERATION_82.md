# Iteration 82 — Origin Panel (maintainer product cockpit)

> **Status:** ⏳ planned (spec only — no implementation yet)  
> **Priority:** 🔵 (maintainer tooling; no customer-facing impact)  
> **Wave:** Product operations (post-It.71 Performance Guard, post-It.81 editorial ops)  
> **Depends on:** existing dashboard APIs, health/capability probe patterns, Demo module packaging model  
> **Snapshot:** 2026-08-15 · planned after `v2.1.0-beta.54`

## Goal

Ship an **Origin Panel** — an admin-only cockpit for the **project-owned** PaginiumCMS instances (maintainer dev machine + `paginiumcms.com` production). The panel combines:

1. **Lightweight operational metrics** (APM, analytics, flat-file counters — not full Grafana),
2. **An auto-checked feature checklist** (each row verified by code probes; **no manual checkboxes**),
3. **Strict distribution gating** — the module is **absent from customer install archives**, same class of exclusion as the **Demo module**.

This iteration is **not** a replacement for host monitoring (Prometheus/node-exporter) or external Grafana. It reuses in-app signals already collected by PaginiumCMS.

---

## Product position (who sees it)

| Deployment profile | Origin Panel | Notes |
|--------------------|:------------:|-------|
| Maintainer **development** machine | ✅ | `ORIGIN_PANEL=true` in local `.env` |
| **paginiumcms.com** production (project home site) | ✅ | enabled via env on that stack only |
| **demo.paginiumcms.com** (Demo module) | ❌ | demo keeps its own isolated profile; no Origin Panel |
| Customer production / self-hosted install | ❌ | module **not shipped** in distribution archive |
| Generic `APP_ENV=production` without allowlist | ❌ | fail-closed (same philosophy as `DemoMode`) |

**Boundary:** Origin Panel is **maintainer intelligence**, not an operator feature for downstream installs. Customer documentation must not mention it; routes must not exist when the module is omitted from the package.

---

## Relationship to Demo module (It.13)

Both are **env-gated, distribution-excluded** modules, but they serve different hosts:

| | Demo (It.13) | Origin Panel (It.82) |
|---|--------------|----------------------|
| Host | `demo.paginiumcms.com` | dev machine + `paginiumcms.com` |
| Env flag | `DEMO_MODE=true` | `ORIGIN_PANEL=true` |
| Audience | public trial visitors | maintainers / super-admins only |
| In customer archive | ❌ | ❌ |
| Data scope | isolated demo storage tree | read-only aggregate over production SSOT |

They must **not** share storage paths or public settings keys beyond the same gating pattern.

---

## Master checklist

| ID | Slice | Priority | Status | Summary |
|----|-------|----------|--------|---------|
| **82a** | Origin gate + packaging exclusion | 🔵 P1 | ⏳ planned | `OriginPanelMode`, env contract, fail-closed boot, omit from distribution build |
| **82b** | Feature probe registry + checklist UI | 🔵 P2 | ⏳ planned | PHP probes, admin API, React panel (auto status only) |
| **82c** | Dashboard metrics lite (optional) | 🔵 P3 | ⏳ planned | Extend existing APM/analytics widgets; flat-file time series where cheap |
| **82d** | Host metrics ingest hook (optional) | 🔵 P4 | ⏸️ deferred | Absorbs remainder of [It.46](ITERATION_46.md) only if needed; not required for MVP |

### Status legend

| Symbol | Meaning |
|--------|---------|
| ⏳ planned | Spec agreed; not started |
| 🚧 in progress | Active implementation |
| ✅ done | Shipped in a tagged release |
| ⏸️ deferred | Consciously postponed |

---

## 82a — Origin gate and packaging

### Env contract (`.env.example` — maintainer profiles only)

```env
# Maintainer-only. Never enable on customer installs. Not present in distribution archive.
ORIGIN_PANEL=false
# Optional hardening: comma-separated hosts (default: derive from APP_URL)
ORIGIN_PANEL_ALLOWED_HOSTS=paginiumcms.com,localhost,127.0.0.1
```

### Backend service (pattern: `DemoMode`)

New class e.g. `backend/app/Modules/Origin/Services/OriginPanelMode.php`:

| Method | Behaviour |
|--------|-----------|
| `isEnabledFromEnv(): bool` | `ORIGIN_PANEL=true` |
| `isAllowedHost(string $host): bool` | match `ORIGIN_PANEL_ALLOWED_HOSTS` or `APP_URL` host |
| `isActive(): bool` | env **and** host allowlist **and** not misconfigured |
| `warnIfMisconfigured(): void` | one-time `error_log` when enabled on disallowed profile |
| `failClosedOnCustomerPackage(): bool` | if module files present but env false → routes 404 |

**Boot:** call `OriginPanelMode::warnIfMisconfigured()` from `backend/bootstrap/app.php` (mirror `DemoMode::warnIfMisconfigured()`).

**Misconfiguration rules (fail-closed):**

- `ORIGIN_PANEL=true` on a host not in allowlist → treated as **disabled**.
- Customer tarball built **without** Origin module → zero routes; no stub that leaks feature names in public API.

### Public settings exposure (FE gate)

Extend `GET /api/settings/public` with a non-secret slice (mirror `demo.enabled`):

```json
{
  "origin": {
    "enabled": true
  }
}
```

Only when `OriginPanelMode::isActive()`; omitted or `enabled: false` otherwise.

### Admin nav gating (FE)

In `frontend/src/config/adminNavSections.ts`:

- new item e.g. `/platform/origin` with `originOnly: true` (new flag, parallel to `hideOnDemoInstance`),
- filtered in `AdminSidebar.tsx` when `settings.origin?.enabled !== true`.

### Distribution archive exclusion

Document and enforce in release packaging (same tier as Demo):

**Include only on maintainer git checkout; exclude from customer install archive:**

```text
backend/app/Modules/Origin/
backend/app/Http/Routes/origin.php
backend/app/Http/Controllers/Origin/
backend/tests/Modules/Origin/
frontend/src/components/backend/OriginPanelView.tsx
frontend/src/components/origin/
frontend/src/api/origin.ts
frontend/src/i18n/modules/origin/
```

Customer `composer autoload` / route auto-discovery must not reference missing classes (routes file absent → no registration).

### Permissions

| Route prefix | Middleware |
|--------------|------------|
| `/api/admin/origin/*` | `AuthMiddleware` + `TwoFactorMiddleware` + `RoleMiddleware(['SUPER_ADMIN'])` |

No `content:edit` — this is platform maintainer tooling.

---

## 82b — Auto-checked feature checklist

### Principle

**No manual checkbox in admin UI.** Each backlog item maps to a **probe** that inspects real wiring:

- route registered in `Http/Routes/`,
- service bound in DI container,
- settings key in `SettingsSchema`,
- capability probe green,
- optional PHPUnit smoke flag file (e.g. `backend/tests/smoke/probes/it73.json` generated in CI — future).

Human markdown (`ITERATION_BACKLOG.md`) is **documentation only**; the panel shows probe results, not doc checkboxes.

### Probe contract

```php
interface FeatureProbeInterface
{
    public function id(): string;           // e.g. 'it.73.multi_locale'
    public function group(): string;          // e.g. 'content', 'security', 'platform'
    public function labelKey(): string;       // i18n key under origin.probes.*
    public function run(): FeatureProbeResult;
}

final class FeatureProbeResult
{
    public function __construct(
        public readonly string $status,      // 'implemented' | 'partial' | 'missing' | 'unknown'
        public readonly string $message,     // safe, no paths/secrets
        public readonly ?string $since = null // semver when probe first passed (optional)
    ) {}
}
```

Registry: `FeatureProbeRegistry` returns ordered list; new iteration shipped = new probe class registered in `backend/app/Modules/Origin/Config/probes.php`.

### Initial probe set (MVP)

| Probe ID | Verifies (examples) |
|----------|---------------------|
| `it.1.locking` | `LockManager` + `/api/locks/acquire` route |
| `it.58.shortcodes` | shortcode registry non-empty + expander service |
| `it.59.scheduled_publish` | scheduler handler + content `scheduledAt` write path |
| `it.67.untrusted_surfaces` | `CodePolicyEngine` + CSP settings keys |
| `it.71.performance_guard` | APM middleware + breach store |
| `it.73.multi_locale` | `localizedContent` schema + locale API routes |
| `it.74.api_keys` | API key store + headless routes |
| `it.80.redirects` | redirect store + public middleware |
| `it.81.duplicate` | duplicate endpoints on pages/articles |
| `it.81.stale_content` | staleness service + dashboard count |

Partial status is allowed when foundation shipped but remainder documented (same rule as backlog “🟡 partial”).

### API

| Method | Route | Response |
|--------|-------|----------|
| `GET` | `/api/admin/origin/overview` | `{ metrics: {...}, probes: [...], health: {...} }` |
| `GET` | `/api/admin/origin/probes` | probe list only (for lazy load) |

Response must pass through `LogSanitizer` for any dynamic strings.

### Frontend

New view `OriginPanelView.tsx` (route `/platform/origin`):

- **Section A — Ops snapshot:** reuse data from existing dashboard overview + APM (`HealthPanel`, `PerformanceGuardPanel` widgets embedded or imported),
- **Section B — Feature checklist:** table grouped by `group`, status badge (`implemented` green, `partial` amber, `missing` slate),
- **No edit controls** — read-only; link to `docs/en/ITERATION_*.md` anchors optional (external, opens in new tab).

**UI pattern to copy:** `SeoHealthChecklist.tsx` + `EngineSettingsPanel.tsx` capability rows.

---

## 82c — Dashboard metrics lite (optional phase)

Reuse existing infrastructure; do **not** introduce Prometheus/Grafana dependencies.

| Signal | Source today | Origin Panel use |
|--------|--------------|------------------|
| p95 / error rate / breaches | `MetricsController`, `PerformanceSampleStore` | headline cards |
| Visits / pageviews | `AnalyticsReporter` | 14-day bar chart (same as dashboard) |
| Entity counts | `AdminCountsService` | secondary KPI row |
| Comment velocity | `CommentSubmissionVelocityStore` | optional sparkline |
| Locks / conflicts | `DashboardController` | operational row |

**New flat-file series (only if cheap):** e.g. hourly API 5xx counter in `data/metrics/api-errors.json` — ring buffer max 168 buckets (7 days). Written from existing error handler hook; optional for MVP.

**Explicit non-goals for 82c:**

- CPU/RAM host graphs (remain It.46 / external monitoring),
- alerting/on-call paging,
- customer-visible metrics pages.

---

## Security baseline (mandatory)

- AuthN + 2FA + `SUPER_ADMIN` on all `/api/admin/origin/*` routes.
- CSRF on mutating routes (overview is GET-only in MVP — no mutators).
- Probes must not expose `.env`, absolute paths, tokens, or user PII.
- Origin Panel disabled → routes return **404** (not 403 with feature name leakage on customer installs).
- No probe may mutate SSOT; read-only inspection only.

---

## Testing and Definition of Done

### 82a DoD

- [ ] `OriginPanelMode` unit tests (env on/off, host allowlist, fail-closed),
- [ ] boot warning when misconfigured,
- [ ] packaging manifest documented; customer archive build excludes Origin paths,
- [ ] FE nav hidden when `origin.enabled` false.

### 82b DoD

- [ ] ≥10 probes registered; each returns deterministic status in PHPUnit,
- [ ] `GET /api/admin/origin/overview` covered by controller test,
- [ ] FE checklist renders Loading / Success / Error states,
- [ ] manual checkbox **absent** from UI (regression grep in CI optional).

### 82c DoD (if shipped)

- [ ] metrics section uses existing APIs only, or one new flat-file store with lock + schema version,
- [ ] iteration gate green.

---

## Recommended implementation order

```text
82a (gate + packaging + empty shell route)
  → 82b (probes + checklist UI)
  → 82c (metrics polish, optional)
```

Target: single beta release **`v2.1.0-beta.55+`** after It.81f or It.78 gate — timing flexible; no user-facing blocker.

---

## Non-goals

- Grafana/Prometheus integration inside CMS,
- Manual backlog editing or “mark done” buttons in admin,
- Shipping Origin Panel to customer install ZIP/tarball,
- Enabling on demo instance,
- Replacing `docs/en/CHECKLIST.md` (docs remain canonical for release gates; Origin Panel is runtime mirror).

---

## Related documents

- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) — active priority table  
- [ITERATION_13.md](ITERATION_13.md) — Demo module (packaging precedent)  
- [ITERATION_46.md](ITERATION_46.md) — host metrics agent (optional 82d)  
- [ITERATION_71.md](ITERATION_71.md) — Performance Guard (metrics reuse)  
- [DEMO_DEPLOY.md](../deploy/DEMO_DEPLOY.md) — isolated deployment model  
- [CHECKLIST.md](CHECKLIST.md) — human release gate (complementary)
