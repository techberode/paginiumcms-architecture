# Iteration 47 – Notification connector credentials

**Status:** ✅ Implemented (Unreleased)  
**Version target:** 2.0.25 (planned)  
**Priority:** 🟡 — po It.41 (OTP workflows používajú rovnaké konektory)

## Problém dnes

It.6 prinieslo konektory (email, ntfy, Discord, Telegram, webhook), ale **autentifikácia je neúplná**:

| Konektor | Dnes v Settings | Chýba |
|----------|-----------------|-------|
| **ntfy** | server URL + topic | **Access token** (`Authorization: Bearer`), Basic auth (username/password) pre self-hosted inštancie |
| **Telegram** | bot token + chat ID | ✅ stačí (token = auth) |
| **Discord** | webhook URL | ✅ URL obsahuje secret token |
| **Webhook** | URL + optional secret | ✅ HMAC header pri secret |
| **Email** | SMTP user/password | ✅ cez skupinu `smtp` |

`NtfyAdapter` posiela POST **bez auth hlavičiek** → zlyhá na privátnych topicoch alebo self-hosted ntfy s ACL.

## Ciele It.47

1. Admin môže zadať **ntfy access token** a voliteľne **username/password** (Basic).
2. **Test pripojenia** per konektor (`POST /api/admin/notifications/test-connector`) s jasnou chybou.
3. Credentials ukladané ako **password fields** v settings (nikdy v plain logu).
4. Rovnaký pattern pre ďalšie konektory, ak vyžadujú extra auth (napr. webhook custom header name).

## Backend

### Settings schema (`connectors`)

| Pole | Typ | Popis |
|------|-----|-------|
| `ntfyAuthMode` | enum | `none` \| `token` \| `basic` |
| `ntfyAccessToken` | password | Bearer token pre ntfy.sh / ACL topic |
| `ntfyUsername` | string | Basic auth (self-hosted) |
| `ntfyPassword` | password | Basic auth |
| `webhookAuthHeader` | string | Voliteľný názov hlavičky (default `X-Webhook-Secret`) |

### Adaptéry

- `NtfyAdapter` — doplniť hlavičky podľa `ntfyAuthMode`
- `WebhookAdapter` — podpora custom header name + HMAC
- `NotificationFactory` — inject credentials z settings

### API

| Metóda | Endpoint | Popis |
|--------|----------|-------|
| POST | `/api/admin/notifications/test-connector` | Body: `{ "connector": "ntfy" }` — test bez odoslania incidentu |
| GET | `/api/admin/notifications/status` | Rozšírenie: `authenticated: true/false` per channel |

### Bezpečnosť

- Tokeny len v `settings.json` (gitignored storage path) — nikdy v audit log plaintext
- Rate-limit test endpoint (1/min per admin session)
- Audit trail: `notification.connector_test` bez secret hodnôt

## Frontend

- **Nastavenia → Connectors** — sekcia „Autentifikácia“ pri ntfy:
  - prepínač režimu (žiadna / token / login)
  - password input pre token
  - username + password pre Basic
- Tlačidlo **Otestovať ntfy** vedľa existujúceho test-send
- `/notifications` overview — badge „Auth OK / chýba token“

## Testy

- PHPUnit: `NtfyAdapterTest` — Bearer, Basic, no-auth
- PHPUnit: `NotificationFactoryTest` — wiring auth modes
- Vitest: settings form validation pre password fields

## Súvisiace

- [ITERATION_6.md](ITERATION_6.md) — pôvodný notification stack
- [ITERATION_41.md](ITERATION_BACKLOG.md#iterácia-41--email-otp-schvaľovanie-) — OTP maily cez rovnaké konektory
- [architecture/SETTINGS.md](architecture/SETTINGS.md) — skupina `connectors`

## Out of scope

- OAuth2 login flow pre ntfy (browser redirect) — backlog ak self-hosted vyžaduje SSO
- Push notifikácie do prehliadača (Web Push) — iná iterácia
