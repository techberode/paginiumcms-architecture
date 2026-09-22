# CMS AI asistent — prevádzkový runbook

> **Rozsah:** Iterácia 75 (`agent.*` nastavenia, `/api/admin/agent/*`, job handler `agent.run`).  
> **Predvolene:** `agent.enabled=false`, `allowedTools=[]` → žiadna odchádzajúca LLM prevádzka.

---

## 1. Čo asistent robí (a čo nie)

| V rozsahu | Mimo rozsahu |
|-----------|----------------|
| Návrhy (SEO, patch, alt text, sumáre) cez allow-list toolov | Autonómny publish alebo zápis bez Apply |
| Async behy cez flat-file frontu | Shell, ľubovoľné URL alebo filesystem tooly |
| Ľudské **Apply** s OCC, oprávneniami a auditom | Ukladanie plných promptov/odpovedí do logov |

Admin UI: **Nastavenia → CMS AI asistent** + akcie v editore (napr. navrhnúť SEO).  
Špecifikácia: [ITERATION_75.md](../ITERATION_75.md) · API: [API.md](../architecture/API.md).

---

## 2. Odporúčaná topológia — ten istý host, nginx proxy, žiadne nové porty do internetu

Produkčné PHP používa `OutboundUrlGuard`: **HTTPS** na hosty, ktoré DNS rieši do **verejných** IP rozsahov. Surové `http://127.0.0.1:11434`, `http://192.168.x.x` alebo Docker názvy služieb z CMS kontajnera sú pri `APP_ENV=production` blokované.

Podporovaný homelab vzor:

```text
Ollama (alebo OpenAI-compatible API)
  └─ počúva len 127.0.0.1:11434 (bez vystavenia na WAN)

Host nginx (existujúci CMS vhost, HTTPS)
  └─ location /internal/llm/  →  proxy_pass http://127.0.0.1:11434/

CMS PHP (Docker alebo FPM)
  └─ agent.baseUrl = https://<tvoj-cms-host>/internal/llm
       (bez koncového /v1 — driver doplní /v1/chat/completions)
```

Prevádzka ostáva na serveri: guard vidí **verejný hostname CMS** (povolené), nginx ukončí TLS a pošle na loopback Ollama. **Neotváraš** port Ollamy vo firewalle.

Príklad snippetu: [nginx-internal-llm.conf.example](../deploy/nginx-internal-llm.conf.example).

### LibreTranslate (It.76)

**Rovnaký vzor:** proxy `/internal/translate/` → port LibreTranslate, `translation.baseUrl` = `https://<tvoj-cms-host>/internal/translate` (v nastaveniach bez `/translate` — cesty doplní driver).

---

## 3. Ollama na homeserveri

```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull llama3.2
# OLLAMA_HOST=127.0.0.1:11434 — len localhost
ss -ltnp | grep 11434
```

Lokálna kontrola:

```bash
curl -sS http://127.0.0.1:11434/api/tags
```

Po nginx:

```bash
curl -sS https://<tvoj-cms-host>/internal/llm/v1/models
```

---

## 4. Nastavenia CMS (`agent`)

| Pole | Poznámka |
|------|-----------|
| `enabled` | Hlavný vypínač. |
| `provider` | `ollama` alebo `openai_compatible`. |
| `baseUrl` | HTTPS základ **bez** `/v1`. |
| `apiKey` | Voliteľný Bearer (šifrovaný, write-only v UI). |
| `model` | ID modelu v Ollame (napr. `llama3.2`). |
| `allowedTools` | Čiarkou oddelený allow-list. **Prázdne = žiadne tooly.** |
| Limity / timeout | Náklady a bezpečnostné hranice. |

Známe tooly: `content.read`, `content.propose_patch`, `seo.suggest_meta`, `media.suggest_alt`, `comments.summarize`, `translation.translate`.

Po uložení: **Otestovať pripojenie** v paneli asistenta.

---

## 5. Scheduler a worker

HTTP `POST /api/admin/agent/runs` vráti **202** a zaradí job. Spracovanie: handler **`agent.run`** cez worker.

Cron musí spúšťať (rovnaký checkout a `.env` ako web):

```bash
php backend/bin/console scheduler:run
php backend/bin/console worker:process
```

Viac: [CRON.md](../deploy/CRON.md). Bez workera behy ostanú vo fronte.

---

## 6. Bezpečnostný checklist

- [ ] Ollama/LibreTranslate **nie** dostupné z internetu (localhost + nginx).
- [ ] nginx `location` pre `/internal/llm/` obmedzené (IP allow alebo auth).
- [ ] `allowedTools` len podľa potreby.
- [ ] 2FA pre staff; Apply kontroluje oprávnenia a revíziu.

V `local`/`development` guard dočasne povolí HTTP a privátne IP — len na vývoj, nie produkciu.

---

## 7. Riešenie problémov

| Príznak | Príčina | Riešenie |
|---------|---------|----------|
| SSRF / URL not allowed | LAN alebo `http://` v produkcii | HTTPS proxy na tom istom hoste (§2). |
| Beh visí queued | Nebeží worker | Cron + `worker:process` (§5). |
| No tools allowed | Prázdny `allowedTools` | Doplniť tooly (§4). |

---

## 8. Súvisiace dokumenty

- [NGINX_API.md](../deploy/NGINX_API.md)  
- [RELEASE_2_1_0_BETA_88.md](../RELEASE_2_1_0_BETA_88.md)  
- [SECURITY.md](../developer/SECURITY.md)  
- [ITERATION_76.md](../ITERATION_76.md)

**MkDocs:** plán admin príručky so screenshotmi: [MKDOCS_ADMIN_GUIDE_PLAN.md](../meta/MKDOCS_ADMIN_GUIDE_PLAN.md).
