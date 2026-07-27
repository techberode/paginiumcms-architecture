# Demo instance — deploy, login, CORS (ISS-098), cron (ISS-099)

> **Doména:** `https://demo.paginiumcms.com`  
> **Účel:** predvádzacie vozidlo — plný CMS trial, zmeny sa resetujú (**It.13 v4**, `v2.1.0-beta.11`).  
> **Nie je súčasť zákazníckeho balíka** — `DEMO_MODE=true` len na tejto inštancii.

---

## Architektúra (typické nasadenie)

| Položka | Hodnota |
|---------|---------|
| Git clone | `/var/www/paginiumcms-demo` |
| Docker stack | `/var/lib/docker/compose/paginiumcms-demo` |
| Host nginx root | `/var/www/paginiumcms-demo/frontend/dist` |
| PHP upstream | `127.0.0.1:8091` |
| Prod (porovnanie) | `paginiumcms.com` → port **8089** |

```
Prehliadač → https://demo.paginiumcms.com/
           → host nginx (static dist + proxy /api → :8091)
           → Docker PHP (DEMO_MODE=true, storage/app/demo/)
```

---

## Demo prihlasovacie údaje

| Pole | Hodnota |
|------|---------|
| E-mail | `demo@paginiumcms.com` |
| Heslo | `Demo123!` |

**Poznámka:** `FIRST_ADMIN_*` v `.env` sa pri `DEMO_MODE=true` **ignoruje** — platí seed účet z `DemoFixtures`.

---

## Povinné `.env` (demo)

Šablóna: [app.env.demo.example](app.env.demo.example)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.paginiumcms.com

DEMO_MODE=true
DEMO_PUBLIC_URL=https://demo.paginiumcms.com
VITE_PUBLIC_URL=https://demo.paginiumcms.com

VITE_API_URL=
SESSION_LIFETIME=14400
DEMO_AUTO_RESET_MINUTES=60
TRUSTED_PROXIES=127.0.0.1,::1
```

**Kritické:** `APP_URL` musí sedieť s URL v prehliadači. Ak je napr. `https://paginiumcms.com`, login v browseri zlyhá (ISS-098).

Po zmene `.env`:

```bash
cd /var/lib/docker/compose/paginiumcms-demo
./stack.sh up -d    # nie len restart — načíta env znova
```

---

## ISS-098 — Demo login 401, prázdne telo v DevTools

### Symptóm

| Nástroj | Výsledok |
|---------|----------|
| `curl` bez `Origin` | ✅ `200` JSON `{ "success": true, "user": … }` |
| Prehliadač / axios | ❌ `401`, `Content-Type: text/html`, **prázdne telo** |
| Konzola FE | `Unexpected end of JSON input` |

### Príčina

Prehliadač posiela hlavičku `Origin: https://demo.paginiumcms.com`.  
**Tuupola CorsMiddleware** odmietne neznámy origin → HTTP **401** s prázdnym HTML telom (nie JSON z `AuthController`).

Typické spúšťače:

- `APP_URL` v demo `.env` ukazuje na produkciu alebo localhost
- Chýba `DEMO_PUBLIC_URL` / `VITE_PUBLIC_URL` v CORS allow-liste (pred opravou v kóde)

### Riešenie A — okamžité (len `.env`, bez git pull)

```bash
APP_ROOT=/var/www/paginiumcms-demo
STACK_DIR=/var/lib/docker/compose/paginiumcms-demo

grep -E '^(APP_URL|DEMO_MODE|DEMO_PUBLIC_URL)=' "$APP_ROOT/.env"

# Oprava ak treba:
sed -i 's|^APP_URL=.*|APP_URL=https://demo.paginiumcms.com|' "$APP_ROOT/.env"
grep -q '^DEMO_PUBLIC_URL=' "$APP_ROOT/.env" || \
  echo 'DEMO_PUBLIC_URL=https://demo.paginiumcms.com' >> "$APP_ROOT/.env"

cd "$STACK_DIR" && ./stack.sh up -d
sleep 5
```

### Riešenie B — kód (SameOriginCorsMiddleware)

Commit na `main` pridáva `SameOriginCorsMiddleware` — automaticky povolí Origin, ak sedí s `Host` (+ `X-Forwarded-Proto`). Funguje aj pri zle nastavenom `APP_URL` pri same-origin SPA.

Súbory:

- `backend/app/Http/Middleware/SameOriginCorsMiddleware.php`
- `backend/bootstrap/app.php` — nahradí priame volanie `CorsMiddleware`
- `backend/tests/Http/Middleware/SameOriginCorsMiddlewareTest.php`

---

## C&P — plný deploy demo (kód + FE)

```bash
APP_ROOT=/var/www/paginiumcms-demo
STACK_DIR=/var/lib/docker/compose/paginiumcms-demo
BACKEND_PORT=8091

cd "$APP_ROOT"
git fetch origin
git checkout v2.1.0-beta.11   # alebo: git pull origin main
git log -1 --oneline

composer install --no-dev --optimize-autoloader

cd frontend
npm ci
npm run build:prod    # VITE_API_URL musí byť prázdne v .env
cd ..

"$STACK_DIR/stack.sh" restart php
sleep 8
curl -sf "http://127.0.0.1:${BACKEND_PORT}/api/health" | jq .
```

Alternatíva — helper skript:

```bash
APP_ROOT=/var/www/paginiumcms-demo \
STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
BACKEND_PORT=8091 \
./scripts/deploy-instance-update.sh
```

---

## C&P — smoke testy (po deployi)

### 1. Health + demo settings (no password)

```bash
curl -s https://demo.paginiumcms.com/api/health | jq .
curl -s https://demo.paginiumcms.com/api/settings/public | jq '.data.demo'
# očakávané: enabled: true, loginEmail — **bez** credentials/password
```

### 2. Login bez Origin (curl baseline)

```bash
curl -sS -X POST 'https://demo.paginiumcms.com/api/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}' | jq '.success'
# očakávané: true
```

### 3. Login **s Origin** (simulácia prehliadača — ISS-098)

```bash
curl -sS -o /tmp/demo-login-origin.txt -w 'HTTP %{http_code} CT:%{content_type} size:%{size_download}\n' \
  -X POST 'https://demo.paginiumcms.com/api/auth/login' \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://demo.paginiumcms.com' \
  -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}'

head -c 200 /tmp/demo-login-origin.txt; echo
# očakávané: HTTP 200, application/json, {"success":true,...}
# zlyhanie:   HTTP 401, text/html, size 0  → oprav APP_URL alebo git pull (SameOriginCors)
```

### 4. CORS preflight (OPTIONS)

```bash
curl -sS -D - -o /dev/null \
  -X OPTIONS 'https://demo.paginiumcms.com/api/auth/login' \
  -H 'Origin: https://demo.paginiumcms.com' \
  -H 'Access-Control-Request-Method: POST' \
  -H 'Access-Control-Request-Headers: content-type,x-csrf-token' | head -15
# očakávané: HTTP 200 + Access-Control-Allow-Origin: https://demo.paginiumcms.com
```

### 5. Prehliadač

1. Otvor `https://demo.paginiumcms.com/login`
2. **Vyplniť demo údaje** (amber box) → Prihlásiť sa
3. DevTools → Network → `POST /api/auth/login` → **200**, JSON s `success: true`
4. Verejný web — amber **DemoPublicStrip** s countdownom

### 6. Quick login (v4 — S-DEMOCREDS)

```bash
curl -sS -X POST 'https://demo.paginiumcms.com/api/demo/quick-login' \
  -H 'Content-Type: application/json' | jq .
# očakávané: success: true, user: { email: demo@paginiumcms.com, ... }
```

### 7. Public demo info

```bash
curl -s https://demo.paginiumcms.com/api/demo/public-info | jq .
# očakávané: enabled: true, loginEmail, next_reset_at, seconds_until_reset
```

### 8. Reset seed (po upgrade)

V admin: **Demo** (`/demo`) → **Reset demo seed** — načíta komentáre, správy, newsletter, kontakt.

### 8. CLI auto-reset (ISS-099)

```bash
cd /var/www/paginiumcms-demo
php backend/bin/console demo:reset-if-due
# očakávané: ✅ Demo snapshot obnovený  alebo  ⏭ Demo reset nebol spustený (not_due / demo_disabled)
# zlyhanie: Permission denied na data/plugins.json → § ISS-099 nižšie
```

---

## ISS-099 — `demo:reset-if-due` Permission denied (`plugins.json`)

### Symptóm

Host CLI spadne ešte pred resetom:

```
PHP Warning: fopen(.../storage/app/demo/data/plugins.json): Failed to open stream: Permission denied
PHP Fatal error: Unable to open plugin registry
```

Web/API v Dockeri môže fungovať — cron na hoste nie.

### Príčina

Rovnaká trieda problému ako **ISS-094** (It.62): **SSH user** na hoste vs **`www-data`** v PHP kontajneri.

`backend/bin/console` načíta celý `bootstrap/app.php` → `PluginManager->bootEnabledExtensions()` → `PluginRegistry` otvára `storage/app/demo/data/plugins.json` režimom `c+` (vyžaduje zápis). Súbor/adresár vytvoril Docker ako `www-data`; host user nemá group write.

| Prostredie | User | Storage |
|------------|------|---------|
| Docker PHP (web + API) | `www-data` | `storage/app/demo/` |
| Host cron / SSH CLI | napr. `marian` | ten istý mount — potrebuje zdieľanú skupinu |

### Riešenie A — zdieľané práva (odporúčané)

Rovnaký pattern ako prod scheduler ([ITERATION_62.md](../ITERATION_62.md)):

```bash
APP_ROOT=/var/www/paginiumcms-demo
STACK_DIR=/var/lib/docker/compose/paginiumcms-demo

cd "$APP_ROOT"

# Diagnostika
ls -la backend/storage/app/demo/data/
id -un

# Setgid + skupina www-data (host user + kontajner)
sudo chown -R "$(id -un):www-data" backend/storage
sudo find backend/storage -type d -exec chmod 2775 {} \;
sudo find backend/storage -type f -exec chmod 664 {} \;

# Ak plugins.json chýba
sudo -u www-data touch backend/storage/app/demo/data/plugins.json
sudo chmod 664 backend/storage/app/demo/data/plugins.json

# Overenie zápisu — host
touch backend/storage/app/demo/data/.write-test-host \
  && rm backend/storage/app/demo/data/.write-test-host && echo HOST_WRITE_OK

# Overenie zápisu — Docker www-data
cd "$STACK_DIR"
./stack.sh exec -u www-data php sh -c \
  'touch /var/www/html/backend/storage/app/demo/data/.write-test-docker && rm /var/www/html/backend/storage/app/demo/data/.write-test-docker && echo DOCKER_WRITE_OK'

# Smoke reset
cd "$APP_ROOT"
php backend/bin/console demo:reset-if-due
```

### Riešenie B — cron len v Dockeri

Ak nechceš meniť ownership na hoste:

```bash
cd /var/lib/docker/compose/paginiumcms-demo
./stack.sh exec php php backend/bin/console demo:reset-if-due
```

Crontab (každých 15 min, interval resetu riadi `DEMO_AUTO_RESET_MINUTES` v `.env`):

```cron
*/15 * * * * cd /var/lib/docker/compose/paginiumcms-demo && ./stack.sh exec -T php php backend/bin/console demo:reset-if-due >> /var/log/paginium-demo-reset.log 2>&1
```

**Odporúčanie:** Riešenie A — potom funguje aj manuálny `php backend/bin/console …` z SSH bez Docker wrappera.

---

## C&P — cron auto-reset (demo)

Po úspešnom smoke teste z §8 / ISS-099:

```cron
*/15 * * * * cd /var/www/paginiumcms-demo && /usr/bin/php backend/bin/console demo:reset-if-due >> /var/log/paginium-demo-reset.log 2>&1
```

| Premenná | Default | Význam |
|----------|---------|--------|
| `DEMO_AUTO_RESET_MINUTES` | `60` | Po koľkých minútach sa má obnoviť snapshot |
| Cron interval | `*/15` | Len kontrola — príkaz je no-op, kým interval neuplynie |

Voliteľne na demo inštancii aj prod scheduler ([CRON.md](CRON.md)) — `scheduler:run`, `worker:process` — ak chceš scheduled publish / backup aj na demo.

---

## C&P — prod footer odkaz na demo

Prod (`paginiumcms.com`) musí mať v settings `demo.enabled: false`. Footer odkaz riadi **Nastavenia → Marketing** (`demoFooterLinkEnabled`, `demoUrl`). Odkaz ide na `demo.paginiumcms.com` v **novom tabe** (`target="_blank"`).

Deploy prod FE po `git pull`:

```bash
cd /var/www/paginiumcms.com/frontend && npm ci && npm run build:prod
# nginx servuje nový dist — restart PHP netreba pre statiku
```

---

## Čo **nie** pomáha (ISS-098)

| Skúšané | Prečo nefunguje |
|---------|-----------------|
| Vymazať cookies / SW / incognito | Origin hlavička ide vždy |
| `mockServiceWorker.js` | MSW beží len pri `VITE_MSW=true` |
| Rate limit / lockout | vracia 429 JSON, nie prázdnu 401 |
| Zlé heslo | vracia 401 **JSON** s chybovou správou |

---

## Súvisiace dokumenty

- [DEPLOY.md](DEPLOY.md) — §C demo, §F troubleshooting
- [ITERATION_13.md](../ITERATION_13.md) — demo modul v3 ✅
- [developer/RELEASE.md](../developer/RELEASE.md) — `v2.1.0-beta.10` C&P
- [ISSUES.md](../ISSUES.md) — ISS-098, ISS-099
- [CRON.md](CRON.md) — prod + demo crontab
- [ITERATION_62.md](../ITERATION_62.md) — storage permissions (ISS-094, rovnaký pattern ako ISS-099)
- [NGINX_API.md](NGINX_API.md) — host nginx + `/api` proxy
- [app.env.demo.example](app.env.demo.example) — env šablóna
