# PaginiumCMS

> **Verzia:** 2.0.8 · **Posledná aktualizácia:** júl 2026

Headless flat-file CMS — PHP 8.5 backend (Slim 4) + React admin SPA.

**Kompletná dokumentácia:** [`docs/README.md`](docs/README.md)

## Rýchly štart

```bash
composer install
./vendor/bin/phpunit

# Backend (port 8080)
cd backend/public && php -S localhost:8080

# Frontend (port 3025, proxy /api + /storage)
cd frontend && npm install && npm run dev
```

## Aktuálny stav (2.0.8)

| Oblasť | Stav |
|--------|------|
| Backend API | ✅ Slim 4, auto-discovery routes, PHPStan L8 |
| Auth + 2FA + RBAC | ✅ Session, TOTP, `PermissionMiddleware` na mutáciách |
| Content index + pagination | ✅ It. 19 — index, search, stránkovanie |
| Core hardening | ✅ It. 20 — maintenance, trash, `/storage`, backup cron |
| PHPUnit | ✅ **488 testov**, 1223 assertions |
| Frontend | 🟡 Admin + verejný web; It. 21 (API kontrakt, MSW) ďalej |

## Kľúčové dokumenty

- [Architektúra & stav projektu](docs/README.md)
- [Roadmap](docs/ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Content API (pagination/search)](docs/architecture/CONTENT_API.md)
- [Core hardening (RBAC, maintenance, trash)](docs/architecture/CORE_HARDENING.md)
- [Lokálny vývoj](docs/deploy/DEV.md)
- [Testovanie](docs/developer/TESTING.md)
