# CMS AI assistant — operations runbook

> **Scope:** Iteration 75 (`agent.*` settings, `/api/admin/agent/*`, job handler `agent.run`).  
> **Default:** `agent.enabled=false`, `allowedTools=[]` → zero outbound LLM traffic.

---

## 1. What the assistant does (and does not)

| In scope | Out of scope |
|----------|----------------|
| Proposals (SEO, patches, alt text, summaries) via allow-listed tools | Autonomous publish or save without Apply |
| Async runs through the flat-file job queue | Shell, arbitrary URLs, or filesystem tools |
| Human **Apply** with OCC, permissions, and audit | Storing full prompts/completions in logs |

Admin UI: **Settings → CMS AI assistant** plus editor actions (e.g. suggest SEO).  
Spec: [ITERATION_75.md](../ITERATION_75.md) · API: [API.md](../architecture/API.md#it75).

---

## 2. Recommended topology — same host, nginx proxy, no new public ports

Production PHP uses `OutboundUrlGuard`: **HTTPS** to hosts that resolve to **public** IP ranges. Raw `http://127.0.0.1:11434`, `http://192.168.x.x`, or Docker service names from the CMS container are blocked in `APP_ENV=production`.

The supported homelab pattern:

```text
Ollama (or OpenAI-compatible API)
  └─ binds 127.0.0.1:11434 only (not exposed to WAN)

Host nginx (existing CMS vhost, HTTPS)
  └─ location /internal/llm/  →  proxy_pass http://127.0.0.1:11434/

CMS PHP (Docker or FPM)
  └─ agent.baseUrl = https://<your-cms-host>/internal/llm
       (no trailing /v1 — the driver appends /v1/chat/completions)
```

Traffic stays on the server: the guard sees your **public CMS hostname** (allowed), nginx terminates TLS and forwards to loopback Ollama. You do **not** open Ollama’s port on the firewall.

Example snippet: [nginx-internal-llm.conf.example](../deploy/nginx-internal-llm.conf.example).

### LibreTranslate (It.76)

Use the **same pattern**: proxy `/internal/translate/` → your LibreTranslate HTTP port, set `translation.baseUrl` to `https://<your-cms-host>/internal/translate` (no `/translate` suffix in settings — the driver adds paths).

---

## 3. Ollama on the homeserver

```bash
# Example: install and pull a model (operator choice)
curl -fsSL https://ollama.com/install.sh | sh
ollama pull llama3.2

# Bind localhost only (systemd override or OLLAMA_HOST=127.0.0.1:11434)
ss -ltnp | grep 11434
```

Verify locally:

```bash
curl -sS http://127.0.0.1:11434/api/tags
```

After nginx is configured:

```bash
curl -sS -H "Authorization: Bearer <optional-token>" \
  https://<your-cms-host>/internal/llm/v1/models
```

---

## 4. CMS settings (`agent` group)

| Field | Notes |
|-------|--------|
| `enabled` | Master switch. Off = no LLM calls. |
| `provider` | `ollama` or `openai_compatible` (OpenAI-compatible HTTP API). |
| `baseUrl` | HTTPS base **without** `/v1`. Example: `https://paginium.example/internal/llm`. |
| `apiKey` | Optional Bearer token (encrypted at rest, write-only in UI). |
| `model` | Ollama model id (e.g. `llama3.2`). Empty → driver default. |
| `allowedTools` | Comma-separated allow-list. **Empty = no tools** even when enabled. |
| `maxTokensPerRun`, `maxToolSteps`, `dailyTokenLimit`, `timeoutSeconds` | Cost and safety bounds. |

Known tools: `content.read`, `content.propose_patch`, `seo.suggest_meta`, `media.suggest_alt`, `comments.summarize`, `translation.translate`.

Save settings, then use **Test connection** in the assistant panel (`POST /api/admin/agent/connection`).

---

## 5. Scheduler and worker (required for long runs)

HTTP `POST /api/admin/agent/runs` returns **202** and enqueues work. Execution uses handler **`agent.run`** via the worker.

Ensure host cron runs both (same checkout and `.env` as the web app):

```bash
php backend/bin/console scheduler:run
php backend/bin/console worker:process
```

See [CRON.md](../deploy/CRON.md). Without the worker, runs stay queued and proposals never appear.

---

## 6. Security checklist

- [ ] Ollama/LibreTranslate **not** reachable from the internet (localhost or unix socket + nginx only).
- [ ] nginx `location` for `/internal/llm/` (and translate) restricted: `allow` management IPs or `auth_request` if exposed on a shared host.
- [ ] `allowedTools` lists only what editors need; empty deny-by-default.
- [ ] Staff use 2FA; Apply requires live permissions + revision match.
- [ ] No secrets in `baseUrl` (userinfo in URLs is rejected by the guard).

Development / `APP_ENV=local`: the guard allows HTTP and private IPs for local testing only — do not rely on that in production.

---

## 7. Troubleshooting

| Symptom | Likely cause | Action |
|---------|----------------|--------|
| Test connection: SSRF / URL not allowed | LAN or `http://` URL in production | Use HTTPS same-host nginx proxy (§2). |
| Test OK, runs stuck queued | Worker not running | Fix cron + `worker:process` (§5). |
| `No tools are allowed` | Empty or invalid `allowedTools` | Add comma-separated tool names (§4). |
| 503 provider unavailable | Ollama down, wrong model, timeout | Check Ollama logs, increase `timeoutSeconds`. |
| Apply 409 | Content changed during run | Re-run proposal against current revision. |

---

## 8. Related documents

- [NGINX_API.md](../deploy/NGINX_API.md) — same-origin proxy and location ordering  
- [RELEASE_2_1_0_BETA_88.md](../RELEASE_2_1_0_BETA_88.md) — release operator notes  
- [SECURITY.md](../developer/SECURITY.md) §14, §22.2  
- [ITERATION_76.md](../ITERATION_76.md) — self-hosted translation (parallel URL policy)

**MkDocs:** future admin guide with screenshots is planned in [MKDOCS_ADMIN_GUIDE_PLAN.md](../meta/MKDOCS_ADMIN_GUIDE_PLAN.md).
