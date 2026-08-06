# Release `v2.1.0-beta.28` — Performance Guard and UX polish

> **Date:** 2026-08-06  
> **Tag:** `v2.1.0-beta.28`  
> **Previous:** [`v2.1.0-beta.27`](../../CHANGELOG.md#release-2-1-0-beta-27)  
> **Canonical changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-28)  
> **Incidents:** [ISSUES.md](../ISSUES.md) ISS-121–ISS-133

## Summary

This release ships **Iteration 71 (Performance Guard / APM)**, the **UX polish batch (Phases A–C)**, and the **post-beta.27 CI/incident bundle** that accumulated on `main` between beta.27 and this tag.

It does **not** include **Iteration 72** (media storage drivers) — that remains the next planned milestone.

---

## Scope

| Track | Content | Doc |
|-------|---------|-----|
| **It.71** | In-request APM, Engine settings, dashboard panel, `metrics:read` API | [ITERATION_71.md](ITERATION_71.md) |
| **UX Phase A** | Footer CMS version, back-to-top (public + admin), SEO health checklist | [ITERATION_UX_POLISH.md](ITERATION_UX_POLISH.md) |
| **UX Phase B** | Analytics charts on all admin tabs | [ITERATION_UX_POLISH.md](ITERATION_UX_POLISH.md) |
| **UX Phase C** | Newsletter bulk unsubscribe/delete | [ITERATION_UX_POLISH.md](ITERATION_UX_POLISH.md) |
| **CI / incidents** | ISS-121–133 (see below) | [ISSUES.md](../ISSUES.md) |

---

## Incident register (this release)

| ID | Symptom | Severity | Fix in beta.28 |
|----|---------|----------|----------------|
| [ISS-121](../ISSUES.md#iss-121) | Invalid settings group shapes silently dropped | Medium | It.68 fail-closed validation |
| [ISS-122](../ISSUES.md#iss-122) | Storage read path symlink escape | Medium | `LocalFlatFileStorage` containment |
| [ISS-123](../ISSUES.md#iss-123) | Corrupt settings leaked between HTTP tests | Low | `Http/TestCase` reset |
| [ISS-125](../ISSUES.md#iss-125) | DEMO_MODE leaked after demo PHPUnit (4 failures) | Medium | `phpunit.xml` + test hygiene |
| [ISS-126](../ISSUES.md#iss-126) | Post-beta.27 CI bundle (storage path, API barrel, demo quota) | Medium | hotfix commits on `main` |
| [ISS-127](../ISSUES.md#iss-127) | It.71 DI incomplete — 233 PHPUnit errors | High | explicit PHP-DI constructors |
| [ISS-128](../ISSUES.md#iss-128) | Performance Guard settings could not save | Medium | float field + numeric Zod |
| [ISS-129](../ISSUES.md#iss-129) | FileDriver PHP warning on read-only cache dir | Low | writable guard |
| [ISS-130](../ISSUES.md#iss-130) | Newsletter unreadable in light mode | Medium | theme token fix |
| [ISS-131](../ISSUES.md#iss-131) | PHPCS CVE-2026-67434 (dev dep) | High | PHPCS 4.0.4 |
| [ISS-132](../ISSUES.md#iss-132) | `analyticsChartData` TS2307 import path | Low (CI) | `../../../api/analytics` |
| [ISS-133](../ISSUES.md#iss-133) | BackToTopButton Vitest missing I18nProvider | Low (CI) | `renderWithProviders` |

---

## Deploy

```bash
# From PRIVATE_DOMAIN_DEPLOY.md pattern — use the tag, not a moving branch
export GIT_REF=v2.1.0-beta.28
# run your instance update script, then:
cd frontend && npm ci && npm run build:prod
```

### Post-deploy checklist

1. **Version** — public footer and health/version endpoints report `2.1.0-beta.28`.
2. **Performance Guard (optional)** — Settings → Engine → enable **Performance Guard** → Save ([ISS-128](ISS-128) regression).
3. **ACL** — if ADMIN uses saved `permissionsAdmin`, ensure `metrics:read` is present for APM API ([ISS-127](ISS-127)).
4. **Smoke** — Analytics charts load; Newsletter bulk actions; back-to-top on long admin pages and public site.
5. **Dev/CI only** — `composer audit` clean after PHPCS bump ([ISS-131](ISS-131)).

---

## Verification

```bash
./scripts/iteration-gate.sh
git describe --tags --exact-match   # after checkout v2.1.0-beta.28
php -r "require 'vendor/autoload.php'; echo PaginiumCMS\Support\AppVersion::VERSION;"
```

---

## Related

- [ROADMAP.md](ROADMAP.md) · [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md)  
- Next target: **It.72** media storage drivers
