# PaginiumCMS

> **Verzia:** 2.0.26 · **Posledná aktualizácia:** júl 2026  
> **Unreleased:** It.41–43 (OTP, sidebar counts, advanced search) — [CHANGELOG](CHANGELOG.md#unreleased)

Headless flat-file CMS — PHP 8.5 backend (Slim 4) + React admin SPA.

**Kompletná dokumentácia:** [`docs/README.md`](docs/README.md)

## Rýchly štart

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/phpstan analyse backend --level=8

# Backend (port 8080)
cd backend/public && php -S localhost:8080

# Frontend (port 3025, proxy /api + /storage + /feed.xml + /sitemap.xml)
cd frontend && npm install && npm run dev

# Frontend bez backendu (MSW mocks)
cd frontend && VITE_MSW=true npm run dev
```

## Aktuálny stav (2.0.26)

| Oblasť | Stav |
|--------|------|
| Backend API | ✅ Slim 4, auto-discovery routes, JsonResponder, PHPStan L8 |
| Auth + 2FA + RBAC | ✅ Session, TOTP, `PermissionMiddleware` |
| Content + index | ✅ It.19–20 — pagination, search, trash, published filter |
| Media (FE + DAM) | ✅ It.8 + It.24 — `MediaManager`, picker, WYSIWYG, folders |
| SEO + feeds | ✅ It.23 SEO meta (2.0.11) · 🟡 It.10 RSS/sitemap (BE hotové, polish pred It.11) |
| Scheduler + monitoring | ✅ It.7, It.29 — cron planner, reports, `/scheduler` |
| WAF + structured logs | ✅ It.50 — 2.0.26 |
| PHPUnit | ✅ **607 testov** (15 skipped) |
| Frontend | ✅ Admin SPA, public site, Monaco Code Editor, Ctrl+K search (Unreleased) |

### Ďalší krok

1. **It.10 polish** — cache feedov, `robots.txt`, sitemap v `<head>`, Newman smoke  
2. **It.11** — SSO + jemnozrnné ACL + audit log (až po It.10)

Detail: [docs/ROADMAP.md](docs/ROADMAP.md) · [docs/CONTINUATION.md](docs/CONTINUATION.md)

## Kľúčové dokumenty

- [Architektúra & stav projektu](docs/README.md)
- [API kontrakt (JSON obaly)](docs/architecture/API_CONTRACT.md)
- [Roadmap](docs/ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Content API](docs/architecture/CONTENT_API.md)
- [Core hardening](docs/architecture/CORE_HARDENING.md)
- [Iterácie 8 / 10 / 11](docs/ITERATION_8.md) · [It.10 feeds](docs/ITERATION_10.md) · [It.11 SSO](docs/ITERATION_11.md)
- [Lokálny vývoj](docs/deploy/DEV.md)
- [Testovanie](docs/developer/TESTING.md)
