# PaginiumCMS

> **Verzia:** 2.0.58 · **Posledná aktualizácia:** júl 2026  
> **Wave 5f:** Docker + first-run onboarding · **Wave 6:** Beta infra gate

Headless flat-file CMS — PHP 8.5 backend (Slim 4) + React admin SPA (Vite 8).

**Filozofia:** 100 % open source, bez poplatkov — [docs/PHILOSOPHY.md](docs/PHILOSOPHY.md)

**Kompletná dokumentácia:** [`docs/README.md`](docs/README.md) · **Beta tester:** [`docs/user/README.md`](docs/user/README.md)

---

## Rýchly štart (odporúčané)

Nový clone → pripravený admin → API bez 500:

```bash
git clone <repo> paginiumcms && cd paginiumcms
chmod +x scripts/first-run.sh
./scripts/first-run.sh
docker compose up -d
curl -s http://localhost:8080/api/health
```

**Predvolený admin** (len pri prázdnom `data/users/`):

| Pole | Hodnota |
|------|---------|
| Email | `admin@localhost` |
| Heslo | `Admin123!ChangeMe` |

Vlastné údaje pred `first-run`:

```bash
export FIRST_ADMIN_EMAIL=you@example.com
export FIRST_ADMIN_PASSWORD='YourStr0ngPass!'
export FIRST_ADMIN_NAME='Your Name'
./scripts/first-run.sh
```

**Frontend dev** (Vite proxy `/api` → `:8080`):

```bash
INSTALL_FRONTEND=1 ./scripts/first-run.sh   # voliteľné npm ci
docker compose --profile dev up -d          # alebo: cd frontend && npm run dev
# → http://localhost:3025
```

Detail: [docs/developer/LOCAL_SETUP.md](docs/developer/LOCAL_SETUP.md) · [docs/user/INSTALLATION.md](docs/user/INSTALLATION.md) · [docs/user/FIRST_STEPS.md](docs/user/FIRST_STEPS.md)

---

## Klasický vývoj (bez Dockeru)

```bash
./scripts/first-run.sh
cd backend/public && php -S localhost:8080    # API :8080
cd frontend && npm install && npm run dev     # SPA :3025
```

Quality gate:

```bash
composer test && composer stan
cd frontend && npm run type-check && npm run lint && npm run lint:api-barrel && npm test
```

---

## Aktuálny stav (2.0.58)

| Oblasť | Stav |
|--------|------|
| Backend API | ✅ Slim 4, auto-discovery, JsonResponder, PHPStan L8 |
| Auth + 2FA + RBAC + password confirm | ✅ 2.0.48–56 |
| Admin + public i18n (SK/EN) | ✅ 2.0.47–50 |
| Scheduled publish (It.59) | ✅ 2.0.53 · potrebuje cron `scheduler:run` |
| External plugins + hook emitters | ✅ 2.0.38, 2.0.54 |
| Docker + `first-run.sh` | ✅ Wave **5f** |
| Beta infra docs + cron | ✅ Wave **6** |
| Public Beta 1 | ⏳ Wave **7** ([BETA_INFRA.md](docs/developer/BETA_INFRA.md)) |

### Ďalší krok

**Wave 6** — Beta infra checklist (cron, gate, security baseline) → release **2.0.58**

**Wave 7** — Public Beta 1 → `v2.1.0-beta.1` / **2.0.59**

Detail: [docs/CONTINUATION.md](docs/CONTINUATION.md) · [docs/ROADMAP.md](docs/ROADMAP.md)

---

## Kľúčové dokumenty

| Pre koho | Dokument |
|----------|----------|
| Beta tester / admin | [docs/user/README.md](docs/user/README.md) |
| Inštalácia | [docs/user/INSTALLATION.md](docs/user/INSTALLATION.md) |
| Vývojár (Docker) | [docs/developer/LOCAL_SETUP.md](docs/developer/LOCAL_SETUP.md) |
| Prispievanie | [docs/developer/CONTRIBUTING.md](docs/developer/CONTRIBUTING.md) |
| API kontrakt | [docs/architecture/API_CONTRACT.md](docs/architecture/API_CONTRACT.md) |
| Release / C&P | [docs/developer/RELEASE.md](docs/developer/RELEASE.md) |
| Beta infra (Wave 6) | [docs/developer/BETA_INFRA.md](docs/developer/BETA_INFRA.md) |
| Produkcia cron | [docs/deploy/CRON.md](docs/deploy/CRON.md) |
| Beta tester checklist | [docs/user/README.md](docs/user/README.md) |
| Changelog | [CHANGELOG.md](CHANGELOG.md) |
