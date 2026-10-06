# PaginiumCMS

> **Documentation index:** [docs/NAVIGATION.md](docs/NAVIGATION.md) · **Slovak:** [docs/sk/NAVIGATION.md](docs/sk/NAVIGATION.md)  
> **Version:** **`v2.1.0-beta.96`** · **Public Beta** · October 2026  
> **Direction:** Hybrid Headless Content Engine · flat-file source of truth · API-first

PaginiumCMS is an open-source **Hybrid Headless Content Engine** built with PHP 8.5, Slim 4, and a React administration SPA (Vite 8).

Content, configuration, and operational state stay in **files** (JSON, Markdown, YAML). Derived layers — optional SQLite query index, Redis cache, Git publish — accelerate reads and distribution but must remain rebuildable from files.

> **Immutable rule:** neither SQL nor an external document database may replace files as the primary CMS source of truth.

**Architecture:** [docs/architecture/HYBRID_ENGINE.md](docs/architecture/HYBRID_ENGINE.md) · **Philosophy:** [docs/PHILOSOPHY.md](docs/PHILOSOPHY.md) · **No-SQL mandate:** [docs/architecture/NOSQL_MANDATE.md](docs/architecture/NOSQL_MANDATE.md)

**Handoff:** [docs/en/CONTINUATION.md](docs/en/CONTINUATION.md) · **Releases:** [CHANGELOG.md](CHANGELOG.md) · **Latest notes:** [docs/en/RELEASE_2_1_0_BETA_96.md](docs/en/RELEASE_2_1_0_BETA_96.md)

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

Details: [docs/en/developer/LOCAL_SETUP.md](docs/en/developer/LOCAL_SETUP.md) · [docs/en/user/INSTALLATION.md](docs/en/user/INSTALLATION.md) · [docs/en/user/FIRST_STEPS.md](docs/en/user/FIRST_STEPS.md)

---

## Classic development without Docker

```bash
./scripts/first-run.sh
cd backend/public && php -S localhost:8080    # API :8080
cd frontend && npm install && npm run dev     # SPA :3025
```

Quality gate (required before commit/push):

```bash
./scripts/iteration-gate.sh
```

---

## Current status (October 2026)

| Area | Status |
|------|--------|
| Backend API | ✅ Slim 4, route auto-discovery, `JsonResponder`, PHPStan L8 |
| Authentication and security | ✅ Session, CSRF, 2FA, RBAC, WAF, upload policy, extension Code Policy |
| Administration and public site | ✅ React SPA, SK/EN i18n, content, media, desk, newsletter |
| File source of truth | ✅ JSON / Markdown / YAML, index, locks, OCC, versioning |
| Hybrid Engine **It.68–77** | ✅ Core shipped (storage, cache/Redis, Git publish, APM, S3 media, locales, API keys, AI propose/apply, translation) — verify probes in Origin Panel |
| **It.58** composer wave | ✅ React shortcode islands, section-band, pricing/stats motion, unified lightbox — [IT_58_COMPOSER_HANDOFF.md](docs/en/architecture/IT_58_COMPOSER_HANDOFF.md) |
| **Latest tag** | ✅ **`v2.1.0-beta.96`** — Experience B1–B2 motion (`visual-frame`, section-band v3); gallery wave in **beta.95** |
| **Next slices** | ⏳ Experience Phase C (reference landing seeds) · **It.98** workspace + CLI · optional B3 (reading progress, View Transitions) |

Public beta installs should use the **latest** `v2.1.0-beta.*` tag from [CHANGELOG.md](CHANGELOG.md), not an old beta number.

---

## Key documents

| Audience / area | Document |
|-----------------|----------|
| Vision and principles | [docs/PHILOSOPHY.md](docs/PHILOSOPHY.md) |
| Target architecture | [docs/architecture/HYBRID_ENGINE.md](docs/architecture/HYBRID_ENGINE.md) |
| Shortcodes (operators) | [docs/en/user/SHORTCODE_COOKBOOK.md](docs/en/user/SHORTCODE_COOKBOOK.md) |
| Beta tester / administrator | [docs/en/user/README.md](docs/en/user/README.md) |
| Local development | [docs/en/developer/LOCAL_SETUP.md](docs/en/developer/LOCAL_SETUP.md) |
| Contributing | [docs/en/developer/CONTRIBUTING.md](docs/en/developer/CONTRIBUTING.md) |
| API contract | [docs/architecture/API_CONTRACT.md](docs/architecture/API_CONTRACT.md) |
| Releases | [docs/en/developer/RELEASE.md](docs/en/developer/RELEASE.md) |
| Production cron | [docs/deploy/CRON.md](docs/deploy/CRON.md) |
| Change history | [CHANGELOG.md](CHANGELOG.md) |
| Known incidents | [docs/ISSUES.md](docs/ISSUES.md) |

---

> **Documentation First:** when code and documentation diverge, document the actual state precisely first, then close the gap in the next deliberate code change.
