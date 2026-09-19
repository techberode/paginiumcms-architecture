# Release `v2.1.0-beta.88` — It.75 assistant + visitor polish + Origin snapshot

> **Date:** 2026-09-19  
> **Tag:** `v2.1.0-beta.88`  
> **Type:** feature  
> **Previous:** [`v2.1.0-beta.87`](../../CHANGELOG.md#release-2-1-0-beta-87) — It.70 GitHub API · It.76/77 translation  
> **Includes:** git-fetch deploy-key hotfix already on `main` after `beta.87` (`c0a0afda`)

---

## One-line summary

CMS AI assistant that only proposes (human Apply, never publishes), plus contact E.164 / SMTP reply / article discussion ratings, deploy-key remount for System Update, and an Origin Panel “state as of today” overview.

---

## What shipped

| Area | Change |
|------|--------|
| **It.75** | Allow-listed schema tools, async `agent.run`, Settings default `enabled=false` / `allowedTools=[]`. Editor Suggest SEO → `POST /api/admin/agent/runs` (202). Apply is a separate OCC write. `OutboundUrlGuard` on provider HTTP. Production blocks `http://` and loopback/LAN. Audit without prompts or completions. |
| **Contact / desk** | Public phone is E.164 with a visible hint. Desk **Reply** uses site SMTP (To = visitor; From = SMTP mailbox). Article comments are a discussion; optional 1–5 rating (`comments.ratingEnabled`). `GET /api/auth/me/desk` returns an empty desk instead of 500. |
| **System Update** | Credentials probe reports env-set-but-unreadable keys; `stack.sh` remounts the host deploy key; SSH host keys pinned; deploy key wins over a leftover PAT. |
| **Origin Panel** | Catalog honesty (100% = Shipped). Today snapshot: live / unreleased / next. Roadmap splits focus vs shipped. Ops wave is not a numbered iteration. |

Specs: [ITERATION_75.md](ITERATION_75.md) · [ITERATION_82.md](ITERATION_82.md). Private Ollama/Nginx runbook stays gitignored (`PRIVATE_AI_ASSISTANT.md`).

---

## Operator notes

- **Assistant is off until you enable it.** Settings → CMS AI assistant. Empty tool allow-list = no outbound. Do not point `baseUrl` at `http://127.0.0.1` from production PHP (that is the container). Use a public HTTPS proxy; `baseUrl` must not already end in `/v1`.
- **Apply** writes a draft. Publish remains a separate human action.
- **Deploy this tag**, not `v2.1.0-beta.87` — the older tag rolls back the deploy-key hotfix and everything in this release.

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.88 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

Confirm: `curl -sS http://127.0.0.1:8089/api/health | jq '.data.version // .version'` → `2.1.0-beta.88`.

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.88 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] Settings → CMS AI assistant: off = no outbound; connection test uses `OutboundUrlGuard`.
- [ ] Editor Suggest SEO: 202 run → execute/job → Apply as draft; public page unchanged.
- [ ] Contact form rejects a national number without `+` prefix; Reply sends via SMTP.
- [ ] Article discussion: optional star rating when enabled; desk `/me` does not 500.
- [ ] Origin Panel: today snapshot shows `2.1.0-beta.88`; It.75 is live, next is It.48 / 58f-h / It.95 / 93l-2.
- [ ] `GET /api/health` reports `2.1.0-beta.88`.
