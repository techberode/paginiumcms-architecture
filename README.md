# PaginiumCMS

> **Verzia:** 2.0.43 · **Posledná aktualizácia:** júl 2026  
> **Posledná iterácia:** [It.55](docs/ITERATION_55.md) — Tiptap JSON + upload

Headless flat-file CMS — PHP 8.5 backend (Slim 4) + React admin SPA.

**Filozofia:** 100 % open source, bez poplatkov, nikdy ako platené riesenie — [docs/PHILOSOPHY.md](docs/PHILOSOPHY.md)

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

## Aktuálny stav (2.0.43)

| Oblasť | Stav |
|--------|------|
| Backend API | ✅ Slim 4, auto-discovery routes, JsonResponder, PHPStan L8 |
| Auth + 2FA + RBAC | ✅ ISS-042 login retry v 2.0.43 |
| Content + index | ✅ It.19–20 — pagination, search, trash, published filter |
| Editor | ✅ It.54 profiles · **It.55 Tiptap JSON + upload** |
| Media (FE + DAM) | ✅ It.8 + It.24 — `MediaManager`, picker, WYSIWYG, folders |
| SEO + feeds | ✅ It.23 SEO meta (2.0.11) · It.10 RSS/sitemap/robots |
| Scheduler + monitoring | ✅ It.7, It.29 — cron planner, reports, `/scheduler` |
| WAF + structured logs | ✅ It.50 — 2.0.26 |
| SSO + path ACL + security audit | ✅ It.11 — **2.0.27** |
| Blueprint / schema engine | ✅ It.12 — **2.0.28** |
| Demo sandbox (demo.paginiumcms.com) | ✅ It.13 v2 — **2.0.28**, nie zákaznícky balík |
| Advanced search + OTP workflows | ✅ It.41–43 — **2.0.27** |
| PHPUnit | ✅ editor + content pipeline tests |
| Frontend | ✅ Admin SPA, WYSIWYG JSON storage, public site, Ctrl+K search |

### Ďalší krok

**It.56** — Rich navigation menu items ([ITERATION_56.md](docs/ITERATION_56.md))

Detail: [docs/ROADMAP.md](docs/ROADMAP.md) · [docs/CONTINUATION.md](docs/CONTINUATION.md)

## Kľúčové dokumenty

- [Architektúra & stav projektu](docs/README.md)
- **[Filozofia & dôvod vzniku](docs/PHILOSOPHY.md)**
- [API kontrakt (JSON obaly)](docs/architecture/API_CONTRACT.md)
- [Roadmap](docs/ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Content API](docs/architecture/CONTENT_API.md)
- [Core hardening](docs/architecture/CORE_HARDENING.md)
- [Iterácie 8 / 10 / 11](docs/ITERATION_8.md) · [It.10 feeds](docs/ITERATION_10.md) · [It.11 SSO](docs/ITERATION_11.md)
- [Lokálny vývoj](docs/deploy/DEV.md)
- [Testovanie](docs/developer/TESTING.md)
