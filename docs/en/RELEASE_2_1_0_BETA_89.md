# Release `v2.1.0-beta.89` — It.48 static HTML + secret scan + desk role gate

> **Date:** 2026-09-20  
> **Tag:** `v2.1.0-beta.89`  
> **Type:** feature + security  
> **Previous:** [`v2.1.0-beta.88`](../../CHANGELOG.md#release-2-1-0-beta-88) — It.75 assistant · visitor polish · Origin snapshot

---

## One-line summary

Compile published pages/articles to derived HTML, optionally serve it at `/static-html`, and close the public deploy-key leak follow-up (gitleaks) plus a USER-role hole on the admin message desk.

---

## What shipped

| Area | Change |
|------|--------|
| **It.48a** | `engine.renderMode` (`dynamic` default / `hybrid` / `static`). Compiler writes `storage/app/static/{pages\|blog}/{slug}/index.html` (never `.php`). Job `static.rebuild`. Admin `GET/POST /api/admin/static/*` needs `static:rebuild`. Auto-compile after save in hybrid/static. Git publish queue unchanged. |
| **It.48b** | Anonymous `GET /static-html/pages/{slug}` and `/static-html/blog/{slug}` when mode is hybrid/static. 404 in dynamic, on reserved slugs (`login`, `dashboard`, `api`, …), or when the file is missing. Strict CSP without `script-src`. `/storage` still does not serve the compiled tree. Optional nginx snippet maps pretty URLs and falls back to the SPA. |
| **ISS-173** | CI gitleaks (8.30.1, checksum-pinned) + local `scripts/secret-scan.sh` in the iteration gate. Deploy key was already rotated and history rewritten. |
| **ISS-174** | `/api/admin/messages` is EDITOR+. USER / external-team cannot probe the desk API. |
| **AppVersion** | Never report a git tag older than the `VERSION` constant (rewrite can still see `v2.0.1`). |

Specs: [ITERATION_48.md](ITERATION_48.md) · [ISSUES.md](../ISSUES.md#iss-173).

---

## Operator notes

- **Classic/dynamic is unchanged.** Public site stays the React SPA until you set `engine.renderMode` to `hybrid` or `static` **and** include `docs/deploy/nginx-static-html.conf`. Do not enable the snippet in Classic — every public GET would take an extra PHP 404.
- **Rebuild** from Settings → Engine. Markdown/JSON stay SSOT. Save, Build, and Git publish stay separate.
- **Derived layers (unchanged contract):** SQLite query index (It.92) is optional catalog acceleration. Redis remains **cache only** and is **not installed** — `cacheDriver=redis` falls back to `auto`.
- **Deploy this tag**, not `v2.1.0-beta.88`, if you want It.48 and the secret-scan gate.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.89 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.89`.

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.89 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] Settings → Engine: `dynamic` = `/static-html/pages/{slug}` is 404 even after a rebuild file exists.
- [ ] `hybrid` + rebuild: `GET /static-html/pages/home` is HTML; `/static-html/pages/login` is 404; `/storage/app/static/...` is 404.
- [ ] `/api/admin/messages` as a plain USER is 403.
- [ ] `./scripts/secret-scan.sh` exits 0; CI has the gitleaks job.
- [ ] Origin Panel snapshot shows `2.1.0-beta.89`; It.48 is live; next is 58f-h / It.95 / 93l-2 / Redis cache driver.
- [ ] `GET /api/health` reports `2.1.0-beta.89`.
