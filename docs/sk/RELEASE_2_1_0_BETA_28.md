# Release `v2.1.0-beta.28` — Performance Guard a UX polish

> **Dátum:** 2026-08-06  
> **Tag:** `v2.1.0-beta.28`  
> **Predchádzajúci:** [`v2.1.0-beta.27`](../../CHANGELOG.md#release-2-1-0-beta-27)  
> **Changelog:** [CHANGELOG.md](../../CHANGELOG.md#release-2-1-0-beta-28)  
> **Incidenty:** [ISSUES.md](../ISSUES.md) ISS-121–ISS-133

## Zhrnutie

Tento release doručuje **Iteráciu 71 (Performance Guard / APM)**, **UX polish (Fázy A–C)** a **CI/incident balík po beta.27**, ktorý sa nahromadil na `main`.

**Iterácia 72** (media storage drivers) v tomto release **nie je** — zostáva ďalší míľnik.

---

## Rozsah

| Smer | Obsah | Doc |
|------|-------|-----|
| **It.71** | APM v requeste, Engine nastavenia, dashboard panel, API `metrics:read` | [ITERATION_71.md](../en/ITERATION_71.md) |
| **UX Fáza A** | Verzia CMS vo footeri, back-to-top (verejný web + admin), SEO checklist | [ITERATION_UX_POLISH.md](../en/ITERATION_UX_POLISH.md) |
| **UX Fáza B** | Grafy vo všetkých analytics taboch | [ITERATION_UX_POLISH.md](../en/ITERATION_UX_POLISH.md) |
| **UX Fáza C** | Newsletter bulk odhlásenie/vymazanie | [ITERATION_UX_POLISH.md](../en/ITERATION_UX_POLISH.md) |
| **CI / incidenty** | ISS-121–133 | [ISSUES.md](../ISSUES.md) |

---

## Register incidentov (tento release)

| ID | Príznak | Závažnosť |
|----|---------|-----------|
| ISS-121–123 | It.68 validácia úložiska a test hygiene | Medium/Low |
| ISS-125–126 | Post-beta.27 CI (DEMO_MODE, storage path, API barrel, demo quota) | Medium |
| ISS-127–128 | It.71 DI + ukladanie Engine nastavení | High/Medium |
| ISS-129 | FileDriver warning na read-only cache | Low |
| ISS-130–131 | Newsletter light mode + PHPCS CVE | Medium/High |
| ISS-132–133 | CI: TS import path + Vitest I18nProvider | Low |

Detailné popisy: [ISSUES.md](../ISSUES.md).

---

## Deploy

```bash
export GIT_REF=v2.1.0-beta.28
# skript aktualizácie inštancie podľa PRIVATE_DOMAIN_DEPLOY.md
cd frontend && npm ci && npm run build:prod
```

### Po deployi

1. Verzia `2.1.0-beta.28` vo footeri / health.
2. Voliteľne zapnúť **Performance Guard** v Nastavenia → Engine.
3. Skontrolovať `metrics:read` v ACL pre ADMIN ([ISS-127](../ISSUES.md#iss-127)).
4. Smoke: analytics grafy, newsletter bulk, back-to-top.

---

## Ďalej

**It.72** — ovládače médiového úložiska (Flysystem / S3).
