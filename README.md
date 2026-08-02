# PaginiumCMS

> **Final consolidated edition — 2026-08-02.** This tree contains the complete English documentation, historical iterations, the Hybrid Engine design, and the latest security changes including ISS-120. [Open the complete navigation](docs/NAVIGATION.md).
> **Slovenská dokumentácia:** [docs/sk/NAVIGATION.md](docs/sk/NAVIGATION.md)

> **Version:** 2.1.0-beta.23 · **Public Beta 1** · August 2026  
> **Direction:** Hybrid Headless Content Engine · No-SQL file source of truth · API-first

PaginiumCMS is an open-source **Hybrid Headless Content Engine** built with PHP 8.5, Slim 4, and a React administration SPA powered by Vite 8.

The project retains the defining property of a flat-file CMS: content, configuration, and operational state remain in files. It adds professional layers for indexing, caching, versioning, Git-based distribution, multilingual content, and future AI-assisted workflows.

> **Immutable rule:** neither SQL nor an external document database may replace files as the primary CMS source of truth. Redis, APCu, and similar technologies may be used only as derived cache or temporary coordination layers.

**Architecture:** [docs/architecture/HYBRID_ENGINE.md](docs/architecture/HYBRID_ENGINE.md) · **Philosophy:** [docs/PHILOSOPHY.md](docs/PHILOSOPHY.md) · **No-SQL mandate:** [docs/architecture/NOSQL_MANDATE.md](docs/architecture/NOSQL_MANDATE.md)

**Full documentation:** [`docs/README.md`](docs/README.md) · **Public Beta 1:** [`docs/PUBLIC_BETA1.md`](docs/PUBLIC_BETA1.md) · **Tester guide:** [`docs/user/BETA_TESTER.md`](docs/user/BETA_TESTER.md) · **Security review:** [`docs/SECURITY_REVIEW.md`](docs/SECURITY_REVIEW.md)

---

## Quick start

Fresh clone → initial administrator → working API:

```bash
git clone <repo> paginiumcms && cd paginiumcms
chmod +x scripts/first-run.sh
./scripts/first-run.sh
docker compose up -d
curl -s http://localhost:8080/api/health
```

The **default administrator** is created only when `data/users/` is empty:

| Field | Value |
|-------|-------|
| Email | `admin@localhost` |
| Password | `Admin123!ChangeMe` |

Set custom credentials before running `first-run.sh`:

```bash
export FIRST_ADMIN_EMAIL=you@example.com
export FIRST_ADMIN_PASSWORD='YourStr0ngPass!'
export FIRST_ADMIN_NAME='Your Name'
./scripts/first-run.sh
```

**Frontend development** — the Vite proxy forwards `/api` to port `8080`:

```bash
INSTALL_FRONTEND=1 ./scripts/first-run.sh   # optional npm ci
docker compose --profile dev up -d          # or: cd frontend && npm run dev
# → http://localhost:3025
```

Details: [docs/developer/LOCAL_SETUP.md](docs/developer/LOCAL_SETUP.md) · [docs/user/INSTALLATION.md](docs/user/INSTALLATION.md) · [docs/user/FIRST_STEPS.md](docs/user/FIRST_STEPS.md)

---

## Classic development without Docker

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

## Current status

| Area | Status |
|------|--------|
| Backend API | ✅ Slim 4, route auto-discovery, `JsonResponder`, PHPStan L8 |
| Authentication and security | ✅ Session, CSRF, 2FA, RBAC, password confirmation, WAF |
| Administration and public site | ✅ React SPA, SK/EN i18n, content, media, navigation, newsletter |
| File source of truth | ✅ JSON / Markdown / YAML, index, locks, OCC, and versioning |
| Public Beta 1 | ✅ releases through `v2.1.0-beta.23` |
| Hybrid Engine — documentation Phase 0 | ✅ target architecture and direction defined |
| Hybrid Engine — implementation | ⏸️ paused until the bilingual documentation pass is complete |
| Next implementation | ⏳ It.68 — storage abstraction and engine settings |

### Target model

1. **Files on disk** remain the only source of truth.
2. **Indexes and caches** accelerate reads but must be rebuildable from files.
3. The **REST API** provides validation, authorization, and a consistent contract.
4. **Git distribution** may publish content immediately or in batches.
5. **Deployment modes** allow the same core to operate as a classic CMS, hybrid website, or Git-headless/Jamstack engine.

---

## Key documents

| Audience / area | Document |
|-----------------|----------|
| Vision and immutable principles | [docs/PHILOSOPHY.md](docs/PHILOSOPHY.md) |
| Target architecture | [docs/architecture/HYBRID_ENGINE.md](docs/architecture/HYBRID_ENGINE.md) |
| No-SQL rule | [docs/architecture/NOSQL_MANDATE.md](docs/architecture/NOSQL_MANDATE.md) |
| Deployment modes | [docs/architecture/DEPLOYMENT_MODES.md](docs/architecture/DEPLOYMENT_MODES.md) |
| Beta tester / administrator | [docs/user/README.md](docs/user/README.md) |
| Installation | [docs/user/INSTALLATION.md](docs/user/INSTALLATION.md) |
| Local development | [docs/developer/LOCAL_SETUP.md](docs/developer/LOCAL_SETUP.md) |
| Contributing | [docs/developer/CONTRIBUTING.md](docs/developer/CONTRIBUTING.md) |
| API contract | [docs/architecture/API_CONTRACT.md](docs/architecture/API_CONTRACT.md) |
| Releases | [docs/developer/RELEASE.md](docs/developer/RELEASE.md) |
| Production cron | [docs/deploy/CRON.md](docs/deploy/CRON.md) |
| Change history | [CHANGELOG.md](CHANGELOG.md) |

---

> **Documentation First:** when code and documentation diverge, document the actual state precisely first, then make the next code change deliberately close the gap.
