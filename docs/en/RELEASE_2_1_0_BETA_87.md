# Release `v2.1.0-beta.87` — It.70 GitHub API + It.76/77 assisted translation

> **Date:** 2026-09-19  
> **Tag:** `v2.1.0-beta.87`  
> **Type:** feature  
> **Previous:** [`v2.1.0-beta.86`](../../CHANGELOG.md#release-2-1-0-beta-86) — It.93o desk/staff · It.96 documents · remaining beta.85 CI

---

## One-line summary

Git as distribution via the GitHub API (no local clone required), plus assisted translation: LibreTranslate on your own instance, or DeepL/Google cloud keys — always a human Apply as draft, never auto-publish.

---

## What shipped

| Area | Change |
|------|--------|
| **It.70** | `GitHubApiPublisher` — Git Data API blob/tree/commit/PATCH ref. Settings `gitPublisher=github_api`, encrypted `gitGithubToken`, `owner/name`. Engine **Publish release** panel. Content-list `pending_publish` badge. Local git publisher unchanged. SSOT stays on disk. |
| **It.76** | Shared `TranslationProviderInterface`, LibreTranslate driver, proposal store, daily quota, placeholder guard. Settings → Translation (default off). Editor **Translate missing**. Apply = It.73 draft + OCC. Routes under `/api/admin/content-translations/*` (not the It.18d catalog). **Own LibreTranslate instance required** — CMS does not host it. |
| **It.77** | `deepl` + `google` on the same UI. Fixed vendor hosts only. Encrypted write-only keys. Explicit failover (unavailable/429 only, never AUTH_FAILED). No price/free-tier promise. |
| **CI hygiene** | `security-static-grep` allow-lists `GitHubApiClient` and `TranslationHttpClient`. PHPStan L8 on GitHub curl options / JSON decode. |

Specs: [ITERATION_70.md](ITERATION_70.md) · [ITERATION_76.md](ITERATION_76.md) · [ITERATION_77.md](ITERATION_77.md).

---

## Operator notes

- **GitHub API publish:** Settings → Hybrid Engine → publisher `github_api`, repository `owner/name`, token with `contents:write`. Then **Publish release**. Failure does not roll back the CMS write.
- **LibreTranslate:** run your own (or compatible) HTTP instance and set its base URL. Hosted LibreTranslate.com is a third-party paid API, not included.
- **DeepL / Google:** vendor API key only. DeepL keys ending in `:fx` use `api-free.deepl.com`. Google uses Cloud Translation v2 on `translation.googleapis.com`. Selected title/body/SEO fields leave the server.
- **Apply** stores a **draft**. Publish remains a separate human action.
- Disabled translation = zero outbound traffic.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.87 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.87`.

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.87 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] Settings → Engine: Publish release panel loads; queued paths preview; no token leaked in the UI.
- [ ] Settings → Translation: off = no outbound; LibreTranslate help mentions own instance; DeepL/Google fields are write-only after save.
- [ ] Editor (saved page, SK+EN): Translate missing → review → Apply as draft; public page unchanged until publish.
- [ ] `GET /api/health` reports `2.1.0-beta.87`.
