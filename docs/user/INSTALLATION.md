# Inštalácia PaginiumCMS

> **Cieľ:** mať bežiaci backend (API na porte 8080) a admin + verejný web v prehliadači.  
> **Posledná aktualizácia:** júl 2026 · Fáza 2 (Docker + `first-run.sh`)

---

## Požiadavky

| Komponent | Minimálna verzia | Poznámka |
|-----------|------------------|----------|
| PHP | **8.5+** | s rozšíreniami: json, mbstring, zip, curl, fileinfo |
| Composer | 2.x | na serveri alebo lokálne pred uploadom |
| Node.js | 22+ | len ak **buildujete** frontend sami |
| nginx alebo Apache | odporúčané | produkcia; lokálne stačí Docker alebo `php -S` |
| RAM | 512 MB+ | odporúčané 1 GB+ pre PHP + nginx |

PaginiumCMS **nepoužíva SQL databázu** — všetky dáta sú v súboroch pod `backend/storage/`.

---

## Varianta A — Docker (najjednoduchšia pre beta)

```bash
unzip paginiumcms-beta.zip && cd paginiumcms
chmod +x scripts/first-run.sh
./scripts/first-run.sh
docker compose up -d
curl -s http://localhost:8080/api/health
```

| Služba | URL |
|--------|-----|
| API + backend | http://localhost:8080 |
| Admin (Vite dev, voliteľné) | `docker compose --profile dev up` → http://localhost:3025 |

Podrobnosti: [developer/LOCAL_SETUP.md](../developer/LOCAL_SETUP.md).

---

## Varianta B — Natívne (bez Dockeru)

### 1. Príprava

```bash
cd paginiumcms
composer install --no-dev --optimize-autoloader   # produkcia
# alebo: composer install                         # vývoj + testy

chmod +x scripts/first-run.sh
./scripts/first-run.sh
```

Skript `first-run.sh` automaticky:

- vytvorí `.env` z `.env.example`
- vygeneruje `APP_KEY` (šifrovanie tajomstiev)
- pripraví priečinky `backend/storage/*`
- vytvorí **prvého admina** (ak ešte neexistuje žiadny účet)
- spustí `content:diagnose --fix`

**Predvolený admin** (zmeň hneď po prvom prihlásení):

| Pole | Hodnota |
|------|---------|
| Email | `admin@localhost` |
| Heslo | `Admin123!ChangeMe` |

Vlastné údaje pred spustením skriptu:

```bash
export FIRST_ADMIN_EMAIL=admin@vase-domena.sk
export FIRST_ADMIN_PASSWORD='VaseSilneHeslo1!'
export FIRST_ADMIN_NAME='Hlavný admin'
./scripts/first-run.sh
```

### 2. Backend

```bash
cd backend/public
php -S 0.0.0.0:8080
```

Overenie: `curl http://localhost:8080/api/health` → JSON so `"success": true`.

### 3. Frontend (admin + verejný web)

**Produkcia (jeden server):**

```bash
cd frontend
npm ci
npm run build:prod    # VITE_API_URL prázdne → same-origin /api
```

Výstup je v `frontend/dist/`. Nginx musí:

- servírovať `dist/` ako SPA (`try_files … /index.html`)
- proxyovať `/api`, `/storage`, `/feed.xml`, `/sitemap.xml`, `/robots.txt` na PHP

Ukážka: [deploy/NGINX_API.md](../deploy/NGINX_API.md).

**Lokálny vývoj:**

```bash
cd frontend && npm run dev
# → http://localhost:3025 (proxy /api → :8080)
```

---

## Varianta C — Produkčný hosting (VPS)

Typický postup:

1. Nahrať balík na server (bez `.env`, bez `backend/storage/logs`, bez `vendor/` ak buildíte na CI)
2. Na serveri: `composer install --no-dev`
3. `./scripts/first-run.sh`
4. Nastaviť nginx podľa [NGINX_API.md](../deploy/NGINX_API.md)
5. `npm run build:prod` v `frontend/` (alebo nahrať hotový `dist/` z CI)
6. Nastaviť cron (voliteľné):

```bash
* * * * * cd /var/www/paginiumcms && php backend/bin/console scheduler:run && php backend/bin/console worker:process
```

---

## Súbor `.env` — dôležité premenné

Skopíruj `.env.example` → `.env`. Minimálne skontroluj:

| Premenná | Význam |
|----------|--------|
| `APP_ENV` | `production` na live, `development` lokálne |
| `APP_DEBUG` | `false` na produkcii |
| `APP_URL` | Verejná URL backendu (napr. `https://cms.example.sk`) |
| `APP_KEY` | Generuje `first-run.sh` — **nemeň** po nasadení so šifrovanými dátami |
| `FIRST_ADMIN_EMAIL` | Voliteľné env pred `first-run` — email prvého admina (default `admin@localhost`) |
| `FIRST_ADMIN_PASSWORD` | Voliteľné env pred `first-run` — heslo prvého admina |
| `FIRST_ADMIN_NAME` | Voliteľné env pred `first-run` — zobrazované meno |
| `SESSION_LIFETIME` | Dĺžka session (odporúčané 7200–28800 s) |
| `TWO_FACTOR_REQUIRED` | `false` len pri dev; produkcia vždy vyžaduje 2FA pre staff |
| `DEMO_MODE` | **`false`** na zákazníckej inštancii (demo len na demo.paginiumcms.com) |

Hodnoty s medzerou musia byť v úvodzovkách, napr. `DEV_UNLOCK_SECRET="secret code"`.

---

## Čo **nie je** v inštalačnom balíku

Tieto veci sa vytvárajú pri behu alebo obsahujú citlivé dáta:

| Nepatrí do balíka | Prečo |
|-------------------|--------|
| `.env` | Heslá, kľúče, URL |
| `vendor/` | Dá sa obnoviť cez `composer install` (voliteľne v balíku pre offline) |
| `node_modules/` | Obnoví `npm ci` |
| `backend/storage/logs/` | Runtime logy |
| `backend/storage/cache/` | Runtime cache |
| `backend/storage/app/content/data/users/` | Hashované heslá |
| `backend/storage/backups/` | Zálohy z prevádzky |

**Patrí do beta balíka:** zdrojový kód `backend/`, `frontend/` (alebo hotový `frontend/dist/`), `composer.json` + `composer.lock`, `docker-compose.yml`, `scripts/first-run.sh`, `.env.example`, `docs/user/`.

---

## Riešenie problémov po inštalácii

| Problém | Riešenie |
|---------|----------|
| `/api/*` vracia HTML namiesto JSON | Chýba nginx proxy — viď [NGINX_API.md](../deploy/NGINX_API.md) |
| 401 po prihlásení | Reštart PHP; `SESSION_STRICT=false` za reverse proxy |
| Obrázky 404 na webe | Proxy `/storage` na backend |
| Prázdny obsah / chyby indexu | `php backend/bin/console content:diagnose --fix` |
| Nemôžem sa prihlásiť | `./scripts/first-run.sh` alebo `php backend/bin/bootstrap-admin.php` |

Ďalší krok: **[FIRST_STEPS.md](FIRST_STEPS.md)** — prihlásenie a prvý obsah.
