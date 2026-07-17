# PaginiumCMS

> **Verzia:** 2.0.9 · **Posledná aktualizácia:** júl 2026

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

# Frontend bez backendu (MSW mocks)
cd frontend && VITE_MSW=true npm run dev
```

## Aktuálny stav (2.0.9)

| Oblasť | Stav |
|--------|------|
| Backend API | ✅ Slim 4, JsonResponder, PHPStan L8 |
| Auth + 2FA + RBAC | ✅ Session, TOTP, PermissionMiddleware |
| Content index + pagination | ✅ It. 19 |
| Core hardening | ✅ It. 20 |
| API contract | 🟡 It. 21 — API_CONTRACT.md, MSW, Postman smoke |
| PHPUnit | ✅ **502 testov** |
| Frontend | MSW mocks, typed `content.ts` API |

## Kľúčové dokumenty

- [Architektúra & stav projektu](docs/README.md)
- [API kontrakt (JSON obaly)](docs/architecture/API_CONTRACT.md)
- [Roadmap](docs/ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Content API](docs/architecture/CONTENT_API.md)
- [Core hardening](docs/architecture/CORE_HARDENING.md)
- [Lokálny vývoj](docs/deploy/DEV.md)
- [Testovanie](docs/developer/TESTING.md)
