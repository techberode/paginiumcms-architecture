# Lokálne prostredie — PaginiumCMS

> **RECOMMENDATIONS Fáza 2** · Docker Compose + natívny vývoj bez kontajnerov.

---

## Rýchly štart (odporúčané)

```bash
git clone <repo> paginiumcms && cd paginiumcms
chmod +x scripts/first-run.sh
./scripts/first-run.sh
docker compose up -d
curl -s http://localhost:8080/api/health | head
```

**Predvolený admin** (len ak ešte neexistuje žiadny používateľ):

| Pole | Hodnota |
|------|---------|
| Email | `admin@localhost` |
| Heslo | `Admin123!ChangeMe` |

Vlastné údaje cez env pred `./scripts/first-run.sh`:

```bash
export FIRST_ADMIN_EMAIL=you@example.com
export FIRST_ADMIN_PASSWORD='YourStr0ngPass!'
export FIRST_ADMIN_NAME='Your Name'
./scripts/first-run.sh
```

Frontend (voliteľne):

```bash
INSTALL_FRONTEND=1 ./scripts/first-run.sh
cd frontend && npm run dev
# → http://localhost:3025 (Vite proxy /api → :8080)
```

---

## Čo robí `scripts/first-run.sh`

1. Skopíruje `.env.example` → `.env` (ak chýba)
2. Vygeneruje `APP_KEY` (ak chýba) — potrebné pre šifrovanie tajomstiev at-rest
3. Vytvorí `backend/storage/*` strom (cache, logy, content, users, index, …)
4. `composer install` (ak chýba `vendor/`)
5. `php backend/bin/bootstrap-admin.php` — prvý SUPER_ADMIN
6. `php backend/bin/console content:diagnose --fix` — index + cache

Spustiteľné aj v Dockeri:

```bash
docker compose run --rm php bash scripts/first-run.sh
```

---

## Premenné prostredia (first-run / bootstrap)

| Premenná | Kde | Význam |
|----------|-----|--------|
| `FIRST_ADMIN_EMAIL` | shell env | Email prvého SUPER_ADMIN (default `admin@localhost`) |
| `FIRST_ADMIN_PASSWORD` | shell env | Heslo — musí spĺňať password policy |
| `FIRST_ADMIN_NAME` | shell env | Zobrazované meno (default `Administrator`) |
| `INSTALL_FRONTEND=1` | shell env | Po first-run spustí `npm ci` vo `frontend/` |
| `APP_KEY` | `.env` | Generuje first-run ak chýba — **nemeň** po nasadení so šifrovanými settings |
| `APP_ENV` | `.env` | `development` lokálne, `production` na live |
| `APP_DEBUG` | `.env` | `true` lokálne, **`false`** na produkcii |
| `TWO_FACTOR_REQUIRED` | `.env` | `false` len dev; produkcia vždy vyžaduje 2FA pre staff |
| `SESSION_LIFETIME` | `.env` | Odporúčané 7200–28800 s pri editácii |
| `SESSION_STRICT` | `.env` | `false` za reverse proxy / LAN |
| `DEMO_MODE` | `.env` | **`false`** na zákazníckej inštancii |

Kompletný zoznam: `.env.example` · [user/INSTALLATION.md](../user/INSTALLATION.md) § `.env`.

---

## Docker Compose

| Služba | Port | Popis |
|--------|------|--------|
| `nginx` + `php` | **8080** | Slim API (`backend/public`) |
| `frontend` (profile `dev`) | **3025** | Vite dev server |
| `frontend-build` (profile `build`) | — | `npm run build` do `frontend/dist` |

```bash
# Len backend
docker compose up -d

# Backend + Vite (profil dev)
docker compose --profile dev up -d

# Production bundle FE
docker compose --profile build run --rm frontend-build
```

Súbory:

- `docker-compose.yml`
- `docker/php/Dockerfile` — PHP **8.5-FPM**, rozšírenia: mbstring, zip, curl, opcache
- `docker/nginx/default.conf` — front controller → `index.php`

Health check:

```bash
curl http://localhost:8080/api/health
curl http://localhost:8080/api/test
```

---

## Natívny vývoj (bez Dockeru)

Rovnaká príprava cez `first-run`, potom dva terminály:

```bash
# Terminal 1 — backend
cd backend/public
php -S localhost:8080

# Terminal 2 — frontend
cd frontend
npm install   # alebo npm ci
npm run dev
# → http://localhost:3025
```

Podrobnejší troubleshooting: [deploy/DEV.md](../deploy/DEV.md).

---

## Composer skripty (Fáza 1)

```bash
composer test    # PHPUnit
composer stan    # PHPStan L8
composer cs      # php -l syntax lint
composer gate    # iteration-gate.sh
composer audit   # security audit
```

---

## Produkcia / LAN

- Jeden host (nginx + SPA + API): [deploy/NGINX_API.md](../deploy/NGINX_API.md)
- Rozdelený LAN test (SPA :8081, PHP :8080): [deploy/nginx-paginium-test.conf](../deploy/nginx-paginium-test.conf)

Pri produkčnom build-e nechaj `VITE_API_URL` prázdne (same-origin `/api`).

**Produkcia — cron:** [deploy/CRON.md](../deploy/CRON.md) · **Beta gate:** [BETA_INFRA.md](./BETA_INFRA.md).

---

## Časté problémy

| Symptom | Riešenie |
|---------|----------|
| `401` po prihlásení | Reštart PHP/Docker; `.env`: `SESSION_LIFETIME=28800`, `SESSION_STRICT=false` |
| 2FA obmedzuje vývoj | `TWO_FACTOR_REQUIRED=false` + `APP_ENV=development` |
| Prázdny obsah | `php backend/bin/console content:diagnose --fix` |
| Docker port obsadený | Uprav `8080:80` v `docker-compose.yml` |
| Žiadny admin | `./scripts/first-run.sh` alebo `php backend/bin/bootstrap-admin.php` |

---

## Súvisiace dokumenty

- [RECOMMENDATIONS.md](../../RECOMMENDATIONS.md) — plán fáz
- [ROADMAP.md](../ROADMAP.md)
- [CODING_STANDARDS.md](./CODING_STANDARDS.md)
- [TESTING.md](./TESTING.md)
