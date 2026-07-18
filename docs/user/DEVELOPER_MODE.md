# Developer Mode — odomknutie a dev tokeny

> Developer Mode je **brána** pred nebezpečnými operáciami (Code Editor, dev logy).  
> Samotná úprava súborov v editore: **[CODE_EDITOR.md](CODE_EDITOR.md)**

---

## Stav gate

| Pole | Význam |
|------|--------|
| `feature_available` | `DEVELOPER_MODE` / `APP_DEBUG` / dev `APP_ENV` povolené v `.env` |
| `unlocked` | Session odomknutá (TOTP alebo token) |
| `unlocked_until` | Unix timestamp expirácie (predvolene +8 h) |
| `method` | `totp` alebo `token:label` |

```bash
curl -s -b cookies.txt http://localhost:8080/api/admin/developer/status | jq .
```

---

## Odomknutie (TOTP)

```http
POST /api/admin/developer/unlock
Content-Type: application/json

{ "totp_code": "123456" }
```

Vyžaduje prihláseného admina s **aktívnou 2FA** ([Bezpečnosť účtu](/account/security) v admin paneli).

---

## Odomknutie (dev token)

```http
POST /api/admin/developer/unlock

{ "token": "pagdev_…" }
```

### Generovanie tokenu (CLI)

```bash
# Načíta .env alebo lokálny fallback pri APP_DEBUG
php backend/bin/dev-token.php --label=moj-pc
php backend/bin/dev-token-register.php
```

Token sa ukladá len ako SHA-256 hash v `storage/dev/registered_tokens.json`.  
Tajomstvo podpisu: `DEV_UNLOCK_SECRET` v `.env` (rovnaká logika ako v `backend/bin/cli-env.php`).

---

## Zamknutie

```http
POST /api/admin/developer/lock
```

V admin UI: tlačidlo **Zamknúť editor** v Code Editore. Viď [CODE_EDITOR.md](CODE_EDITOR.md).

---

## Dev logy (po odomknutí)

```http
GET /api/admin/developer/logs?limit=100
```

---

## Konfigurácia `.env`

```env
DEVELOPER_MODE=true
DEV_UNLOCK_SECRET=change-me-local-dev-secret
APP_DEBUG=true          # alternatíva k DEVELOPER_MODE
APP_ENV=development     # na LAN stačí aj bez explicitného DEVELOPER_MODE
```

**Produkcia:** `DEVELOPER_MODE=false`, `APP_DEBUG=false`, `APP_ENV=production`.

---

## Súvisiace

- [CODE_EDITOR.md](CODE_EDITOR.md) — používateľská príručka Code Editora  
- [deploy/NGINX_API.md](../deploy/NGINX_API.md) — backend `.env` na LAN  
